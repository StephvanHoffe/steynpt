<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Metingen en voortgang: validatie van een meting, samenvatting per meetwaarde en astikken voor grafieken.
 */
final class Progress
{
    /** Metingen die Steyn invoert. Volgorde = volgorde in tabellen en grafieken. */
    public const MEASUREMENT_FIELDS = [
        ['key' => 'weight', 'label' => 'Gewicht', 'unit' => 'kg', 'min' => 30, 'max' => 300],
        ['key' => 'bodyFat', 'label' => 'Vetpercentage', 'unit' => '%', 'min' => 2, 'max' => 70],
        ['key' => 'muscleMass', 'label' => 'Spiermassa', 'unit' => 'kg', 'min' => 10, 'max' => 150],
        ['key' => 'waist', 'label' => 'Taille', 'unit' => 'cm', 'min' => 40, 'max' => 200],
        ['key' => 'hip', 'label' => 'Heup', 'unit' => 'cm', 'min' => 50, 'max' => 200],
        ['key' => 'chest', 'label' => 'Borst', 'unit' => 'cm', 'min' => 50, 'max' => 200],
        ['key' => 'arm', 'label' => 'Bovenarm', 'unit' => 'cm', 'min' => 15, 'max' => 70],
        ['key' => 'thigh', 'label' => 'Bovenbeen', 'unit' => 'cm', 'min' => 25, 'max' => 110],
    ];

    /** ISO-datum zoals z.iso.date() (met schrikkeljaren). */
    public const ISO_DATE_PATTERN = '/^(?:(?:\d\d[2468][048]|\d\d[13579][26]|\d\d0[48]|[02468][048]00|[13579][26]00)-02-29|\d{4}-(?:(?:0[13578]|1[02])-(?:0[1-9]|[12]\d|3[01])|(?:0[469]|11)-(?:0[1-9]|[12]\d|30)|(?:02)-(?:0[1-9]|1\d|2[0-8])))$/D';

    /**
     * Zet formulierinvoer om naar invoer voor validateMeasurement (zoals Object.fromEntries(formData)).
     * Laravel maakt van lege velden null (ConvertEmptyStringsToNull); een formulier stuurt altijd tekst,
     * dus null wordt hier weer "".
     */
    public static function measurementFromFormData(array $formData): array
    {
        $raw = [];
        foreach ($formData as $key => $value) {
            if ($value === null) {
                $raw[$key] = '';
            } elseif (is_string($value)) {
                $raw[$key] = $value;
            }
        }

        return $raw;
    }

    /**
     * Valideert een meting (measurementSchema). Geeft [data, fouten]: data is null bij fouten; fouten zijn
     * per veld de eerste melding (zoals fieldErrorsFrom). Lege meetwaarden worden null; komma's mogen.
     *
     * @return array{0: ?array{measuredAt: string, note: ?string, weight: int|float|null, bodyFat: int|float|null, muscleMass: int|float|null, waist: int|float|null, hip: int|float|null, chest: int|float|null, arm: int|float|null, thigh: int|float|null}, 1: array<string, string>}
     */
    public static function validateMeasurement(array $input): array
    {
        $errors = [];
        $aborted = false;
        $add = function (string $key, string $message, bool $abort) use (&$errors, &$aborted): void {
            $errors[$key] ??= $message;
            $aborted = $aborted || $abort;
        };
        $data = [];

        // measuredAt: z.iso.date("Kies een datum")
        $measuredAt = $input['measuredAt'] ?? null;
        if (! is_string($measuredAt)) {
            $add('measuredAt', 'Kies een datum', true);
        } elseif (! preg_match(self::ISO_DATE_PATTERN, $measuredAt)) {
            $add('measuredAt', 'Kies een datum', false);
        }
        $data['measuredAt'] = $measuredAt;

        // note: z.string().trim().max(1000).optional().transform((v) => v || null)
        if (! array_key_exists('note', $input)) {
            $data['note'] = null;
        } elseif (! is_string($input['note'])) {
            $add('note', 'Controleer de notitie', true);
        } else {
            $note = Js::trim($input['note']);
            if (mb_strlen($note, 'UTF-8') > 1000) {
                $add('note', 'De notitie mag maximaal 1000 tekens hebben', false);
            }
            $data['note'] = $note === '' ? null : $note;
        }

        foreach (self::MEASUREMENT_FIELDS as $field) {
            $key = $field['key'];
            $v = $input[$key] ?? null;
            // Leeg -> null; tekst: eerste komma wordt een punt.
            if (is_string($v)) {
                $v = Js::trim($v) === '' ? null : Js::trim(preg_replace('/,/', '.', $v, 1) ?? $v);
            }
            if ($v === null) {
                $data[$key] = null;

                continue;
            }
            $n = Js::toNumber($v);
            if (! is_finite($n)) {
                // Geen enkele optie van de union past: de melding van de union zelf.
                $add($key, "{$field['label']}: vul een getal in", true);

                continue;
            }
            if ($n < $field['min'] || $n > $field['max']) {
                $add($key, "{$field['label']} lijkt niet te kloppen", false);
            }
            $data[$key] = Js::num($n);
        }

        // Pas als er geen afbrekende fout is, controleert zod de meting als geheel.
        if (! $aborted) {
            $any = false;
            foreach (self::MEASUREMENT_FIELDS as $field) {
                $any = $any || ($data[$field['key']] ?? null) !== null;
            }
            if (! $any) {
                $add('weight', 'Vul minimaal één meetwaarde in', false);
            }
        }

        return $errors === [] ? [$data, []] : [null, $errors];
    }

