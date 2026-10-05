<?php

declare(strict_types=1);

namespace App\Content;

use JsonException;
use LogicException;

/**
 * Controle van teksten uit het beheer (bij opslaan) en van opgeslagen teksten (bij tonen). Puur en getest.
 */
final class Values
{
    public const LINE_MAX = 160;

    public const TEXT_MAX = 1200;

    public const LIST_ITEM_MAX = 300;

    /** Pagina's waar de balk bovenaan naartoe kan linken. */
    public const SITE_LINKS = [
        ['href' => '/', 'label' => 'Homepage'],
        ['href' => '/online-coaching', 'label' => 'Online coaching'],
        ['href' => '/personal-training', 'label' => 'Personal training'],
        ['href' => '/ademcoaching', 'label' => 'Ademcoaching'],
        ['href' => '/voedingscoaching', 'label' => 'Voedingscoaching'],
        ['href' => '/tarieven', 'label' => 'Tarieven'],
        ['href' => '/over-steyn', 'label' => 'Over Steyn'],
        ['href' => '/vriend-uitnodigen', 'label' => 'Vriendenactie'],
        ['href' => '/contact', 'label' => 'Contact'],
        ['href' => '/registreren', 'label' => 'Account aanmaken'],
        ['href' => '/account/agenda', 'label' => 'Afspraak maken'],
    ];

    private const PRICE = '/^([0-9]{1,6}|[0-9]{1,3}(\.[0-9]{3})+)(,[0-9]{2})?$/D';

    /** Bedrag als getal, voor bijvoorbeeld "online coaching vanaf" (NAN als het geen getal is). */
    public static function priceNumber(string $value): float
    {
        return Js::toNumber((string) preg_replace('/,/', '.', str_replace('.', '', $value), 1));
    }

    // Opschonen: Windows-regeleinden, spaties aan het eind van een regel en meer dan één lege regel achter elkaar.
    private static function clean(string $value): string
    {
        $value = (string) preg_replace('/\r\n?/', "\n", $value);
        $value = implode("\n", array_map(fn (string $l) => rtrim($l, " \t"), explode("\n", $value)));

        return Js::trim((string) preg_replace('/\n{3,}/', "\n\n", $value));
    }

    private static function asString(mixed $value): string
    {
        return match (true) {
            is_string($value) => Js::utf8($value),
            is_int($value), is_float($value) => Js::numberToString($value),
            default => '',
        };
    }

    /** Een object of array als array met sleutels; null voor alle andere waarden. */
    private static function asObject(mixed $value): ?array
    {
        return match (true) {
            is_array($value) => $value,
            is_object($value) => get_object_vars($value),
            default => null,
        };
    }

