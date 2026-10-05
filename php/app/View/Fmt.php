<?php

namespace App\View;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/** Datums en getallen zoals de site ze toont (Nederlands, Europe/Amsterdam). */
final class Fmt
{
    public const TZ = 'Europe/Amsterdam';

    private const WEEKDAYS_SHORT = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];

    private const MONTHS_SHORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    public static function local(DateTimeInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->setTimezone(self::TZ)->locale('nl');
    }

    /** 5 oktober 2026 */
    public static function date(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('j F Y');
    }

    /** 5 oktober */
    public static function dayMonth(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('j F');
    }

    /** 5 okt, of 5 okt 2025 met jaartal */
    public static function shortDate(DateTimeInterface|string $date, bool $year = false): string
    {
        $d = self::local($date);

        return $d->day.' '.self::MONTHS_SHORT[$d->month - 1].($year ? ' '.$d->year : '');
    }

    /** 5 okt dit jaar, anders 5 okt 2025 */
    public static function shortDateNear(DateTimeInterface|string $date): string
    {
        return self::shortDate($date, self::local($date)->year !== CarbonImmutable::now(self::TZ)->year);
    }

    /** 07:30 */
    public static function time(DateTimeInterface|string $date): string
    {
        return self::local($date)->format('H:i');
    }

    /** ma 5 okt (zoals Intl.DateTimeFormat met weekday short, day numeric, month short) */
    public static function shortDay(DateTimeInterface|string $date): string
    {
        $d = self::local($date);

        return self::WEEKDAYS_SHORT[$d->dayOfWeek].' '.$d->day.' '.self::MONTHS_SHORT[$d->month - 1];
    }

    /** maandag 5 oktober */
    public static function longDay(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('l j F');
    }

    /** Getal met komma, zonder overbodige nullen: 79,4 */
    public static function number(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '–';
        }
        $text = number_format((float) $value, $decimals, ',', '.');

        return str_contains($text, ',') ? rtrim(rtrim($text, '0'), ',') : $text;
    }
}
