<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Hulpjes die het gedrag van JavaScript nabootsen (Number(), Math.round, String(number), trim,
 * Date.UTC, encodeURIComponent …). De Support-klassen zijn 1-op-1 overgezet uit de TypeScript-code
 * en gebruiken deze functies zodat randgevallen (afronding, coercion, datumoverloop) identiek zijn.
 *
 * @internal
 */
final class Js
{
    /** Witruimte volgens JavaScript (String.prototype.trim, \s): WhiteSpace + LineTerminator. */
    public const SPACE = '\t\n\x{0B}\f\r\x{FEFF}\x{2028}\x{2029}\p{Zs}';

    private const DAY_MS = 86_400_000;

    /** String.prototype.trim(). */
    public static function trim(string $value): string
    {
        return preg_replace('/^['.self::SPACE.']+|['.self::SPACE.']+$/u', '', $value) ?? trim($value);
    }

    /** Waarheidswaarde zoals in JavaScript ("" / 0 / NaN / null / false zijn onwaar, "0" en [] niet). */
    public static function truthy(mixed $value): bool
    {
        return match (true) {
            $value === null, $value === false, $value === '' => false,
            is_int($value) => $value !== 0,
            is_float($value) => ! is_nan($value) && $value != 0.0,
            default => true,
        };
    }

    /** Number(value). Een ontbrekende waarde (undefined) moet de aanroeper zelf als NAN behandelen. */
    public static function toNumber(mixed $value): float
    {
        return match (true) {
            $value === null => 0.0,
            is_bool($value) => $value ? 1.0 : 0.0,
            is_int($value), is_float($value) => (float) $value,
            is_string($value) => self::stringToNumber($value),
            is_array($value) && array_is_list($value) => self::stringToNumber(self::toString($value)),
            default => NAN,
        };
    }

    /** Number("…") volgens StringNumericLiteral: decimaal, Infinity, 0x/0o/0b; lege tekst = 0. */
    public static function stringToNumber(string $value): float
    {
        $s = self::trim($value);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/^[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?$/D', $s)) {
            return (float) $s;
        }
        if (preg_match('/^([+-]?)Infinity$/D', $s, $m)) {
            return $m[1] === '-' ? -INF : INF;
        }
        if (preg_match('/^0[xX]([0-9a-fA-F]+)$/D', $s, $m)) {
            return (float) hexdec($m[1]);
        }
        if (preg_match('/^0[oO]([0-7]+)$/D', $s, $m)) {
            return (float) octdec($m[1]);
        }
        if (preg_match('/^0[bB]([01]+)$/D', $s, $m)) {
            return (float) bindec($m[1]);
        }

        return NAN;
    }

