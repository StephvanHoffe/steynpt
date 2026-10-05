<?php

namespace App\View;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Container\Container;
use Throwable;

/**
 * Datums en getallen zoals de site ze toont (Europe/Amsterdam), in de taal van het verzoek: Nederlands
 * ("5 oktober 2026", "ma 5 okt") of Engels ("5 October 2026", "Mon 5 Oct"). Het beheer is altijd Nederlands.
 */
final class Fmt
{
    public const TZ = 'Europe/Amsterdam';

    private const WEEKDAYS_SHORT = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];

    private const MONTHS_SHORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    private const WEEKDAYS_SHORT_EN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    private const MONTHS_SHORT_EN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /** Taal van de weergave: die van de app ('en' of 'nl'); zonder Laravel-app (zoals in unittests) Nederlands. */
    public static function lang(): string
    {
        try {
            $app = Container::getInstance();

            return method_exists($app, 'getLocale') && $app->getLocale() === 'en' ? 'en' : 'nl';
        } catch (Throwable) {
            return 'nl';
        }
    }

    public static function local(DateTimeInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->setTimezone(self::TZ)->locale(self::lang());
    }

    /** 5 oktober 2026 (Engels: 5 October 2026) */
    public static function date(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('j F Y');
    }

    /** 5 oktober (Engels: 5 October) */
    public static function dayMonth(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('j F');
    }

    /** 5 okt, of 5 okt 2025 met jaartal (Engels: 5 Oct, 5 Oct 2025) */
    public static function shortDate(DateTimeInterface|string $date, bool $year = false): string
    {
        $d = self::local($date);
        $months = self::lang() === 'en' ? self::MONTHS_SHORT_EN : self::MONTHS_SHORT;

        return $d->day.' '.$months[$d->month - 1].($year ? ' '.$d->year : '');
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

    /** ma 5 okt (zoals Intl.DateTimeFormat met weekday short, day numeric, month short; Engels: Mon 5 Oct) */
    public static function shortDay(DateTimeInterface|string $date): string
    {
        $d = self::local($date);
        [$weekdays, $months] = self::lang() === 'en' ? [self::WEEKDAYS_SHORT_EN, self::MONTHS_SHORT_EN] : [self::WEEKDAYS_SHORT, self::MONTHS_SHORT];

        return $weekdays[$d->dayOfWeek].' '.$d->day.' '.$months[$d->month - 1];
    }

    /** maandag 5 oktober (Engels: Monday 5 October) */
    public static function longDay(DateTimeInterface|string $date): string
    {
        return self::local($date)->translatedFormat('l j F');
    }

    /** Getal met komma, zonder overbodige nullen: 79,4 (Engels: 79.4) */
    public static function number(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '–';
        }
        [$point, $thousands] = self::lang() === 'en' ? ['.', ','] : [',', '.'];
        $text = number_format((float) $value, $decimals, $point, $thousands);

        return str_contains($text, $point) ? rtrim(rtrim($text, '0'), $point) : $text;
    }
}
