<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Weeknummers en check-in-reeksen. Net als in de TypeScript-versie telt de kalenderdatum van het
 * tijdstip in zijn eigen tijdzone (daar: de tijdzone van de server; hier: die van het Carbon-object).
 */
final class Weeks
{
    /** ISO-weeknummer, bijvoorbeeld "2026-W40". */
    public static function isoWeekKey(CarbonInterface $date): string
    {
        $d = Js::dateUtc($date->year, $date->month - 1, $date->day);
        $day = Js::utcDay($d) ?: 7;
        $d += (4 - $day) * 86_400_000;
        $year = (int) substr(Js::isoDate($d), 0, -6);
        $yearStart = Js::dateUtc($year, 0, 1);
        $week = (int) ceil((($d - $yearStart) / 86400000 + 1) / 7);

        return $year.'-W'.str_pad((string) $week, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Aantal opeenvolgende weken met een check-in, eindigend in deze week
     * (of vorige week, als deze week nog geen check-in heeft).
     *
     * @param  list<string>  $weeks  weeksleutels zoals "2026-W40"
     */
    public static function checkInStreak(array $weeks, ?CarbonInterface $now = null): int
    {
        $done = array_flip($weeks);
        $cursor = CarbonImmutable::instance($now ?? CarbonImmutable::now());
        if (! isset($done[self::isoWeekKey($cursor)])) {
            $cursor = $cursor->subDays(7);
        }
        $streak = 0;
        while (isset($done[self::isoWeekKey($cursor)])) {
            $streak++;
            $cursor = $cursor->subDays(7);
        }

        return $streak;
    }
}
