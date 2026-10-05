<?php

namespace Tests\Unit;

use App\Support\Progress;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ProgressTest extends TestCase
{
    public function test_meting_komma_decimalen_lege_velden_en_minimaal_een_waarde(): void
    {
        [$ok, $errors] = Progress::validateMeasurement(['measuredAt' => '2026-10-01', 'weight' => '74,6', 'bodyFat' => '', 'note' => '']);
        $this->assertSame([], $errors);
        $this->assertSame(74.6, $ok['weight']);
        $this->assertNull($ok['bodyFat']);
        $this->assertNull($ok['note']);
        $this->assertNotEmpty(Progress::validateMeasurement(['measuredAt' => '2026-10-01', 'weight' => ''])[1]);
        $this->assertNotEmpty(Progress::validateMeasurement(['measuredAt' => '2026-10-01', 'weight' => '7'])[1], 'onrealistisch gewicht');
        $this->assertNotEmpty(Progress::validateMeasurement(['measuredAt' => 'gisteren', 'weight' => '70'])[1]);
    }

    public function test_meting_meldingen_zoals_zod(): void
    {
        $errors = fn (array $input) => Progress::validateMeasurement($input)[1];
        $this->assertSame(['weight' => 'Vul minimaal één meetwaarde in'], $errors(['measuredAt' => '2026-10-01', 'weight' => '']));
        $this->assertSame(['weight' => 'Gewicht lijkt niet te kloppen'], $errors(['measuredAt' => '2026-10-01', 'weight' => '7']));
        $this->assertSame(['measuredAt' => 'Kies een datum'], $errors(['measuredAt' => 'gisteren', 'weight' => '70']));
        $this->assertSame(['measuredAt' => 'Kies een datum', 'weight' => 'Vul minimaal één meetwaarde in'], $errors(['measuredAt' => 'gisteren', 'weight' => '']));
        $this->assertSame(['measuredAt' => 'Kies een datum'], $errors(['measuredAt' => '2026-02-30', 'weight' => '70']));
        $this->assertSame(['measuredAt' => 'Kies een datum'], $errors(['measuredAt' => '2023-02-29', 'weight' => '70']));
        $this->assertSame(['measuredAt' => 'Kies een datum'], $errors(['weight' => '70']), 'ontbrekende datum');
        $this->assertSame(['weight' => 'Invalid input'], $errors(['measuredAt' => '2026-10-01', 'weight' => 'abc']), 'geen getal: algemene melding van de union');
        $this->assertSame(['weight' => 'Invalid input', 'bodyFat' => 'Invalid input'], $errors(['measuredAt' => '2026-10-01', 'weight' => '1,2,3', 'bodyFat' => 'Infinity']));
        $this->assertSame(['measuredAt' => 'Kies een datum', 'note' => 'Invalid input: expected string, received null'], $errors(['measuredAt' => 5, 'weight' => '70', 'note' => null]));
        $this->assertSame(['note' => 'Too big: expected string to have <=1000 characters'], $errors(['measuredAt' => '2026-10-01', 'weight' => '70', 'note' => str_repeat('x', 1001)]));
        $this->assertSame([], $errors(['measuredAt' => '2026-10-01', 'weight' => '70', 'note' => str_repeat('😀', 1000)]), 'lengte in tekens, niet in bytes');
        $this->assertSame(['weight' => 'Gewicht lijkt niet te kloppen'], $errors(['measuredAt' => '2026-10-01', 'weight' => true]));
    }

    public function test_meting_normaliseert_alle_velden(): void
    {
        [$data] = Progress::validateMeasurement(['measuredAt' => '2024-02-29', 'weight' => '70', 'bodyFat' => '0x10', 'hip' => ' 90 ', 'arm' => 30, 'note' => '  notitie ', 'userId' => 'u1']);
        $this->assertSame([
            'measuredAt' => '2024-02-29', 'note' => 'notitie', 'weight' => 70, 'bodyFat' => 16, 'muscleMass' => null,
            'waist' => null, 'hip' => 90, 'chest' => null, 'arm' => 30, 'thigh' => null,
        ], $data);
    }

    public function test_meting_uit_formulier_met_lege_velden_als_null(): void
    {
        $raw = Progress::measurementFromFormData(['measuredAt' => '2026-10-01', 'weight' => '74,6', 'note' => null, 'bodyFat' => null]);
        [$data, $errors] = Progress::validateMeasurement($raw);
        $this->assertSame([], $errors);
        $this->assertNull($data['note']);
        $this->assertNull($data['bodyFat']);
    }

    public function test_voortgang_verschil_eerste_en_laatste_meting_per_waarde(): void
    {
        $rows = [
            ['measuredAt' => CarbonImmutable::parse('2026-09-01'), 'weight' => 80, 'bodyFat' => 24, 'waist' => null],
            ['measuredAt' => CarbonImmutable::parse('2026-08-01'), 'weight' => 82, 'bodyFat' => null, 'waist' => 90],
            ['measuredAt' => CarbonImmutable::parse('2026-10-01'), 'weight' => 78.5, 'bodyFat' => 22.5, 'waist' => null],
        ];
        $s = Progress::progressSummary($rows);
        $find = fn (string $key) => array_values(array_filter($s, fn ($f) => $f['key'] === $key))[0] ?? null;
        $weight = $find('weight');
        $this->assertCount(3, $weight['points']);
        $this->assertSame(78.5, $weight['latest']['value']);
        $this->assertSame(-3.5, $weight['change']);
        $this->assertSame(-1.5, $find('bodyFat')['change']);
        $this->assertNull($find('waist')['change'], 'één meting: geen verschil');
        $this->assertNull($find('hip'));
        $this->assertSame('2026-08-01', $weight['since']->format('Y-m-d'));
        $this->assertSame(['weight', 'bodyFat', 'waist'], array_column($s, 'key'));
    }

    public function test_astikken_zijn_ronde_getallen_rond_de_data(): void
    {
        $this->assertSame([78, 79, 80, 81, 82], Progress::niceTicks(78.5, 82));
        $this->assertSame([22.5, 23, 23.5, 24], Progress::niceTicks(22.5, 24));
        $flat = Progress::niceTicks(70, 70);
        $this->assertTrue($flat[0] < 70 && $flat[count($flat) - 1] > 70);
        $this->assertSame([66, 68, 70, 72, 74], $flat);
        $this->assertSame([0.0012, 0.0014, 0.0016, 0.0018, 0.002, 0.0022], Progress::niceTicks(0.0013, 0.0021));
        $this->assertSame([], Progress::niceTicks(5, 1));
    }

    public function test_getallen_in_nederlandse_notatie(): void
    {
        $this->assertSame('1.234,6', Progress::formatNumber(1234.56));
        $this->assertSame('1.234,56', Progress::formatNumber(1234.56, 2));
        $this->assertSame('-3,5', Progress::formatNumber(-3.5));
        $this->assertSame('-4', Progress::formatNumber(-3.5, 0));
        $this->assertSame('2,3', Progress::formatNumber(2.25));
        $this->assertSame('1,01', Progress::formatNumber(1.005, 2));
        $this->assertSame('0', Progress::formatNumber(0.04));
        $this->assertSame('-0', Progress::formatNumber(-0.04));
        $this->assertSame('-0', Progress::formatNumber(-0.0));
        $this->assertSame('100', Progress::formatNumber(99.96));
        $this->assertSame('74,6', Progress::formatNumber(74.6));
        $this->assertSame('1.000.000.000.000.000.000.000', Progress::formatNumber(1e21));
    }
}