    /**
     * Getal in Nederlandse notatie ("1.234,5"), zoals toLocaleString("nl-NL", { maximumFractionDigits }).
     * Met $locale 'en' in Engelse notatie ("1,234.5").
     */
    public static function formatNumber(int|float $n, int $digits = 1, string $locale = 'nl'): string
    {
        if (is_float($n) && is_nan($n)) {
            return 'NaN';
        }
        $negative = $n < 0 || (is_float($n) && $n == 0.0 && fdiv(1, $n) < 0);
        if (is_float($n) && is_infinite($n)) {
            return ($negative ? '-' : '').'∞';
        }
        if ($n == 0) {
            [$digitsStr, $point] = ['0', 1];
        } elseif (is_int($n)) {
            $digitsStr = (string) abs($n);
            $point = strlen($digitsStr);
        } else {
            [$digitsStr, $point] = Js::shortestDecimal(abs($n));
        }
        // Volledige decimale notatie: geheel deel en breuk.
        if ($point <= 0) {
            $int = '0';
            $frac = str_repeat('0', -$point).$digitsStr;
        } elseif ($point >= strlen($digitsStr)) {
            $int = $digitsStr.str_repeat('0', $point - strlen($digitsStr));
            $frac = '';
        } else {
            $int = substr($digitsStr, 0, $point);
            $frac = substr($digitsStr, $point);
        }
        // Afronden: halverwege van nul af (zoals Intl).
        if (strlen($frac) > $digits) {
            $roundUp = $frac[$digits] >= '5';
            $frac = substr($frac, 0, $digits);
            if ($roundUp) {
                $all = $int.$frac;
                $i = strlen($all) - 1;
                while ($i >= 0 && $all[$i] === '9') {
                    $all[$i] = '0';
                    $i--;
                }
                $all = $i < 0 ? '1'.$all : substr_replace($all, (string) ((int) $all[$i] + 1), $i, 1);
                $int = substr($all, 0, strlen($all) - $digits);
                $frac = $digits > 0 ? substr($all, -$digits) : '';
            }
        }
        $int = ltrim($int, '0') ?: '0';
        $frac = rtrim($frac, '0');
        [$thousands, $point] = $locale === 'en' ? [',', '.'] : ['.', ','];
        $grouped = strrev(implode($thousands, str_split(strrev($int), 3)));

        return ($negative ? '-' : '').$grouped.($frac !== '' ? $point.$frac : '');
    }

    /**
     * Verschil tussen eerste en laatste meting per waarde (alleen als er minstens twee metingen zijn).
     *
     * @param  list<array<string, mixed>>  $rows  met 'measuredAt' (CarbonInterface) en meetwaarden
     * @return list<array{key: string, label: string, unit: string, min: int, max: int, points: list<array{date: CarbonInterface, value: int|float}>, latest: ?array{date: CarbonInterface, value: int|float}, change: int|float|null, since: ?CarbonInterface}>
     */
    public static function progressSummary(array $rows): array
    {
        $sorted = $rows;
        usort($sorted, fn (array $a, array $b) => Js::ms($a['measuredAt']) <=> Js::ms($b['measuredAt']));
        $result = [];
        foreach (self::MEASUREMENT_FIELDS as $field) {
            $points = [];
            foreach ($sorted as $r) {
                if (isset($r[$field['key']])) {
                    $points[] = ['date' => $r['measuredAt'], 'value' => $r[$field['key']]];
                }
            }
            if ($points === []) {
                continue;
            }
            $latest = $points[count($points) - 1];
            $first = $points[0];
            $result[] = [
                ...$field,
                'points' => $points,
                'latest' => $latest,
                'change' => count($points) > 1 ? $latest['value'] - $first['value'] : null,
                'since' => $first['date'],
            ];
        }

        return $result;
    }

    /**
     * "Mooie" astikken voor een grafiek, bijvoorbeeld 70, 72, 74, 76.
     *
     * @return list<int|float>
     */
    public static function niceTicks(int|float $min, int|float $max, int $count = 4): array
    {
        if ($min == $max) {
            $pad = max(abs($min) * 0.05, 1);
            $min -= $pad;
            $max += $pad;
        }
        $raw = ($max - $min) / $count;
        $magnitude = 10 ** floor(log10($raw));
        $step = null;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $magnitude >= $raw) {
                $step = $m * $magnitude;
                break;
            }
        }
        $step ??= 10 * $magnitude;
        $start = floor($min / $step) * $step;
        $end = ceil($max / $step) * $step;
        $ticks = [];
        for ($t = $start; $t <= $end + $step / 1000; $t += $step) {
            $ticks[] = Js::num(round($t, 6) + 0.0);
        }

        return $ticks;
    }
}
