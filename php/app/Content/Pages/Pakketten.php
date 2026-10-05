<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Defaults;
use App\Content\Fields as F;

/**
 * Teksten van 'Prijzen en pakketten' in het beheer (overgenomen uit src/lib/content/pages/pakketten.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Pakketten
{
    public const SLUG = 'pakketten';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'pakketten',
            'title' => 'Prijzen en pakketten',
            'path' => '/tarieven',
            'description' => 'Online coaching, personal training en de ademsessie: namen, prijzen en wat erbij hoort.',
            'sections' => [
                'online' => F::section(
                    'Online coaching',
                    [
                        'plans' => F::items(
                            'Pakketten',
                            'Pakket',
                            [
                                'name' => F::line('Naam', '', ['max' => 30]),
                                'tagline' => F::line('Korte omschrijving', '', ['max' => 80]),
                                'price' => F::price('Prijs per maand', ''),
                                'features' => F::list('Wat zit erin', [], ['hint' => 'Eén punt per regel.']),
                                'featured' => F::check("Markeren als 'Meest gekozen'", false),
                            ],
                            array_map(fn (array $p) => ['name' => $p['name'], 'tagline' => $p['tagline'], 'price' => $p['price'], 'features' => $p['features'], 'featured' => $p['featured']], Defaults::ONLINE_PLANS),
                            ['fixed' => true],
                        ),
                    ],
                    "Op de pagina's Online coaching en Tarieven, bij het aanmaken van een account en in Mijn omgeving. De laagste prijs is automatische waarde {online-vanaf}.",
                ),
                'pt' => F::section(
                    'Personal training',
                    [
                        'cards' => F::items(
                            'Pakketten',
                            'Pakket',
                            [
                                'label' => F::line('Kleine kop', '', ['max' => 40]),
                                'name' => F::line('Naam', '', ['max' => 40]),
                                'price' => F::price('Prijs', ''),
                                'unit' => F::line('Na de prijs', '', ['max' => 40, 'optional' => true, 'hint' => "Bijvoorbeeld 'per uur'. Leeg laten mag."]),
                                'features' => F::list('Wat zit erin', [], ['hint' => 'Eén punt per regel.']),
                                'note' => F::line('Kleine tekst onderaan', '', ['max' => 120, 'optional' => true]),
                                'featured' => F::check("Markeren als 'Meest gekozen'", false),
                            ],
                            array_map(fn (array $c) => ['label' => $c['label'], 'name' => $c['name'], 'price' => $c['price'], 'unit' => $c['unit'] ?? '', 'features' => $c['features'], 'note' => $c['note'] ?? '', 'featured' => $c['featured']], Defaults::PT_PRICES),
                            ['min' => 1, 'max' => 6],
                        ),
                    ],
                    "Op de pagina's Personal training en Tarieven.",
                ),
                'adem' => F::section(
                    'Ademsessie 1-op-1',
                    [
                        'name' => F::line('Naam', 'Ademsessie 1-op-1', ['max' => 40]),
                        'label' => F::line('Kleine kop', 'Ademcoaching', ['max' => 40]),
                        'price' => F::price('Prijs per sessie', Defaults::BREATHWORK_SESSION['price'], ['noVars' => true, 'hint' => 'Automatische waarde {ademprijs}.']),
                        'duration' => F::line('Duur', Defaults::BREATHWORK_SESSION['duration'], ['max' => 30, 'noVars' => true, 'hint' => "Automatische waarde {ademduur}, bijvoorbeeld '1,5 uur'."]),
                        'unit' => F::line('Na de prijs (tarievenpagina)', 'per sessie van {ademduur}', ['max' => 60]),
                        'features' => F::list('Wat zit erin', Defaults::BREATHWORK_SESSION['features'], ['hint' => 'Eén punt per regel.']),
                    ],
                    "Op de pagina's Ademcoaching en Tarieven.",
                ),
                'labels' => F::section('Overige', [
                    'featured' => F::line('Label bij een gemarkeerd pakket', 'Meest gekozen', ['max' => 30]),
                ]),
            ],
        ];
    }
}
