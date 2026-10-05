<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Kleine opmaak in teksten uit het beheer: {codes} voor automatische waarden en *sterretjes* voor de accentkleur.
 * Puur: de site gebruikt dit bij het tonen, het beheer voor de voorvertoning en de controle bij opslaan.
 */
final class Markup
{
    private const PLACEHOLDER = '/\{([a-z][a-z0-9-]*)\}/';

    /**
     * Alle {codes} in een tekst, zonder dubbele.
     *
     * @return list<string>
     */
    public static function placeholdersIn(string $value): array
    {
        preg_match_all(self::PLACEHOLDER, $value, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Vervangt bekende {codes}; onbekende blijven staan (die houdt het opslaan al tegen).
     *
     * @param  array<string, string>  $vars
     */
    public static function fillText(string $value, array $vars): string
    {
        return (string) preg_replace_callback(
            self::PLACEHOLDER,
            fn (array $m) => array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0],
            $value,
        );
    }

    /**
     * Vult {codes} in alle teksten van een waarde: tekst, opsomming of lijst met items (ook een hele pagina).
     *
     * @param  array<string, string>  $vars
     */
    public static function fillVars(mixed $value, array $vars): mixed
    {
        if (is_string($value)) {
            return self::fillText($value, $vars);
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (is_array($value)) {
            return array_map(fn ($v) => self::fillVars($v, $vars), $value);
        }

        return $value;
    }

    /**
     * Deelt een titel op in gewone tekst, *accent* en regeleinden. Een los sterretje blijft gewoon staan.
     *
     * @return list<array{text: string, accent: bool}|array{br: true}>
     */
    public static function parseRich(string $value): array
    {
        $parts = [];
        foreach (explode("\n", $value) as $i => $line) {
            if ($i > 0) {
                $parts[] = ['br' => true];
            }
            $pieces = explode('*', $line);
            // Bij een oneven aantal sterretjes is het laatste niet gesloten: dat is gewoon een teken.
            $count = count($pieces);
            if ($count % 2 === 0) {
                array_splice($pieces, $count - 2, 2, [$pieces[$count - 2].'*'.$pieces[$count - 1]]);
            }
            foreach ($pieces as $j => $text) {
                if ($text !== '') {
                    $parts[] = ['text' => $text, 'accent' => $j % 2 === 1];
                }
            }
        }

        return $parts;
    }

    /** Titel zonder opmaaktekens, voor plekken zonder opmaak (zoals de omschrijving in Google). */
    public static function plainRich(string $value): string
    {
        $text = implode('', array_map(fn (array $p) => isset($p['br']) ? ' ' : $p['text'], self::parseRich(Js::utf8($value))));

        return Js::trim((string) preg_replace('/['.Js::WS.']+/u', ' ', $text));
    }

    /**
     * Alinea's: gescheiden door een lege regel.
     *
     * @return list<string>
     */
    public static function paragraphs(string $value): array
    {
        $parts = preg_split('/\n['.Js::WS.']*\n/u', Js::utf8($value)) ?: [];

        return array_values(array_filter(array_map(Js::trim(...), $parts), fn (string $p) => $p !== ''));
    }
}
