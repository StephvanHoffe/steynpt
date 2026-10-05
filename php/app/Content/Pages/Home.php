<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Defaults;
use App\Content\Fields as F;

/**
 * Teksten van 'Homepage' in het beheer (overgenomen uit src/lib/content/pages/home.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Home
{
    public const SLUG = 'home';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        $card = ['title' => F::line('Titel', '', ['max' => 60]), 'text' => F::text('Tekst', '', ['max' => 300])];

        return [
            'slug' => 'home',
            'title' => 'Homepage',
            'path' => '/',
            'description' => 'De eerste pagina van de site.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Personal training · Amsterdam & online', ['max' => 80]),
                    'title' => F::title('Titel', "Sterker lichaam.\n*Gezonder* leven.", ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Ik ben Steyn van Leeuwen, personal trainer en orthomoleculair voedingscoach. Ik help je aan een gezondere leefstijl, begeleid je 1-op-1 naar je specifieke doel en coach sporters naar hun beste prestatie. Vanaf nu ook online, waar je ook bent.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Eerste knop', 'Start online coaching', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Gratis kennismaking', ['max' => 40]),
                    'stats' => F::items(
                        'Kerncijfers',
                        'Kerncijfer',
                        ['value' => F::line('Groot', '', ['max' => 12]), 'label' => F::line('Klein eronder', '', ['max' => 40])],
                        [
                            ['value' => '1-op-1', 'label' => 'persoonlijke aandacht'],
                            ['value' => 'Online', 'label' => 'overal coaching'],
                            ['value' => '24 uur', 'label' => 'en Steyn neemt contact op'],
                            ['value' => '100%', 'label' => 'inzet voor jouw doel'],
                        ],
                        ['fixed' => true],
                    ),
                    'badgeLabel' => F::line('Kaartje op de foto: kleine kop', 'Nieuw', ['max' => 20]),
                    'badgeText' => F::line('Kaartje op de foto: tekst', 'Online coaching vanaf € {online-vanaf} p/m', ['max' => 60]),
                ]),
                'diensten' => F::section('Balk met diensten', [
                    'list' => F::list('Diensten', ['Personal training', 'Online coaching', 'Ademcoaching', 'Voedingscoaching', 'Topsport', 'Leefstijl'], [
                        'max' => 8,
                        'hint' => 'Eén per regel. De smalle balk onder de foto.',
                    ]),
                ]),
                'online' => F::section('Online coaching', [
                    'eyebrow' => F::line('Kleine kop', 'Nieuw bij SteynPT', ['max' => 60]),
                    'title' => F::title('Titel', 'Online coaching. *Jouw coach*, altijd en overal.', ['max' => 100, 'hint' => 'Tussen *sterretjes* krijgt de tekst een markering.']),
                    'intro' => F::text(
                        'Introductie',
                        'Dezelfde persoonlijke aanpak als in de gym, nu in je eigen online dashboard. Je krijgt een plan op maat, checkt wekelijks in en Steyn stuurt bij. Ideaal als je zelfstandig traint, veel reist of naast je PT-sessies extra begeleiding wilt.',
                        ['max' => 500],
                    ),
                    'features' => F::items(
                        'Kenmerken',
                        'Kenmerk',
                        $card,
                        [
                            ['title' => 'Schema op maat', 'text' => 'Afgestemd op je doel, niveau en agenda.'],
                            ['title' => 'Voedingsplan', 'text' => 'De juiste balans in macro- en micronutriënten.'],
                            ['title' => 'Check-ins & metingen', 'text' => 'Je voortgang overzichtelijk in je dashboard.'],
                            ['title' => 'Direct contact', 'text' => 'Steyn stuurt bij waar nodig.'],
                        ],
                        ['fixed' => true],
                    ),
                    'primary' => F::line('Eerste knop', 'Bekijk de pakketten', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Gratis account aanmaken', ['max' => 40]),
                ]),
                'aanbod' => F::section('Aanbod', [
                    'eyebrow' => F::line('Kleine kop', 'Aanbod', ['max' => 60]),
                    'title' => F::title('Titel', 'Eén coach, alles voor jouw doel', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Met een breed scala aan opleidingen en jarenlange ervaring durft Steyn iedereen een garantie op resultaat te geven. Ben jij er klaar voor?',
                        ['max' => 400],
                    ),
                    'button' => F::line('Knop', 'Alle tarieven', ['max' => 40]),
                    'services' => F::items(
                        'Diensten',
                        'Dienst',
                        $card,
                        [
                            ['title' => 'Online coaching', 'text' => 'Schema, voedingsplan en wekelijkse check-ins in je eigen dashboard. Train waar en wanneer jij wilt.'],
                            ['title' => 'Personal training', 'text' => '1-op-1 training voor een gezondere leefstijl. Ongeacht jouw doel of sport: SteynPT gaat er 100% voor.'],
                            ['title' => 'Topsport & specifieke doelen', 'text' => 'Sportspecifieke begeleiding, periodisering en blessurepreventie voor sporters die meer willen.'],
                            [
                                'title' => 'Ademcoaching',
                                'text' => '1-op-1 of in groepsverband werken aan rust, focus, herstel en energie. Groepssessies voor teams, bedrijven en vriendengroepen op aanvraag.',
                            ],
                            ['title' => 'Voedingscoaching', 'text' => 'Bij afvallen en aankomen. Orthomoleculaire, leefstijl- en vitaliteitscoaching.'],
                        ],
                        ['fixed' => true, 'hint' => 'Vaste volgorde: online coaching, personal training, topsport, ademcoaching, voedingscoaching. Elke kaart linkt naar zijn eigen pagina.'],
                    ),
                    'onlinePoints' => F::list(
                        'Punten bij online coaching',
                        ['Trainings- en voedingsschema op maat', 'Wekelijkse check-in met feedback van Steyn', 'Afspraken en voortgang in je dashboard', 'Vanaf € {online-vanaf} per maand'],
                        ['max' => 6, 'hint' => 'Eén per regel. Staan in de grote kaart van online coaching.'],
                    ),
                    'badge' => F::line('Label op de grote kaart', 'Nieuw', ['max' => 20]),
                    'more' => F::line('Link onder elke kaart', 'Lees meer', ['max' => 30]),
                ]),
                'over' => F::section('Ontmoet Steyn', [
                    'eyebrow' => F::line('Kleine kop', 'Ontmoet Steyn', ['max' => 60]),
                    'title' => F::title('Titel', 'Hallo, ik ben Steyn van Leeuwen', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Full-time personal trainer en voedingscoach, geboren in Hoevelaken en werkzaam in Amsterdam. Door een beginnende hernia van het hockeyen ontdekte ik krachttraining. Daar vond ik mijn passie, en de motivatie om de onduidelijkheid in de fitnesswereld te doorbreken.',
                        ['max' => 600],
                    ),
                    'cardTitle' => F::line('Kaartje op de foto: groot', '15 jaar', ['max' => 20]),
                    'cardText' => F::text('Kaartje op de foto: tekst', 'Zo oud was ik toen ik op advies van de fysio begon met krachttraining.', ['max' => 160]),
                    'button' => F::line('Knop', 'Lees mijn verhaal', ['max' => 40]),
                ], "De expertises pas je aan onder 'Op elke pagina'."),
                'werkwijze' => F::section('Werkwijze', [
                    'eyebrow' => F::line('Kleine kop', 'Werkwijze', ['max' => 60]),
                    'title' => F::title('Titel', 'Van intake tot resultaat', ['max' => 100]),
                    'intro' => F::text('Introductie', 'Of je nu in de gym traint of online: iedere samenwerking volgt dezelfde bewezen aanpak.', ['max' => 400]),
                ], "De stappen zelf pas je aan onder 'Op elke pagina'."),
                'vriendenactie' => F::section('Vriendenactie', [
                    'eyebrow' => F::line('Kleine kop', 'Vriendenactie', ['max' => 60]),
                    'title' => F::title('Titel', 'Breng een vriend mee. *{actie}*', ['max' => 100]),
                    'intro' => F::text('Introductie', 'Nodig een vriend uit voor online coaching. Je vriend krijgt {vriendkorting}; jij krijgt {jouwkorting} zodra je vriend start.', ['max' => 400]),
                    'primary' => F::line('Eerste knop', 'Maak gratis account', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Zo werkt het', ['max' => 40]),
                ]),
                'reviews' => F::section('Reviews', [
                    'eyebrow' => F::line('Kleine kop', 'Reviews', ['max' => 60]),
                    'title' => F::title('Titel', 'Wat sporters zeggen', ['max' => 100]),
                ], "De reviews zelf pas je aan onder 'Op elke pagina'."),
                'locaties' => F::section('Locaties', [
                    'eyebrow' => F::line('Kleine kop', 'Bezoek ons', ['max' => 60]),
                    'title' => F::title('Titel', 'Trainen waar het jou uitkomt', ['max' => 100]),
                    'intro' => F::text('Introductie', 'Bij Gymbase in Amsterdam, op jouw favoriete plek of volledig online.', ['max' => 400]),
                ]),
                'seo' => F::seoSection('SteynPT · Personal training, online coaching & ademcoaching in Amsterdam', Defaults::SITE['description']),
            ],
        ];
    }
}
