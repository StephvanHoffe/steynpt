<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Kalenderweergaven voor het beheer: welke dagen een weergave toont, bladeren en de
 * indeling van afspraken in het tijdrooster. Puur (geen database), zodat het los te testen is.
 */
final class AgendaCalendar
{
    public const CALENDAR_VIEWS = [
        ['id' => 'dag', 'label' => 'Dag'],
        ['id' => 'week', 'label' => 'Week'],
        ['id' => 'maand', 'label' => 'Maand'],
        ['id' => 'lijst', 'label' => 'Lijst'],
    ];

    /** Aantal dagen dat de lijstweergave toont. */
    public const LIST_DAYS = 28;

    public static function parseView(mixed $v): string
    {
        foreach (self::CALENDAR_VIEWS as $view) {
            if ($view['id'] === $v) {
                return $v;
            }
        }

        return 'week';
    }

    public static function startOfWeek(string $day): string
    {
        return Agenda::addDays($day, 1 - Agenda::weekdayOf($day));
    }

    public static function startOfMonth(string $day): string
    {
        return substr($day, 0, 7).'-01';
    }

    public static function addMonths(string $day, int $n): string
    {
        $d = Js::splitNumbers($day, '-');

        return Js::isoDate(Js::dateUtc($d[0], ($d[1] ?? NAN) - 1 + $n, 1));
    }

    /**
     * Dagen die een weergave toont (bij de maand: hele weken, maandag t/m zondag).
     *
     * @return list<string>
     */
    public static function viewDays(string $view, string $day): array
    {
        $range = fn (string $from, int $n) => array_map(fn (int $i) => Agenda::addDays($from, $i), $n > 0 ? range(0, $n - 1) : []);
        if ($view === 'dag') {
            return [$day];
        }
        if ($view === 'week') {
            return $range(self::startOfWeek($day), 7);
        }
        if ($view === 'lijst') {
            return $range($day, self::LIST_DAYS);
        }
        $first = self::startOfMonth($day);
        $gridStart = self::startOfWeek($first);
        $last = Agenda::addDays(self::addMonths($first, 1), -1);
        $gridEnd = Agenda::addDays(self::startOfWeek($last), 6);
        $n = (int) Js::round((Js::parseDay($gridEnd) - Js::parseDay($gridStart)) / 864e5) + 1;

        return $range($gridStart, $n);
    }

    /**
     * Vorige of volgende periode.
     *
     * @param  -1|1  $direction
     */
    public static function shiftDay(string $view, string $day, int $direction): string
    {
        if ($view === 'dag') {
            return Agenda::addDays($day, $direction);
        }
        if ($view === 'week') {
            return Agenda::addDays($day, 7 * $direction);
        }
        if ($view === 'lijst') {
            return Agenda::addDays($day, self::LIST_DAYS * $direction);
        }

        return self::addMonths(self::startOfMonth($day), $direction);
    }

    /** ISO-weeknummer van een dag ("2026-10-05" -> 41). */
    public static function isoWeek(string $day): int
    {
        $d = Js::splitNumbers($day, '-');
        $date = Js::dateUtc($d[0], ($d[1] ?? NAN) - 1, $d[2] ?? NAN);
        $date += (4 - (Js::utcDay($date) ?: 7)) * 86_400_000;
        $yearStart = Js::dateUtc((int) substr(Js::isoDate($date), 0, -6), 0, 1);

        return (int) ceil((($date - $yearStart) / 864e5 + 1) / 7);
    }

    // ---------------------------------------------------------------------------
    // Tijdrooster

    /** Minuten sinds middernacht (Nederlandse tijd). */
    public static function minutesOfDay(CarbonInterface $date): int
    {
        [$h, $m] = array_map('intval', explode(':', Agenda::zonedParts($date)['time']));

        return $h * 60 + $m;
    }

    public static function timeToMinutes(string $time): int|float
    {
        $parts = Js::splitNumbers($time, ':');

        return Js::num($parts[0] * 60 + ($parts[1] ?? NAN));
    }

    /**
     * Uren die het rooster toont: standaard 07:00–21:00, ruimer als er eerder of later iets staat.
     *
     * @param  iterable<array{start: int|float, end: int|float}>  $items  minuten sinds middernacht
     * @param  array{start: int, end: int}  $fallback
     * @return array{start: int, end: int}
     */
    public static function gridHours(iterable $items, array $fallback = ['start' => 7, 'end' => 21]): array
    {
        $start = $fallback['start'];
        $end = $fallback['end'];
        foreach ($items as $item) {
            $start = min($start, (int) floor($item['start'] / 60));
            $end = max($end, (int) ceil($item['end'] / 60));
        }

        return ['start' => max(0, $start), 'end' => min(24, $end)];
    }

    /**
     * Zet afspraken die elkaar overlappen naast elkaar: elke afspraak krijgt een kolom (lane)
     * en het aantal kolommen van de groep waarin ze valt.
     *
     * @param  list<array{id: int|string, start: int|float, end: int|float}>  $events
     * @return array<int|string, array{lane: int, lanes: int}> per id
     */
    public static function layoutLanes(array $events): array
    {
        $sorted = $events;
        usort($sorted, fn (array $a, array $b) => ($a['start'] <=> $b['start']) ?: ($b['end'] <=> $a['end']));
        $result = [];
        $group = [];
        $laneEnds = [];
        $groupEnd = -INF;

        $closeGroup = function () use (&$result, &$group, &$laneEnds): void {
            foreach ($group as $g) {
                $result[$g['id']] = ['lane' => $g['lane'], 'lanes' => count($laneEnds)];
            }
            $group = [];
            $laneEnds = [];
        };

        foreach ($sorted as $e) {
            if ($e['start'] >= $groupEnd) {
                $closeGroup();
                $groupEnd = -INF;
            }
            $lane = -1;
            foreach ($laneEnds as $i => $end) {
                if ($end <= $e['start']) {
                    $lane = $i;
                    break;
                }
            }
            if ($lane === -1) {
                $lane = count($laneEnds);
                $laneEnds[] = $e['end'];
            } else {
                $laneEnds[$lane] = $e['end'];
            }
            $group[] = ['id' => $e['id'], 'lane' => $lane];
            $groupEnd = max($groupEnd, $e['end']);
        }
        $closeGroup();

        return $result;
    }
}
