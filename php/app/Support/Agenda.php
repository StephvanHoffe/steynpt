<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Agenda: afspraaktypes, tijdzone-hulpjes, berekening van vrije tijdsloten en iCal-export.
 * Alles hier is puur (geen database), zodat het los te testen is.
 *
 * Tijdstippen zijn CarbonImmutable (UTC); dagen zijn "YYYY-MM-DD" in Nederlandse tijd.
 */
final class Agenda
{
    public const TIME_ZONE = 'Europe/Amsterdam';

    public const AGENDA_LOCATIONS = [
        ['id' => 'gymbase', 'label' => 'Gymbase', 'address' => 'Overtoom 371-w, 1054 JN Amsterdam'],
        ['id' => 'op-locatie', 'label' => 'Op locatie', 'address' => 'Locatie in overleg'],
        ['id' => 'online', 'label' => 'Online (videocall)', 'address' => 'Online, Steyn stuurt een link'],
    ];

    public const APPOINTMENT_TYPES = [
        [
            'id' => 'personal-training',
            'label' => 'Personal training',
            'minutes' => 60,
            'locations' => ['gymbase', 'op-locatie'],
            'description' => '1-op-1 training van 60 minuten.',
        ],
        [
            'id' => 'kennismaking',
            'label' => 'Gratis kennismaking',
            'minutes' => 30,
            'locations' => ['gymbase', 'online'],
            'description' => 'Kennismaken, je doelen bespreken en een rondleiding.',
            'maxUpcoming' => 1,
        ],
        [
            'id' => 'meting',
            'label' => 'Meting',
            'minutes' => 30,
            'locations' => ['gymbase'],
            'description' => 'Wegen, meten en vetpercentage bepalen.',
        ],
        [
            'id' => 'online-call',
            'label' => 'Online coaching videocall',
            'minutes' => 30,
            'locations' => ['online'],
            'description' => 'Evaluatie en bijsturen van je online coaching.',
            'requiresCoaching' => true,
        ],
    ];

    public const BOOKING_RULES = [
        'horizonDays' => 42,
        'minNoticeHours' => 12,
        'cancelUntilHours' => 24,
        'slotStepMinutes' => 30,
        'maxUpcomingPerClient' => 8,
    ];