    private static function isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  list<string>  $vars
     */
    private static function checkVars(array $field, string $value, string $path, array &$errors, array $vars): bool
    {
        $used = Markup::placeholdersIn($value);
        if (! $used) {
            return true;
        }
        if (! empty($field['noVars'])) {
            $errors[$path] = 'Hier kun je geen automatische waarden ({…}) gebruiken.';

            return false;
        }
        $unknown = array_values(array_filter($used, fn (string $v) => ! in_array($v, $vars, true)));
        if ($unknown) {
            $codes = fn (array $keys) => implode(', ', array_map(fn ($k) => '{'.$k.'}', $keys));
            $errors[$path] = 'Onbekende automatische waarde '.$codes($unknown).'. Kies uit: '.$codes($vars).'.';

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $errors
     * @param  list<string>  $vars
     */
    private static function normalizeSub(array $field, mixed $input, string $path, array &$errors, array $vars): mixed
    {
        $kind = $field['kind'];

        if ($kind === 'check') {
            return $input === true || $input === 'true' || $input === 'on';
        }

        if ($kind === 'list') {
            // Een array uit PHP telt altijd als lijst, ook met andere sleutels (zoals een formulier met verwijderde regels).
            $raw = is_array($input) ? array_map(self::asString(...), array_values($input)) : explode("\n", self::asString($input));
            $values = array_values(array_filter(
                array_map(fn (string $v) => (string) preg_replace('/\n+/', ' ', self::clean($v)), $raw),
                fn (string $v) => $v !== '',
            ));
            $min = $field['min'] ?? (! empty($field['optional']) ? 0 : 1);
            $max = $field['max'] ?? 20;
            if (count($values) < $min) {
                $errors[$path] = $min === 1 ? 'Vul minstens één punt in.' : "Vul minstens {$min} punten in.";
            } elseif (count($values) > $max) {
                $errors[$path] = "Maximaal {$max} punten.";
            } elseif (array_filter($values, fn (string $v) => Js::length($v) > self::LIST_ITEM_MAX)) {
                $errors[$path] = 'Een punt mag maximaal '.self::LIST_ITEM_MAX.' tekens hebben.';
            } else {
                foreach ($values as $v) {
                    if (! self::checkVars($field, $v, $path, $errors, $vars)) {
                        break;
                    }
                }
            }

            return $values;
        }

        if ($kind === 'items') {
            throw new LogicException('Geneste lijsten worden niet ondersteund');
        }

        $value = self::clean(self::asString($input));
        // Een gewone regel heeft geen regeleinden; een titel mag er een paar hebben.
        if ($kind === 'line') {
            $value = ! empty($field['rich'])
                ? (string) preg_replace('/\n+/', "\n", $value)
                : (string) preg_replace('/['.Js::WS.']*\n['.Js::WS.']*/u', ' ', $value);
        }
        // Tekst zonder alinea's staat op de site als één alinea: een regeleinde wordt daar een spatie.
        if ($kind === 'text' && empty($field['paragraphs'])) {
            $value = (string) preg_replace('/['.Js::WS.']*\n['.Js::WS.']*/u', ' ', $value);
        }
        if ($kind !== 'text' && $kind !== 'line') {
            $value = (string) preg_replace('/['.Js::WS.']+/u', '', $value);
        }
        if ($value === '') {
            if (empty($field['optional'])) {
                $errors[$path] = 'Dit veld mag niet leeg zijn.';
            }

            return $value;
        }
        if ($kind === 'price') {
            if (! preg_match(self::PRICE, $value)) {
                $errors[$path] = 'Vul een bedrag in zoals 120 of 79,50, zonder €-teken.';
            }

            return $value;
        }
        if ($kind === 'link') {
            if (! in_array($value, array_column(self::SITE_LINKS, 'href'), true)) {
                $errors[$path] = 'Kies een pagina uit de lijst.';
            }

            return $value;
        }
        if ($kind === 'url') {
            $chars = '[^'.Js::WS.'<>"]+';
            if (! preg_match('/^https:\/\/'.$chars.'\.'.$chars.'$/uD', $value) || Js::length($value) > 300) {
                $errors[$path] = 'Vul een volledig webadres in dat begint met https://';
            }

            return $value;
        }
        $max = $field['max'] ?? ($kind === 'text' ? self::TEXT_MAX : self::LINE_MAX);
        $length = Js::length($value);
        if ($length > $max) {
            $errors[$path] = "Maximaal {$max} tekens (nu {$length}).";
        } elseif ($kind === 'line' && ! empty($field['rich']) && count(explode("\n", $value)) > 3) {
            $errors[$path] = 'Maximaal 3 regels.';
        } else {
            self::checkVars($field, $value, $path, $errors, $vars);
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $errors
     * @param  list<string>  $vars
     */
    private static function normalizeField(array $field, mixed $input, string $path, array &$errors, array $vars): mixed
    {
        if ($field['kind'] !== 'items') {
            return self::normalizeSub($field, $input, $path, $errors, $vars);
        }
        $rows = is_array($input) ? array_values($input) : [];
        $fixed = ! empty($field['fixed']) ? count($field['default']) : null;
        $min = $fixed ?? $field['min'] ?? 1;
        $max = $fixed ?? $field['max'] ?? 20;
        $values = [];
        foreach ($rows as $i => $row) {
            $obj = self::asObject($row) ?? [];
            $item = [];
            foreach ($field['fields'] as $k => $sub) {
                $item[$k] = self::normalizeSub($sub, $obj[$k] ?? null, "{$path}.{$i}.{$k}", $errors, $vars);
            }
            $values[] = $item;
        }
        $name = mb_strtolower($field['itemLabel'], 'UTF-8');
        if ($fixed !== null && count($values) !== $fixed) {
            $errors[$path] = "Dit onderdeel heeft altijd {$fixed} items.";
        } elseif (count($values) < $min) {
            $errors[$path] = 'Voeg minstens '.($min === 1 ? "één {$name}" : "{$min} items").' toe.';
        } elseif (count($values) > $max) {
            $errors[$path] = "Maximaal {$max} items.";
        }

        return $values;
    }

    /**
     * Controleert en schoont wat het beheer instuurt. Alleen velden die in de pagina bestaan tellen mee;
     * een veld dat niet is meegestuurd, blijft ongewijzigd en staat dus ook niet in 'values'.
     * Fouten staan onder "onderdeel.veld" of, bij een item, "onderdeel.veld.nummer.subveld" (vanaf 0).
     *
     * @param  list<string>  $varKeys  bekende automatische waarden (Vars::VAR_KEYS)
     * @return array{values: array<string, array<string, mixed>>, errors: array<string, string>}
     */
    public static function normalizePage(array $page, mixed $input, array $varKeys): array
    {
        $errors = [];
        $values = [];
        $data = self::asObject($input) ?? [];
        foreach ($page['sections'] as $s => $section) {
            $given = self::asObject($data[$s] ?? null);
            if ($given === null) {
                continue;
            }
            foreach ($section['fields'] as $f => $field) {
                if (! array_key_exists($f, $given)) {
                    continue;
                }
                $values[$s][$f] = self::normalizeField($field, $given[$f], "{$s}.{$f}", $errors, $varKeys);
            }
        }

        return ['values' => $values, 'errors' => $errors];
    }

    // --- Opgeslagen waarden lezen ---

    private static function coerceSub(array $field, mixed $raw, mixed $fallback): mixed
    {
        return match ($field['kind']) {
            'check' => is_bool($raw) ? $raw : $fallback,
            'list' => self::isList($raw) && count(array_filter($raw, is_string(...))) === count($raw) ? $raw : $fallback,
            'items' => $fallback,
            default => is_string($raw) ? $raw : $fallback,
        };
    }

    private static function emptyOf(array $field): mixed
    {
        return match ($field['kind']) {
            'list' => [],
            'check' => false,
            default => '',
        };
    }

    /**
     * Een opgeslagen waarde die niet (meer) past bij het veld, bijvoorbeeld na een wijziging in de code,
     * wordt de standaardtekst. Zo kan een oude of beschadigde waarde de site nooit breken.
     * $raw is de gelezen JSON; objecten mogen een array of een object (stdClass) zijn.
     */
    public static function coerceStored(array $field, mixed $raw): mixed
    {
        if ($field['kind'] !== 'items') {
            return self::coerceSub($field, $raw, $field['default']);
        }
        if (! self::isList($raw)) {
            return $field['default'];
        }
        if (! empty($field['fixed']) && count($raw) !== count($field['default'])) {
            return $field['default'];
        }
        $out = [];
        foreach ($raw as $i => $row) {
            $obj = self::asObject($row) ?? [];
            $base = $field['default'][$i] ?? [];
            $item = [];
            foreach ($field['fields'] as $k => $sub) {
                $item[$k] = self::coerceSub($sub, $obj[$k] ?? null, $base[$k] ?? self::emptyOf($sub));
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * Teksten van een pagina zonder {codes} in te vullen: zoals ze in het beheer staan.
     * $stored bevat de opgeslagen teksten als JSON, op sleutel (Fields::fieldKey()). Ontbreekt een tekst,
     * is hij onleesbaar of past hij niet bij het veld, dan geldt de standaardtekst.
     *
     * @param  array<string, string>  $stored
     * @return array<string, array<string, mixed>>
     */
    public static function resolvePage(array $page, array $stored): array
    {
        $out = [];
        foreach ($page['sections'] as $s => $section) {
            $out[$s] = [];
            foreach ($section['fields'] as $f => $field) {
                $raw = $stored[Fields::fieldKey($page['slug'], $s, $f)] ?? null;
                $value = $field['default'];
                if (is_string($raw)) {
                    try {
                        // Objecten als stdClass, zodat {} en [] net als in JSON verschillend blijven.
                        $value = self::coerceStored($field, json_decode($raw, false, 512, JSON_THROW_ON_ERROR));
                    } catch (JsonException) {
                        // Onleesbare waarde: standaardtekst.
                    }
                }
                $out[$s][$f] = $value;
            }
        }

        return $out;
    }

    // JSON met vaste volgorde van sleutels, zodat dezelfde inhoud altijd dezelfde tekst oplevert.
    private static function stable(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
            ksort($value, SORT_STRING);

            return (object) array_map(self::stable(...), $value);
        }
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value, SORT_STRING);
            }

            return array_map(self::stable(...), $value);
        }

        return $value;
    }

    /** Zelfde inhoud? Gebruikt om te bepalen of een tekst nog de standaardtekst is. */
    public static function sameValue(mixed $a, mixed $b): bool
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

        return json_encode(self::stable($a), $flags) === json_encode(self::stable($b), $flags);
    }
}
