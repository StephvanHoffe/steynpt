<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Kleine hulpfuncties die het gedrag van JavaScript nabootsen (witruimte, lengte van tekst, getallen als tekst).
 * Het tekstbeheer kwam uit een TypeScript-versie; hiermee schoont en controleert de PHP-versie precies hetzelfde,
 * met dezelfde foutmeldingen (zoals het aantal tekens) en dezelfde notatie van bedragen.
 */
final class Js
{
    /** Tekens die in JavaScript als witruimte tellen (\s en trim()), voor gebruik binnen [...] met de u-vlag. */
    public const WS = '\t\n\x{0B}\f\r \x{A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}';

    /** Lengte zoals `.length` in JavaScript (UTF-16): een emoji telt als twee tekens. */
    public static function length(string $value): int
    {
        return mb_strlen($value, 'UTF-8') + (int) preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $value);
    }

    /** Zoals `.trim()` in JavaScript, dus ook harde spaties en dergelijke (maar geen \0 zoals PHP's trim). */
    public static function trim(string $value): string
    {
        return (string) preg_replace('/^['.self::WS.']+|['.self::WS.']+$/uD', '', $value);
    }

    /** Ongeldige UTF-8 (kan in een formulier zitten) wordt het vervangingsteken, zoals een browser dat doet. */
    public static function utf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return htmlspecialchars_decode(htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_NOQUOTES);
    }

    /** Zoals `Number(tekst)` in JavaScript: lege tekst is 0, iets wat geen getal is NAN. */
    public static function toNumber(string $value): float
    {
        $value = self::trim($value);
        if ($value === '') {
            return 0.0;
        }
        if (preg_match('/^[+-]?(?:[0-9]+\.?[0-9]*|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', $value)) {
            return (float) $value;
        }
        if (preg_match('/^([+-]?)Infinity$/D', $value, $m)) {
            return $m[1] === '-' ? -INF : INF;
        }
        if (preg_match('/^0([xX][0-9a-fA-F]+|[oO][0-7]+|[bB][01]+)$/D', $value, $m)) {
            $digits = substr($m[1], 1);

            return (float) match (strtolower($m[1][0])) {
                'x' => hexdec($digits),
                'o' => octdec($digits),
                default => bindec($digits),
            };
        }

        return NAN;
    }

    /** Zoals `String(getal)` in JavaScript: 79, 59.5, 1e+21. */
    public static function numberToString(int|float $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }
        if ($value == 0.0) {
            return '0';
        }

        // Kortste reeks cijfers die precies dit getal oplevert (zoals JavaScript), daarna de notatie van JavaScript.
        $abs = abs($value);
        for ($precision = 0; $precision < 17; $precision++) {
            $exp = sprintf('%.'.$precision.'e', $abs);
            if ((float) $exp === $abs) {
                break;
            }
        }
        [$mantissa, $exponent] = explode('e', $exp);
        $digits = rtrim(str_replace('.', '', $mantissa), '0');
        $k = strlen($digits);
        $n = (int) $exponent + 1;

        if ($k <= $n && $n <= 21) {
            $out = $digits.str_repeat('0', $n - $k);
        } elseif ($n > 0 && $n <= 21) {
            $out = substr($digits, 0, $n).'.'.substr($digits, $n);
        } elseif ($n > -6 && $n <= 0) {
            $out = '0.'.str_repeat('0', -$n).$digits;
        } else {
            $e = $n - 1;
            $out = ($k === 1 ? $digits : $digits[0].'.'.substr($digits, 1)).'e'.($e < 0 ? '-' : '+').abs($e);
        }

        return ($value < 0 ? '-' : '').$out;
    }

    /** Zoals `getal.toFixed(n)` in JavaScript: precies op de helft rondt af naar boven (0,125 wordt 0,13). */
    public static function toFixed(float $value, int $digits): string
    {
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value) || abs($value) >= 1e21) {
            return self::numberToString($value);
        }

        // Ruim voldoende cijfers om te zien of het getal boven, onder of precies op de helft ligt.
        $exact = sprintf('%.'.($digits + 30).'f', abs($value));
        [$int, $fraction] = explode('.', $exact);
        $number = $int.substr($fraction, 0, $digits);
        if ($fraction[$digits] >= '5') {
            $i = strlen($number) - 1;
            while ($i >= 0 && $number[$i] === '9') {
                $number[$i] = '0';
                $i--;
            }
            $number = $i < 0 ? '1'.$number : substr_replace($number, (string) ((int) $number[$i] + 1), $i, 1);
        }
        $out = $digits > 0 ? substr($number, 0, -$digits).'.'.substr($number, -$digits) : $number;

        return ($value < 0 ? '-' : '').$out;
    }
}