    public const WEEKDAYS = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];

    private const WEEKDAYS_SHORT = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

    private const MONTHS = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];

    private const MONTHS_SHORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    /** @return array{id: string, label: string, minutes: int, locations: list<string>, description: string, maxUpcoming?: int, requiresCoaching?: bool}|null */
    public static function getAppointmentType(?string $id): ?array
    {
        foreach (self::APPOINTMENT_TYPES as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }

        return null;
    }

    /** @return array{id: string, label: string, address: string}|null */
    public static function getAgendaLocation(?string $id): ?array
    {
        foreach (self::AGENDA_LOCATIONS as $location) {
            if ($location['id'] === $id) {
                return $location;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------------
    // Tijdzones

    /** Afwijking van de Nederlandse tijd t.o.v. UTC op dit moment, in milliseconden. */
    private static function offsetMs(int $ms): int
    {
        $seconds = intdiv($ms - Js::floorMod($ms, 1000), 1000);

        return (new DateTimeZone(self::TIME_ZONE))->getOffset(new DateTimeImmutable('@'.$seconds)) * 1000;
    }

    /** "2026-10-05" + "07:30" in Nederlandse tijd -> tijdstip (UTC). */
    public static function zonedTimeToUtc(string $day, string $time): CarbonImmutable
    {
        $d = Js::splitNumbers($day, '-');
        $t = Js::splitNumbers($time, ':');
        $guess = Js::dateUtc($d[0], ($d[1] ?? NAN) - 1, $d[2] ?? NAN, $t[0], $t[1] ?? NAN);
        $first = $guess - self::offsetMs($guess);
        $second = $guess - self::offsetMs($first);

        return Js::fromMs($second);
    }

    /**
     * Datum, tijd en weekdag (1 = maandag) van een moment in Nederlandse tijd.
     *
     * @return array{day: string, time: string, weekday: int}
     */
    public static function zonedParts(CarbonInterface $date): array
    {
        $local = CarbonImmutable::instance($date)->setTimezone(self::TIME_ZONE);
        $day = $local->format('Y-m-d');

        return ['day' => $day, 'time' => $local->format('H:i'), 'weekday' => self::weekdayOf($day)];
    }

    /** Weekdag van een dag: 1 = maandag … 7 = zondag. */
    public static function weekdayOf(string $day): int
    {
        $d = Js::splitNumbers($day, '-');

        return Js::utcDay(Js::dateUtc($d[0], ($d[1] ?? NAN) - 1, $d[2] ?? NAN)) ?: 7;
    }

    public static function addDays(string $day, int $n): string
    {
        $d = Js::splitNumbers($day, '-');

        return Js::isoDate(Js::dateUtc($d[0], ($d[1] ?? NAN) - 1, ($d[2] ?? NAN) + $n));
    }

    public static function isValidDay(mixed $day): bool
    {
        return is_string($day) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $day) === 1 && Js::parseDay($day) !== null;
    }

    // ---------------------------------------------------------------------------
    // Vrije tijdsloten

    private static function overlaps(int $aStart, int $aEnd, int $bStart, int $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }

    /**
     * Starttijden die voor dit afspraaktype op deze dag en locatie vrij zijn.
     *
     * @param  array{minutes: int, locations: list<string>}  $type  een element van APPOINTMENT_TYPES
     * @param  list<array{weekday: int, startTime: string, endTime: string, location: string}>  $windows
     * @param  list<array{startsAt: CarbonInterface, endsAt: CarbonInterface}>  $busy
     * @return list<CarbonImmutable>
     */
    public static function computeSlots(array $type, string $location, string $day, array $windows, array $busy, ?CarbonInterface $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        if (! in_array($location, $type['locations'], true)) {
            return [];
        }
        $earliest = Js::ms($now) + self::BOOKING_RULES['minNoticeHours'] * 3_600_000;
        $latest = Js::ms(self::zonedTimeToUtc(self::addDays(self::zonedParts($now)['day'], self::BOOKING_RULES['horizonDays']), '23:59'));
        $duration = $type['minutes'] * 60_000;
        $step = self::BOOKING_RULES['slotStepMinutes'] * 60_000;
        $weekday = self::weekdayOf($day);
        $busyMs = array_map(fn (array $b) => [Js::ms($b['startsAt']), Js::ms($b['endsAt'])], $busy);
        $slots = [];

        foreach ($windows as $w) {
            if ((int) $w['weekday'] !== $weekday || $w['location'] !== $location) {
                continue;
            }
            $windowEnd = Js::ms(self::zonedTimeToUtc($day, $w['endTime']));
            for ($t = Js::ms(self::zonedTimeToUtc($day, $w['startTime'])); $t + $duration <= $windowEnd; $t += $step) {
                if ($t < $earliest || $t > $latest) {
                    continue;
                }
                foreach ($busyMs as [$start, $end]) {
                    if (self::overlaps($t, $t + $duration, $start, $end)) {
                        continue 2;
                    }
                }
                $slots[$t] = Js::fromMs($t);
            }
        }
        ksort($slots);

        return array_values($slots);
    }

    // ---------------------------------------------------------------------------
    // iCal (RFC 5545)

    private static function icsDate(CarbonInterface $date): string
    {
        return CarbonImmutable::instance($date)->setTimezone('UTC')->format('Ymd\THis\Z');
    }

    public static function escapeIcsText(string $text): string
    {
        $text = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);

        return preg_replace('/\r?\n/', '\\n', $text) ?? $text;
    }

    /** Vouwt regels langer dan 75 octets (UTF-8) zoals de standaard voorschrijft. */
    public static function foldIcsLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }
        $parts = [];
        $current = '';
        foreach (mb_str_split($line, 1, 'UTF-8') as $char) {
            $limit = count($parts) === 0 ? 75 : 74; // vervolgregels beginnen met een spatie
            if (strlen($current.$char) > $limit) {
                $parts[] = $current;
                $current = $char;
            } else {
                $current .= $char;
            }
        }
        $parts[] = $current;

        return implode("\r\n ", $parts);
    }

    /**
     * @param  list<array{uid: string, start: CarbonInterface, end: CarbonInterface, summary: string, location?: ?string, description?: ?string, cancelled?: ?bool, updatedAt?: ?CarbonInterface}>  $events
     */
    public static function buildIcs(array $events, string $name, ?CarbonInterface $now = null): string
    {
        $now ??= CarbonImmutable::now('UTC');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SteynPT//Agenda//NL',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escapeIcsText($name),
            'X-WR-TIMEZONE:'.self::TIME_ZONE,
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];
        foreach ($events as $e) {
            $cancelled = Js::truthy($e['cancelled'] ?? null);
            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                'UID:'.$e['uid'],
                'DTSTAMP:'.self::icsDate($now),
                'DTSTART:'.self::icsDate($e['start']),
                'DTEND:'.self::icsDate($e['end']),
                'SUMMARY:'.self::escapeIcsText($e['summary']),
                ...(Js::truthy($e['location'] ?? null) ? ['LOCATION:'.self::escapeIcsText($e['location'])] : []),
                ...(Js::truthy($e['description'] ?? null) ? ['DESCRIPTION:'.self::escapeIcsText($e['description'])] : []),
                'STATUS:'.($cancelled ? 'CANCELLED' : 'CONFIRMED'),
                'SEQUENCE:'.($cancelled ? 1 : 0),
                ...(isset($e['updatedAt']) ? ['LAST-MODIFIED:'.self::icsDate($e['updatedAt'])] : []),
                'END:VEVENT',
            ];
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::foldIcsLine(...), $lines))."\r\n";
    }

    // ---------------------------------------------------------------------------
    // Weergave: altijd in Nederlandse tijd, ongeacht de tijdzone van de server.

    private static function local(CarbonInterface $date): CarbonImmutable
    {
        return CarbonImmutable::instance($date)->setTimezone(self::TIME_ZONE);
    }

    /** "maandag 5 oktober" */
    public static function formatDayLong(CarbonInterface $date): string
    {
        $d = self::local($date);

        return self::WEEKDAYS[$d->dayOfWeekIso - 1].' '.$d->day.' '.self::MONTHS[$d->month - 1];
    }

    /** "ma 5 okt" */
    public static function formatDayShort(CarbonInterface $date): string
    {
        $d = self::local($date);

        return self::WEEKDAYS_SHORT[$d->dayOfWeekIso - 1].' '.$d->day.' '.self::MONTHS_SHORT[$d->month - 1];
    }

    /** "07:30" */
    public static function formatTime(CarbonInterface $date): string
    {
        return self::local($date)->format('H:i');
    }

    /** Een dag ("2026-10-05") als datum om 12:00, zodat formatteren altijd de juiste dag geeft. */
    public static function dayToDate(string $day): CarbonImmutable
    {
        return self::zonedTimeToUtc($day, '12:00');
    }
}