    /** String(value) voor de waarden die in deze code voorkomen. */
    public static function toString(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => self::numberToString($value),
            is_string($value) => $value,
            is_array($value) && array_is_list($value) => implode(',', array_map(
                fn ($v) => $v === null ? '' : self::toString($v),
                $value,
            )),
            default => '[object Object]',
        };
    }

    /** Number.isSafeInteger(). */
    public static function isSafeInteger(float|int $n): bool
    {
        return is_int($n)
            ? abs($n) <= 9_007_199_254_740_991
            : is_finite($n) && floor($n) === $n && abs($n) <= 9_007_199_254_740_991;
    }

    /** Een JS-getal als int wanneer het een (veilig) geheel getal is, anders als float. */
    public static function num(float|int $n): float|int
    {
        if (is_float($n) && is_finite($n) && floor($n) === $n && abs($n) <= 9_007_199_254_740_991) {
            return (int) $n;
        }

        return $n;
    }

    /** Math.round(): halverwege altijd naar boven (richting +∞). */
    public static function round(float|int $x): float
    {
        $x = (float) $x;
        if (! is_finite($x)) {
            return $x;
        }
        $floor = floor($x);

        return $x - $floor >= 0.5 ? $floor + 1 : $floor;
    }

    /** String(number) zoals JavaScript (kortste representatie, exponent vanaf 1e21 en onder 1e-6). */
    public static function numberToString(float|int $n): string
    {
        if (is_int($n)) {
            return (string) $n;
        }
        if (is_nan($n)) {
            return 'NaN';
        }
        if (is_infinite($n)) {
            return $n > 0 ? 'Infinity' : '-Infinity';
        }
        if ($n == 0.0) {
            return '0';
        }
        [$digits, $point] = self::shortestDecimal(abs($n));
        $sign = $n < 0 ? '-' : '';
        $k = strlen($digits);
        if ($k <= $point && $point <= 21) {
            return $sign.$digits.str_repeat('0', $point - $k);
        }
        if ($point > 0 && $point <= 21) {
            return $sign.substr($digits, 0, $point).'.'.substr($digits, $point);
        }
        if ($point > -6 && $point <= 0) {
            return $sign.'0.'.str_repeat('0', -$point).$digits;
        }
        $e = $point - 1;

        return $sign.$digits[0].($k > 1 ? '.'.substr($digits, 1) : '').'e'.($e >= 0 ? '+' : '-').abs($e);
    }

    /**
     * Kortste decimale cijfers van een positief eindig getal: [cijfers zonder voorloop-/sluitnullen,
     * positie van de komma]. Voorbeeld: 72.5 -> ["725", 2], 0.004 -> ["4", -2].
     *
     * @return array{0: string, 1: int}
     */
    public static function shortestDecimal(float $value): array
    {
        $previous = ini_get('serialize_precision');
        ini_set('serialize_precision', '-1');
        $repr = var_export($value, true);
        ini_set('serialize_precision', $previous === false ? '-1' : $previous);

        if (! preg_match('/^(\d+)(?:\.(\d+))?(?:E([+-]?\d+))?$/D', $repr, $m)) {
            throw new InvalidArgumentException("Onverwacht getal: {$repr}");
        }
        $all = $m[1].($m[2] ?? '');
        $point = strlen($m[1]) + (int) ($m[3] ?? 0);
        $trimmed = ltrim($all, '0');
        $point -= strlen($all) - strlen($trimmed);
        $digits = rtrim($trimmed, '0');

        return [$digits === '' ? '0' : $digits, $point];
    }

    /** encodeURIComponent(). */
    public static function encodeURIComponent(string $value): string
    {
        return strtr(rawurlencode($value), ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')']);
    }

    // -----------------------------------------------------------------------
    // Datums (Date.UTC, toISOString, Date.parse van "YYYY-MM-DD")

    /** Dagen sinds 1970-01-01 voor een (proleptisch gregoriaanse) datum. */
    public static function daysFromCivil(int $y, int $m, int $d): int
    {
        $y -= $m <= 2 ? 1 : 0;
        $era = intdiv($y >= 0 ? $y : $y - 399, 400);
        $yoe = $y - $era * 400;
        $doy = intdiv(153 * ($m + ($m > 2 ? -3 : 9)) + 2, 5) + $d - 1;
        $doe = $yoe * 365 + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;

        return $era * 146097 + $doe - 719468;
    }

    /** @return array{0: int, 1: int, 2: int} [jaar, maand (1-12), dag] */
    public static function civilFromDays(int $days): array
    {
        $z = $days + 719468;
        $era = intdiv($z >= 0 ? $z : $z - 146096, 146097);
        $doe = $z - $era * 146097;
        $yoe = intdiv($doe - intdiv($doe, 1460) + intdiv($doe, 36524) - intdiv($doe, 146096), 365);
        $y = $yoe + $era * 400;
        $doy = $doe - (365 * $yoe + intdiv($yoe, 4) - intdiv($yoe, 100));
        $mp = intdiv(5 * $doy + 2, 153);
        $d = $doy - intdiv(153 * $mp + 2, 5) + 1;
        $m = $mp < 10 ? $mp + 3 : $mp - 9;

        return [$m <= 2 ? $y + 1 : $y, $m, $d];
    }

    /**
     * Date.UTC(year, monthIndex, day, hours, minutes): milliseconden sinds 1970, met overloop van
     * maanden/dagen/uren zoals JavaScript (jaar 0-99 wordt 1900-1999). Ongeldige invoer geeft een fout,
     * net als `new Date(NaN).toISOString()`.
     */
    public static function dateUtc(float|int $year, float|int $monthIndex, float|int $day = 1, float|int $hours = 0, float|int $minutes = 0, float|int $seconds = 0): int
    {
        foreach ([$year, $monthIndex, $day, $hours, $minutes, $seconds] as $part) {
            if (is_float($part) && ! is_finite($part)) {
                throw new InvalidArgumentException('Invalid time value');
            }
        }
        $y = (int) $year;
        if ($y >= 0 && $y <= 99) {
            $y += 1900;
        }
        $mi = (int) $monthIndex;
        $y += intdiv($mi - (($mi % 12 + 12) % 12), 12);
        $mi = ($mi % 12 + 12) % 12;
        $days = self::daysFromCivil($y, $mi + 1, 1) + (int) $day - 1;

        return $days * self::DAY_MS + (int) $hours * 3_600_000 + (int) $minutes * 60_000 + (int) $seconds * 1000;
    }

    /** new Date(ms).toISOString().slice(0, 10) */
    public static function isoDate(int $ms): string
    {
        [$y, $m, $d] = self::civilFromDays(intdiv($ms - self::floorMod($ms, self::DAY_MS), self::DAY_MS));
        $year = $y >= 0 && $y <= 9999 ? sprintf('%04d', $y) : ($y < 0 ? '-' : '+').sprintf('%06d', abs($y));

        return sprintf('%s-%02d-%02d', $year, $m, $d);
    }

    /** Weekdag van een tijdstip in UTC: 0 = zondag … 6 = zaterdag (getUTCDay). */
    public static function utcDay(int $ms): int
    {
        $days = intdiv($ms - self::floorMod($ms, self::DAY_MS), self::DAY_MS);

        return self::floorMod($days + 4, 7);
    }

    /**
     * Date.parse("YYYY-MM-DD") (UTC-middernacht) volgens V8: maand 1-12 en dag 1-31 (ook 31 februari:
     * die loopt door naar maart). Geeft null als JavaScript NaN zou geven.
     */
    public static function parseDay(string $day): ?int
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $day, $m)) {
            return null;
        }
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        if ($mo < 1 || $mo > 12 || $d < 1 || $d > 31) {
            return null;
        }

        return (self::daysFromCivil($y, $mo, 1) + $d - 1) * self::DAY_MS;
    }

    /** "2026-10-05" -> [2026, 10, 5] zoals `day.split("-").map(Number)`. */
    public static function splitNumbers(string $value, string $separator): array
    {
        return array_map(fn (string $part) => self::stringToNumber($part), explode($separator, $value));
    }

    /** date.getTime(): milliseconden sinds 1970 (afgerond naar beneden). */
    public static function ms(CarbonInterface $date): int
    {
        return $date->getTimestamp() * 1000 + intdiv((int) $date->format('u'), 1000);
    }

    /** new Date(ms), als CarbonImmutable in UTC. */
    public static function fromMs(int|float $ms): CarbonImmutable
    {
        if (is_float($ms) && ! is_finite($ms)) {
            throw new InvalidArgumentException('Invalid time value');
        }

        return CarbonImmutable::createFromTimestampMs((int) $ms, 'UTC');
    }

    public static function floorMod(int $a, int $b): int
    {
        return (($a % $b) + $b) % $b;
    }

    /** Typenaam zoals zod die in "Invalid input: expected …, received …" noemt. */
    public static function zodType(mixed $value, bool $present = true): string
    {
        return match (true) {
            ! $present => 'undefined',
            $value === null => 'null',
            is_bool($value) => 'boolean',
            is_int($value) => 'number',
            is_float($value) => is_nan($value) ? 'NaN' : (is_infinite($value) ? ($value > 0 ? 'Infinity' : '-Infinity') : 'number'),
            is_string($value) => 'string',
            is_array($value) => array_is_list($value) ? 'array' : 'object',
            default => 'object',
        };
    }
}
