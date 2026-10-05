<?php

namespace Tests\Unit;

use App\Support\AgendaCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AgendaCalendarTest extends TestCase
{
    public function test_kalender_weergaven_en_dagen(): void
    {
        $this->assertSame('maand', AgendaCalendar::parseView('maand'));
        $this->assertSame('week', AgendaCalendar::parseView('onzin'));
        $this->assertSame('week', AgendaCalendar::parseView(null));
        $this->assertSame('2026-09-28', AgendaCalendar::startOfWeek('2026-10-04'), 'zondag hoort bij de week vanaf maandag');
        $this->assertSame(['2026-10-05'], AgendaCalendar::viewDays('dag', '2026-10-05'));
        $week = AgendaCalendar::viewDays('week', '2026-10-07');
        $this->assertSame('2026-10-05', $week[0]);
        $this->assertSame('2026-10-11', $week[count($week) - 1]);

        $oktober = AgendaCalendar::viewDays('maand', '2026-10-15');
        $this->assertSame('2026-09-28', $oktober[0]);
        $this->assertSame('2026-11-01', $oktober[count($oktober) - 1]);
        $this->assertSame(0, count($oktober) % 7);
        $this->assertCount(28, AgendaCalendar::viewDays('maand', '2027-02-10'), 'februari 2027 begint op maandag en past in vier weken');
        $this->assertCount(28, AgendaCalendar::viewDays('lijst', '2026-10-01'));
    }

    public function test_kalender_bladeren_en_weeknummers(): void
    {
        $this->assertSame('2026-11-01', AgendaCalendar::shiftDay('dag', '2026-10-31', 1));
        $this->assertSame('2026-09-28', AgendaCalendar::shiftDay('week', '2026-10-05', -1));
        $this->assertSame('2026-02-01', AgendaCalendar::shiftDay('maand', '2026-01-31', 1));
        $this->assertSame('2025-12-01', AgendaCalendar::shiftDay('maand', '2026-01-15', -1));
        $this->assertSame('2026-11-02', AgendaCalendar::shiftDay('lijst', '2026-10-05', 1));
        $this->assertSame('2027-01-01', AgendaCalendar::addMonths('2026-12-01', 1));
        $this->assertSame('2026-10-01', AgendaCalendar::startOfMonth('2026-10-15'));
        $this->assertSame(41, AgendaCalendar::isoWeek('2026-10-05'));
        $this->assertSame(53, AgendaCalendar::isoWeek('2027-01-01'), '1 januari 2027 valt nog in week 53 van 2026');
        $this->assertSame(1, AgendaCalendar::isoWeek('2027-01-04'));
    }

    public function test_kalender_tijdrooster_en_overlappende_afspraken(): void
    {
        $this->assertSame(7 * 60 + 30, AgendaCalendar::minutesOfDay(CarbonImmutable::parse('2026-10-05T05:30:00Z')), 'zomertijd');
        $this->assertSame(7 * 60 + 30, AgendaCalendar::minutesOfDay(CarbonImmutable::parse('2026-11-02T06:30:00Z')), 'wintertijd');
        $this->assertSame(450, AgendaCalendar::timeToMinutes('07:30'));
        $this->assertSame(['start' => 7, 'end' => 21], AgendaCalendar::gridHours([]));
        $this->assertSame(['start' => 6, 'end' => 23], AgendaCalendar::gridHours([['start' => 6 * 60 + 30, 'end' => 7 * 60], ['start' => 21 * 60, 'end' => 22 * 60 + 15]]));
        $this->assertSame(['start' => 0, 'end' => 24], AgendaCalendar::gridHours([['start' => 0, 'end' => 1440]], ['start' => 9, 'end' => 17]));

        $lanes = AgendaCalendar::layoutLanes([
            ['id' => 1, 'start' => 480, 'end' => 540],
            ['id' => 2, 'start' => 510, 'end' => 540],
            ['id' => 3, 'start' => 540, 'end' => 600],
            ['id' => 4, 'start' => 600, 'end' => 630],
            ['id' => 5, 'start' => 600, 'end' => 660],
            ['id' => 6, 'start' => 615, 'end' => 630],
        ]);
        $this->assertSame(['lane' => 0, 'lanes' => 2], $lanes[1]);
        $this->assertSame(['lane' => 1, 'lanes' => 2], $lanes[2]);
        $this->assertSame(['lane' => 0, 'lanes' => 1], $lanes[3], 'aansluitend is geen overlap');
        $this->assertSame(3, $lanes[5]['lanes']);
        $this->assertNotSame($lanes[4]['lane'], $lanes[6]['lane']);
    }
}
