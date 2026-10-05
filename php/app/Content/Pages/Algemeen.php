<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Defaults;
use App\Content\Fields as F;

/**
 * Teksten van 'Op elke pagina' in het beheer (overgenomen uit src/lib/content/pages/algemeen.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Algemeen
{
    public const SLUG = 'algemeen';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'algemeen',
            'title' => 'Op elke pagina',
            'path' => null,
            'description' => 'Balk bovenaan, footer, vriendenactie, reviews, werkwijze, expertises en locatie.',
            'sections' => [
                'aankondiging' => F::section(
                    'Balk bovenaan',
                    [
                        'show' => F::check('Balk tonen', true),
                        'label' => F::line('Label', 'Nieuw', ['max' => 20, 'optional' => true, 'hint' => 'Het kleine gekleurde woord ervoor. Leeg laten mag.']),
                        'text' => F::line('Tekst', 'Online coaching: nodig een vriend uit. {actie}.', ['max' => 120]),
                        'href' => F::link('Linkt naar', '/vriend-uitnodigen'),
                    ],
                    'De zwarte balk boven het menu, op elke pagina.',
                ),
                'vriendenactie' => F::section(
                    'Vriendenactie',
                    [
                        'headline' => F::line('Naam van de actie', Defaults::REFERRAL['headline'], ['max' => 60, 'noVars' => true, 'hint' => 'Automatische waarde {actie}.']),
                        'friendReward' => F::line('Wat de vriend krijgt', Defaults::REFERRAL['friendReward'], ['max' => 120, 'noVars' => true, 'hint' => 'Automatische waarde {vriendkorting}.']),
                        'referrerReward' => F::line('Wat de uitnodiger krijgt', Defaults::REFERRAL['referrerReward'], ['max' => 120, 'noVars' => true, 'hint' => 'Automatische waarde {jouwkorting}.']),
                        'steps' => F::items(
                            'Stappen',
                            'Stap',
                            ['title' => F::line('Titel', '', ['max' => 80]), 'text' => F::text('Tekst', '', ['max' => 300])],
                            [
                                ['title' => 'Deel je persoonlijke link', 'text' => 'Je vindt hem in Mijn omgeving en deelt hem met één tik via WhatsApp of e-mail.'],
                                ['title' => 'Je vriend meldt zich aan', 'text' => 'Via jouw link krijgt je vriend {vriendkorting}.'],
                                ['title' => 'Je vriend start met online coaching', 'text' => 'Jij krijgt dan {jouwkorting}. Steyn verrekent het met je volgende factuur.'],
                            ],
                            ['fixed' => true],
                        ),
                    ],
                    'Deze teksten komen terug op de homepage, bij online coaching, de vriendenactie en in Mijn omgeving.',
                ),
                'afsluiter' => F::section(
                    'Standaard afsluiter',
                    [
                        'title' => F::line('Titel', 'Klaar om te starten?', ['max' => 80]),
                        'text' => F::text('Tekst', 'Maak gratis een account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.', ['max' => 300]),
                        'primary' => F::line('Witte knop', 'Start online coaching', ['max' => 40]),
                        'secondary' => F::line('Tweede knop', 'Gratis kennismaking', ['max' => 40]),
                    ],
                    "Het zwarte blok onderaan de homepage en de tarieven. Andere pagina's hebben een eigen afsluiter.",
                ),
                'reviews' => F::section(
                    'Reviews',
                    [
                        'reviews' => F::items(
                            'Reviews',
                            'Review',
                            ['quote' => F::text('Wat zegt de sporter?', '', ['max' => 400]), 'name' => F::line('Naam', '', ['max' => 60]), 'role' => F::line('Beroep of omschrijving', '', ['max' => 60, 'optional' => true])],
                            [
                                [
                                    'quote' => 'Steyn heeft mij geleerd dat het niet alleen gaat om afvallen, maar om bewustwording van je leefstijl.',
                                    'name' => "Miranda 'd Weegman",
                                    'role' => 'Operatieassistente',
                                ],
                                [
                                    'quote' => 'Ik ben al jaren bezig met afvallen maar bleef altijd rond hetzelfde gewicht. Sinds ik met Steyn train behaal ik eindelijk resultaat.',
                                    'name' => 'Steve van Maanen',
                                    'role' => 'Bakker',
                                ],
                            ],
                            ['min' => 1, 'max' => 8],
                        ),
                    ],
                    'Op de homepage, bij personal training en de tarieven.',
                ),
                'werkwijze' => F::section(
                    'Werkwijze',
                    [
                        'steps' => F::items(
                            'Stappen',
                            'Stap',
                            ['title' => F::line('Titel', '', ['max' => 60]), 'text' => F::text('Tekst', '', ['max' => 300])],
                            [
                                ['title' => 'Intake', 'text' => 'We beginnen met een gesprek over jouw doelen, je achtergrond en wat je tot nu toe hebt geprobeerd.'],
                                ['title' => 'Nulmeting', 'text' => 'We wegen, meten en bewegen: zo zien we precies waar we aan moeten werken.'],
                                ['title' => 'Plan op maat', 'text' => 'Je krijgt een persoonlijk schema en voedingsplan met de ideale balans in macro- en micronutriënten.'],
                                ['title' => 'Coaching', 'text' => 'Ook buiten de trainingen hebben we contact. We sturen bij tot je doel bereikt is, en daarna.'],
                            ],
                            ['min' => 2, 'max' => 6],
                        ),
                    ],
                    'De genummerde stappen op de homepage en bij Over Steyn.',
                ),
                'expertises' => F::section(
                    'Expertises',
                    [
                        'list' => F::list(
                            'Expertises',
                            ['Personal trainer', 'Orthomoleculair voedingstherapeut', 'Leefstijl- en vitaliteitscoaching', 'Ademcoaching', 'Powerliften', 'Boksen', 'CrossFit', 'Sportspecifieke training'],
                            ['hint' => 'Eén per regel. Op de homepage en bij Over Steyn.'],
                        ),
                    ],
                ),
                'locatie' => F::section(
                    'Locatie en contact',
                    [
                        'name' => F::line('Naam van de locatie', Defaults::LOCATIONS[0]['name'], ['max' => 60]),
                        'street' => F::line('Straat en huisnummer', Defaults::LOCATIONS[0]['street'], ['max' => 80, 'hint' => 'De routeknop naar Google Maps gebruikt dit adres.']),
                        'city' => F::line('Postcode en plaats', Defaults::LOCATIONS[0]['city'], ['max' => 80]),
                        'area' => F::line('Buurt', 'Amsterdam Oud-West', ['max' => 60, 'hint' => 'Onder het adres, en voor Google.']),
                        'directions' => F::text('Bereikbaarheid', 'Op drie minuten lopen van het Vondelpark. Tram 1 stopt om de hoek, bij de halte Rhijnvis Feithstraat.', [
                            'max' => 200,
                            'hint' => 'Op de homepage en de contactpagina.',
                        ]),
                        'phone' => F::line('Telefoonnummer', '', ['max' => 30, 'optional' => true, 'hint' => 'Leeg laten mag. Op de contactpagina, in de footer en voor Google; gebruik overal precies hetzelfde als in je Google Bedrijfsprofiel.']),
                        'email' => F::line('E-mailadres', '', ['max' => 120, 'optional' => true, 'hint' => 'Leeg laten mag. Op de contactpagina, in de footer en voor Google; gebruik overal precies hetzelfde als in je Google Bedrijfsprofiel.']),
                        'onLocationTitle' => F::line("Kop 'op locatie'", 'Op locatie', ['max' => 40]),
                        'onLocation' => F::text(
                            "Tekst 'op locatie'",
                            'Naast onze vaste locatie Gymbase komen we ook op locatie: in jouw favoriete park of in de kantine van je werk.',
                            ['max' => 300],
                        ),
                        'onlineTitle' => F::line("Kop 'online'", 'Online', ['max' => 40]),
                        'online' => F::text("Tekst 'online'", 'Met online coaching train je waar en wanneer jij wilt, met Steyn altijd binnen handbereik.', ['max' => 300]),
                        'responseTime' => F::line('Reactietijd', 'We streven ernaar om binnen 24 uur contact met je op te nemen.', ['max' => 120, 'hint' => 'Op de contactpagina.']),
                        'instagramHandle' => F::line('Instagram-naam', Defaults::SITE['instagram']['handle'], ['max' => 40]),
                        'instagramUrl' => F::url('Instagram-link', Defaults::SITE['instagram']['url']),
                    ],
                    'Op de homepage, de contactpagina en in de footer.',
                ),
                'footer' => F::section(
                    'Footer',
                    [
                        'intro' => F::text('Tekst onder het logo', 'Personal training, voedingscoaching en ademcoaching in Amsterdam Oud-West. Online coaching waar je ook bent.', ['max' => 200]),
                        'extra' => F::line('Regel onder de locatie', 'Op locatie & online', ['max' => 60, 'optional' => true]),
                        'copyright' => F::line('Onderste regel', 'SteynPT · Personal training Amsterdam', ['max' => 80, 'hint' => 'Het © en het jaartal komen er automatisch voor.']),
                    ],
                    'Het zwarte blok helemaal onderaan elke pagina.',
                ),
            ],
        ];
    }
}
