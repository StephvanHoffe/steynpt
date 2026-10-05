<?php

namespace App\Services;

use App\Support\Agenda;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Datumnotaties van het beheer, gelijk aan Intl.DateTimeFormat("nl-NL") in de Next.js-versie
 * (altijd in Nederlandse tijd, korte maanden zonder punt: "5 okt").
 */
final class AdminFormat
{
    private const WEEKDAYS = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];

    private const WEEKDAYS_SHORT = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

    private const MONTHS = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];

    private const MONTHS_SHORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    private static function local(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::instance($date)->setTimezone(Agenda::TIME_ZONE);
    }

    /** "5 okt" */
    public static function dayMonthShort(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return $d->day.' '.self::MONTHS_SHORT[$d->month - 1];
    }

    /** "5 okt 2026" */
    public static function dayMonthShortYear(DateTimeInterface $date): string
    {
        return self::dayMonthShort($date).' '.self::local($date)->year;
    }

    /** "5 oktober 2026" */
    public static function dayMonthYear(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return $d->day.' '.self::MONTHS[$d->month - 1].' '.$d->year;
    }

    /** "5 oktober" */
    public static function dayMonth(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return $d->day.' '.self::MONTHS[$d->month - 1];
    }

    /** "5 oktober om 16:30" */
    public static function dayMonthTime(DateTimeInterface $date): string
    {
        return self::dayMonth($date).' om '.self::local($date)->format('H:i');
    }

    /** "ma 5 okt, 16:30" */
    public static function shortDayTime(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return self::WEEKDAYS_SHORT[$d->dayOfWeekIso - 1].' '.self::dayMonthShort($date).', '.$d->format('H:i');
    }

    /** "maandag 5 oktober 2026" */
    public static function longDayYear(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return self::WEEKDAYS[$d->dayOfWeekIso - 1].' '.self::dayMonthYear($date);
    }

    /** "oktober 2026" */
    public static function monthYear(DateTimeInterface $date): string
    {
        $d = self::local($date);

        return self::MONTHS[$d->month - 1].' '.$d->year;
    }

    /** Korte weekdag van een dag "YYYY-MM-DD": "ma". */
    public static function weekdayShort(string $day): string
    {
        return self::WEEKDAYS_SHORT[Agenda::weekdayOf($day) - 1];
    }
}
