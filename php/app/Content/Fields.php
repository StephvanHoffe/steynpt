<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Bouwstenen voor het tekstbeheer: welke soorten velden er zijn en hoe een pagina is opgebouwd.
 * Puur (geen database), zodat zowel de site, het beheer als de tests dit kunnen gebruiken.
 *
 * Een veld is een gewone array, bijvoorbeeld ['kind' => 'line', 'label' => 'Titel', 'default' => '…', 'max' => 80].
 * Soorten ('kind'):
 * - line:  één regel tekst. 'rich' => true: *sterretjes* geven de accentkleur en Enter een nieuwe regel (voor titels).
 * - text:  tekst over meerdere regels. 'paragraphs' => true: een lege regel begint een nieuwe alinea
 *          (anders wordt Enter een spatie).
 * - list:  opsomming, één punt per regel. De standaard is een lijst met teksten; ook 'min' en 'max'.
 * - price: bedrag in hele euro's of met komma, zonder €-teken.
 * - check: aan of uit (standaard true of false).
 * - link:  link naar een pagina van de site (zie Values::SITE_LINKS).
 * - url:   volledig webadres (https://…).
 * - items: lijst met items die elk dezelfde velden hebben: 'itemLabel' (naam van één item, zoals "Vraag"),
 *          'fields' (alleen line, text, list, price of check), 'default' (lijst met items),
 *          'fixed' (vast aantal: niet toevoegen of verwijderen), 'min' en 'max'.
 * Voor elk veld: 'hint' (uitleg onder het veld in het beheer), 'optional' (leeg laten mag, anders verplicht) en
 * 'noVars' (dit veld is zelf een automatische waarde en mag daarom geen {codes} bevatten).
 *
 * Een pagina: ['slug' => …, 'title' => …, 'path' => adres op de site of null voor teksten die op meerdere
 * pagina's staan, 'description' => …, 'sections' => [sleutel => ['title' => …, 'hint' => tekst of null,
 * 'fields' => [sleutel => veld]]]]. De volgorde van onderdelen en velden is de volgorde in het beheer.
 *
 * Opties worden achter 'kind', 'label' en 'default' gezet, in de volgorde waarin ze zijn meegegeven.
 */
final class Fields
{
    /** Eén regel tekst. */
    public static function line(string $label, string $value, array $opts = []): array
    {
        return ['kind' => 'line', 'label' => $label, 'default' => $value, ...$opts];
    }

    /** Titel: een regel met *accent* en hooguit drie regels. */
    public static function title(string $label, string $value, array $opts = []): array
    {
        return self::line($label, $value, ['rich' => true, ...$opts]);
    }

    /** Tekst over meerdere regels; met 'paragraphs' => true in alinea's. */
    public static function text(string $label, string $value, array $opts = []): array
    {
        return ['kind' => 'text', 'label' => $label, 'default' => $value, ...$opts];
    }

    /** Opsomming: één punt per regel. */
    public static function list(string $label, array $value, array $opts = []): array
    {
        return ['kind' => 'list', 'label' => $label, 'default' => $value, ...$opts];
    }

    /** Bedrag in hele euro's of met komma, zonder €-teken. */
    public static function price(string $label, string $value, array $opts = []): array
    {
        return ['kind' => 'price', 'label' => $label, 'default' => $value, ...$opts];
    }

    public static function check(string $label, bool $value, array $opts = []): array
    {
        return ['kind' => 'check', 'label' => $label, 'default' => $value, ...$opts];
    }

    /** Link naar een pagina van de site. */
    public static function link(string $label, string $value, array $opts = []): array
    {
        return ['kind' => 'link', 'label' => $label, 'default' => $value, ...$opts];
    }

    /** Volledig webadres (https://…). */
    public static function url(string $label, string $value, array $opts = []): array
    {
        return ['kind' => 'url', 'label' => $label, 'default' => $value, ...$opts];
    }

    /**
     * Lijst met items, zoals vragen of reviews.
     *
     * @param  array<string, array>  $fields  velden van één item
     * @param  list<array<string, mixed>>  $value  standaarditems
     * @param  array  $opts  'fixed', 'min', 'max', 'hint', …
     */
    public static function items(string $label, string $itemLabel, array $fields, array $value, array $opts = []): array
    {
        return ['kind' => 'items', 'label' => $label, 'itemLabel' => $itemLabel, 'fields' => $fields, 'default' => $value, ...$opts];
    }

    public static function section(string $title, array $fields, ?string $hint = null): array
    {
        return ['title' => $title, 'hint' => $hint, 'fields' => $fields];
    }

    // Vaste blokken die op de meeste pagina's terugkomen.

    public static function seoSection(string $pageTitle, string $description): array
    {
        return self::section(
            'Zoekmachines',
            [
                'title' => self::line('Paginatitel', $pageTitle, ['max' => 90, 'hint' => 'Staat in het tabblad van de browser en als kop in Google. Houd hem kort.']),
                'description' => self::text('Omschrijving', $description, ['max' => 300, 'hint' => 'De korte tekst onder de titel in Google. Ongeveer 150 tekens werkt het best.']),
            ],
            'Niet zichtbaar op de pagina zelf.',
        );
    }

    /** @param  array{title: string, text: string, primary: string, secondary: string}  $values */
    public static function ctaSection(array $values): array
    {
        return self::section(
            'Afsluiter onderaan',
            [
                'title' => self::line('Titel', $values['title'], ['max' => 80]),
                'text' => self::text('Tekst', $values['text'], ['max' => 300]),
                'primary' => self::line('Witte knop', $values['primary'], ['max' => 40]),
                'secondary' => self::line('Tweede knop', $values['secondary'], ['max' => 40]),
            ],
            'Het zwarte blok onderaan de pagina.',
        );
    }

    /** Sleutel waaronder een tekst wordt opgeslagen, zoals "home.hero.title". */
    public static function fieldKey(string $page, string $section, string $field): string
    {
        return "{$page}.{$section}.{$field}";
    }

    /**
     * Standaardwaarden van een hele pagina: [onderdeel => [veld => waarde]].
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pageDefaults(array $page): array
    {
        $out = [];
        foreach ($page['sections'] as $s => $section) {
            $out[$s] = [];
            foreach ($section['fields'] as $f => $field) {
                $out[$s][$f] = $field['default'];
            }
        }

        return $out;
    }
}
