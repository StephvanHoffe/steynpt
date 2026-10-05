<?php

namespace Tests\Unit;

use App\Support\Agenda;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AgendaTest extends TestCase
{
    private static function utc(string $iso): CarbonImmutable
    {
        return CarbonImmutable::parse($iso)->utc();
    }

    private static function iso(CarbonImmutable $date): string
    {
        return $date->utc()->format('Y-m-d\TH:i:s.v\Z');
    }

    /** @param list<CarbonImmutable> $slots */
    private static function times(array $slots): array
    {
        return array_map(fn (CarbonImmutable $s) => Agenda::zonedParts($s)['time'], $slots);
    }

    private static function windows(): array
    {
        return [['weekday' => 1, 'startTime' => '07:00', 'endTime' => '10:00', 'location' => 'gymbase']];
    }

    public function test_tijdzone_zomer_en_wintertijd_amsterdam(): void
    {
        $this->assertSame('2026-10-05T05:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-10-05', '07:00')));
        $this->assertSame('2026-11-02T06:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-11-02', '07:00')));
        $this->assertSame('2026-03-29T08:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-03-29', '10:00')));
        $this->assertSame('2026-03-28T09:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-03-28', '10:00')));
        $this->assertSame(['day' => '2026-10-25', 'time' => '02:30', 'weekday' => 7], Agenda::zonedParts(self::utc('2026-10-25T01:30:00Z')));
        $this->assertSame(['day' => '2026-10-05', 'time' => '00:30', 'weekday' => 1], Agenda::zonedParts(self::utc('2026-10-04T22:30:00Z')));
    }

    public function test_tijdzone_dst_randgevallen_zoals_in_typescript(): void
    {
        // Niet-bestaande tijd (zomertijd begint): schuift een uur op.
        $this->assertSame('2026-03-29T01:30:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-03-29', '02:30')));
        $this->assertSame('2026-03-29T01:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-03-29', '02:00')));
        // Dubbele tijd (wintertijd begint): de tweede (wintertijd) wint.
        $this->assertSame('2026-10-25T01:30:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-10-25', '02:30')));
        $this->assertSame('2026-10-24T23:59:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-10-25', '01:59')));
        // Overloop zoals Date.UTC
        $this->assertSame('2026-10-05T22:00:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-10-05', '24:00')));
        $this->assertSame('2026-10-05T05:05:00.000Z', self::iso(Agenda::zonedTimeToUtc('2026-10-05', '7:5')));
        $this->assertSame(['day' => '2026-03-29', 'time' => '03:00', 'weekday' => 7], Agenda::zonedParts(self::utc('2026-03-29T01:00:00Z')));
        $this->assertSame('UTC', Agenda::zonedTimeToUtc('2026-10-05', '07:00')->getTimezone()->getName());
    }

    public function test_datums_weekdag_en_dagen_optellen_over_maandgrenzen(): void
    {
        $this->assertSame(1, Agenda::weekdayOf('2026-10-05'));
        $this->assertSame(7, Agenda::weekdayOf('2026-10-11'));
        $this->assertSame('2026-11-02', Agenda::addDays('2026-10-30', 3));
        $this->assertSame('2027-01-01', Agenda::addDays('2026-12-31', 1));
        $this->assertSame('2025-12-31', Agenda::addDays('2026-01-01', -1));
        $this->assertSame('2026-03-03', Agenda::addDays('2026-02-30', 1), 'overloop zoals Date.UTC');
    }

    public function test_is_valid_day_zoals_date_parse(): void
    {
        foreach (['2026-10-05', '2024-02-29', '2026-02-29', '2026-02-31', '2026-04-31', '0000-01-01', '9999-12-31'] as $day) {
            $this->assertTrue(Agenda::isValidDay($day), $day);
        }
        foreach (['2026-02-32', '2026-13-01', '2026-00-10', '2026-02-00', '2026-1-01', "2026-10-05\n", '', null, 5, 'x2026-01-01'] as $day) {
            $this->assertFalse(Agenda::isValidDay($day), var_export($day, true));
        }
    }

    public function test_slots_binnen_het_venster_passend_bij_de_duur(): void
    {
        $pt = Agenda::getAppointmentType('personal-training');
        $now = self::utc('2026-10-01T08:00:00Z');
        $this->assertSame(
            ['07:00', '07:30', '08:00', '08:30', '09:00'],
            self::times(Agenda::computeSlots(type: $pt, location: 'gymbase', day: '2026-10-05', windows: self::windows(), busy: [], now: $now)),
        );
    }

    public function test_slots_bestaande_afspraken_en_blokkades_worden_overgeslagen(): void
    {
        $pt = Agenda::getAppointmentType('personal-training');
        $now = self::utc('2026-10-01T08:00:00Z');
        $busy = [['startsAt' => Agenda::zonedTimeToUtc('2026-10-05', '08:00'), 'endsAt' => Agenda::zonedTimeToUtc('2026-10-05', '09:00')]];
        $this->assertSame(['07:00', '09:00'], self::times(Agenda::computeSlots($pt, 'gymbase', '2026-10-05', self::windows(), $busy, $now)));
    }

    public function test_slots_minimale_aanmeldtijd_horizon_locatie_en_weekdag(): void
    {
        $pt = Agenda::getAppointmentType('personal-training');
        $now = self::utc('2026-10-01T08:00:00Z');
        $late = self::utc('2026-10-04T17:30:00Z'); // zondag 19:30, 12 uur later = maandag 07:30
        $this->assertSame(['07:30', '08:00', '08:30', '09:00'], self::times(Agenda::computeSlots($pt, 'gymbase', '2026-10-05', self::windows(), [], $late)));
        $this->assertSame([], Agenda::computeSlots($pt, 'online', '2026-10-05', self::windows(), [], $now), 'PT niet online');
        $this->assertSame([], Agenda::computeSlots($pt, 'op-locatie', '2026-10-05', self::windows(), [], $now), 'andere locatie');
        $this->assertSame([], Agenda::computeSlots($pt, 'gymbase', '2026-10-06', self::windows(), [], $now), 'dinsdag');
        $this->assertSame([], Agenda::computeSlots($pt, 'gymbase', '2027-01-04', self::windows(), [], $now), 'voorbij de horizon');
    }

    public function test_slots_overlappende_vensters_geven_unieke_gesorteerde_tijden(): void
    {
        $type = Agenda::getAppointmentType('meting');
        $windows = [
            ['weekday' => 1, 'startTime' => '09:00', 'endTime' => '10:00', 'location' => 'gymbase'],
            ['weekday' => 1, 'startTime' => '08:30', 'endTime' => '09:30', 'location' => 'gymbase'],
        ];
        $slots = Agenda::computeSlots($type, 'gymbase', '2026-10-05', $windows, [], self::utc('2026-10-01T08:00:00Z'));
        $this->assertSame(['08:30', '09:00', '09:30'], self::times($slots));
    }

    public function test_ical_escaping_vouwen_en_crlf(): void
    {
        $this->assertSame('Gymbase\, Overtoom\; 1\nnotitie\\\\', Agenda::escapeIcsText("Gymbase, Overtoom; 1\nnotitie\\"));
        $long = 'DESCRIPTION:'.str_repeat('é', 60);
        $folded = Agenda::foldIcsLine($long);
        foreach (explode("\r\n", $folded) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
        $this->assertSame($long, str_replace("\r\n ", '', $folded));

        $ics = Agenda::buildIcs(
            [
                ['uid' => 'appointment-1@steynpt.nl', 'start' => self::utc('2026-10-05T05:00:00Z'), 'end' => self::utc('2026-10-05T06:00:00Z'), 'summary' => 'Personal training – Lisa', 'location' => 'Gymbase, Overtoom 371-w'],
                ['uid' => 'appointment-2@steynpt.nl', 'start' => self::utc('2026-10-06T05:00:00Z'), 'end' => self::utc('2026-10-06T05:30:00Z'), 'summary' => 'Meting', 'cancelled' => true],
            ],
            name: 'SteynPT afspraken',
            now: self::utc('2026-10-01T08:00:00Z'),
        );
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertMatchesRegularExpression('/DTSTART:20261005T050000Z\r\n/', $ics);
        $this->assertMatchesRegularExpression('/LOCATION:Gymbase\\\\, Overtoom 371-w\r\n/', $ics);
        $this->assertMatchesRegularExpression('/STATUS:CANCELLED/', $ics);
        $this->assertDoesNotMatchRegularExpression('/\n/', str_replace("\r\n", '', $ics), 'alleen CRLF-regeleinden');
    }

    public function test_ical_volledige_uitvoer(): void
    {
        $ics = Agenda::buildIcs(
            [['uid' => 'a@steynpt.nl', 'start' => self::utc('2026-10-06T05:00:00Z'), 'end' => self::utc('2026-10-06T05:30:00Z'), 'summary' => 'Meting', 'description' => '', 'updatedAt' => self::utc('2026-09-30T10:11:12.345Z')]],
            'Agenda',
            self::utc('2026-10-01T08:00:00Z'),
        );
        $this->assertSame(implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//SteynPT//Agenda//NL', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'X-WR-CALNAME:Agenda', 'X-WR-TIMEZONE:Europe/Amsterdam', 'REFRESH-INTERVAL;VALUE=DURATION:PT1H', 'X-PUBLISHED-TTL:PT1H',
            'BEGIN:VEVENT', 'UID:a@steynpt.nl', 'DTSTAMP:20261001T080000Z', 'DTSTART:20261006T050000Z', 'DTEND:20261006T053000Z',
            'SUMMARY:Meting', 'STATUS:CONFIRMED', 'SEQUENCE:0', 'LAST-MODIFIED:20260930T101112Z', 'END:VEVENT', 'END:VCALENDAR',
        ])."\r\n", $ics);
    }

    public function test_weergave_in_nederlandse_tijd(): void
    {
        $this->assertSame('maandag 5 oktober', Agenda::formatDayLong(Agenda::dayToDate('2026-10-05')));
        $this->assertSame('ma 5 okt', Agenda::formatDayShort(Agenda::dayToDate('2026-10-05')));
        $this->assertSame('zo 1 mrt', Agenda::formatDayShort(Agenda::dayToDate('2026-03-01')));
        $this->assertSame('00:05', Agenda::formatTime(self::utc('2026-10-04T22:05:00Z')));
        $this->assertSame('maandag 5 oktober', Agenda::formatDayLong(self::utc('2026-10-04T22:05:00Z')), 'dag in Nederland, niet in UTC');
        $this->assertSame('2026-10-05T10:00:00.000Z', self::iso(Agenda::dayToDate('2026-10-05')));
    }

    public function test_afspraaktypes_en_locaties(): void
    {
        $this->assertSame(30, Agenda::getAppointmentType('kennismaking')['minutes']);
        $this->assertSame(1, Agenda::getAppointmentType('kennismaking')['maxUpcoming']);
        $this->assertNull(Agenda::getAppointmentType('onbekend'));
        $this->assertNull(Agenda::getAppointmentType(null));
        $this->assertSame('Gymbase', Agenda::getAgendaLocation('gymbase')['label']);
        $this->assertNull(Agenda::getAgendaLocation(null));
        $this->assertSame(42, Agenda::BOOKING_RULES['horizonDays']);
    }
}
