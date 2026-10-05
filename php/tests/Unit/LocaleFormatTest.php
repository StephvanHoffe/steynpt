<?php

namespace Tests\Unit;

use App\Support\Agenda;
use App\Support\Progress;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** Datums en getallen voor Mijn omgeving in het Engels (de optionele $locale-parameters); Nederlands blijft de standaard. */
class LocaleFormatTest extends TestCase
{
    public function test_agenda_datums_in_het_engels(): void
    {
        $this->assertSame('Monday 5 October', Agenda::formatDayLong(Agenda::dayToDate('2026-10-05'), 'en'));
        $this->assertSame('Mon 5 Oct', Agenda::formatDayShort(Agenda::dayToDate('2026-10-05'), 'en'));
        $this->assertSame('Sun 1 Mar', Agenda::formatDayShort(Agenda::dayToDate('2026-03-01'), 'en'));
        $this->assertSame('Saturday 26 December', Agenda::formatDayLong(Agenda::dayToDate('2026-12-26'), 'en'));
        $this->assertSame('Monday 5 October', Agenda::formatDayLong(CarbonImmutable::parse('2026-10-04T22:05:00Z'), 'en'), 'dag in Nederland, niet in UTC');
    }

    public function test_agenda_datums_standaard_nederlands(): void
    {
        $this->assertSame('maandag 5 oktober', Agenda::formatDayLong(Agenda::dayToDate('2026-10-05')));
        $this->assertSame('maandag 5 oktober', Agenda::formatDayLong(Agenda::dayToDate('2026-10-05'), 'nl'));
        $this->assertSame('ma 5 okt', Agenda::formatDayShort(Agenda::dayToDate('2026-10-05'), 'nl'));
        $this->assertSame('zo 1 mrt', Agenda::formatDayShort(Agenda::dayToDate('2026-03-01'), 'onbekend'));
    }

    public function test_getallen_in_het_engels(): void
    {
        $this->assertSame('1,234.6', Progress::formatNumber(1234.56, 1, 'en'));
        $this->assertSame('1,234.56', Progress::formatNumber(1234.56, 2, 'en'));
        $this->assertSame('-3.5', Progress::formatNumber(-3.5, 1, 'en'));
        $this->assertSame('74.6', Progress::formatNumber(74.6, 1, 'en'));
        $this->assertSame('80', Progress::formatNumber(80, 1, 'en'));
        $this->assertSame('1,000,000', Progress::formatNumber(1e6, 1, 'en'));
        $this->assertSame('-0', Progress::formatNumber(-0.04, 1, 'en'));
        // Nederlands blijft de standaard.
        $this->assertSame('1.234,6', Progress::formatNumber(1234.56));
        $this->assertSame('1.234,6', Progress::formatNumber(1234.56, 1, 'nl'));
    }
}
