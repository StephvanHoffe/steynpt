<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Online coaching' in het beheer (overgenomen uit src/lib/content/pages/online-coaching.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class OnlineCoaching
{
    public const SLUG = 'online-coaching';

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
            'slug' => 'online-coaching',
            'title' => 'Online coaching',
            'path' => '/online-coaching',
            'description' => 'Uitleg, stappen, pakketten en veelgestelde vragen over online coaching.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Nieuw · Online coaching', ['max' => 60]),
                    'title' => F::title('Titel', "Jouw coach.\n*Altijd* en overal.", ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'De persoonlijke aanpak van SteynPT, nu ook online. Een plan op maat, wekelijkse check-ins in je eigen dashboard en een coach die met je meedenkt, waar je ook traint.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Eerste knop', 'Kies je pakket', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Gratis account aanmaken', ['max' => 40]),
                    'note' => F::line('Kleine tekst onder de knoppen', 'Intake binnen 24 uur · Je betaalt pas na de intake', ['max' => 120, 'optional' => true]),
                ]),
                'voorWie' => F::section('Voor wie', [
                    'eyebrow' => F::line('Kleine kop', 'Voor wie', ['max' => 60]),
                    'title' => F::title('Titel', 'Gemaakt voor sporters die verder willen', ['max' => 100]),
                    'intro' => F::text('Introductie', 'Of je nu net begint of al jaren traint: online coaching geeft je structuur, kennis en een stok achter de deur.', ['max' => 400]),
                    'cards' => F::items(
                        'Kaarten',
                        'Kaart',
                        $card,
                        [
                            ['title' => 'Je traint zelfstandig', 'text' => 'Je wilt een plan dat écht bij jou past en iemand die meekijkt.'],
                            ['title' => 'Je hebt een volle agenda', 'text' => 'Train wanneer het jou uitkomt, thuis, in de gym of op kantoor.'],
                            ['title' => 'Je bent veel onderweg', 'text' => 'Je coaching reist met je mee, waar je ook bent.'],
                            ['title' => 'Je hebt een specifiek doel', 'text' => 'Wedstrijd, seizoen of persoonlijk record: we plannen ernaartoe.'],
                        ],
                        ['fixed' => true],
                    ),
                ]),
                'stappen' => F::section('Zo werkt het', [
                    'eyebrow' => F::line('Kleine kop', 'Zo werkt het', ['max' => 60]),
                    'title' => F::title('Titel', 'In vier stappen van start', ['max' => 100]),
                    'steps' => F::items(
                        'Stappen',
                        'Stap',
                        $card,
                        [
                            ['title' => 'Account aanmaken', 'text' => 'Kies je pakket en maak in twee minuten je gratis account aan.'],
                            ['title' => 'Intake', 'text' => 'Steyn neemt binnen 24 uur contact op voor een intake via videocall of in de studio.'],
                            ['title' => 'Jouw plan', 'text' => 'Je ontvangt je trainingsschema en voedingsplan, afgestemd op jouw doel en agenda.'],
                            ['title' => 'Check-in & bijsturen', 'text' => 'Elke week check je in via je dashboard. Steyn stuurt bij en houdt je metingen bij.'],
                        ],
                        ['min' => 2, 'max' => 6],
                    ),
                ]),
                'pakketten' => F::section(
                    'Pakketten',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Pakketten', ['max' => 60]),
                        'title' => F::title('Titel', 'Kies wat bij jou past', ['max' => 100]),
                        'intro' => F::text('Introductie', 'Alle pakketten inclusief persoonlijk dashboard, wekelijkse check-ins en je metingen in één overzicht.', ['max' => 400]),
                        'note' => F::line('Tekst onder de pakketten', 'Liever eerst kennismaken?', ['max' => 80]),
                        'noteLink' => F::line('Link onder de pakketten', 'Plan een gratis kennismaking', ['max' => 60]),
                    ],
                    "De pakketten zelf (namen, prijzen, inhoud) pas je aan onder 'Prijzen en pakketten'.",
                ),
                'vriendenactie' => F::section('Vriendenactie', [
                    'eyebrow' => F::line('Kleine kop', 'Vriendenactie', ['max' => 60]),
                    'title' => F::title('Titel', '{actie}', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Samen trainen is leuker en houdt je allebei scherp. Nodig een vriend uit via je persoonlijke link in Mijn omgeving.',
                        ['max' => 400],
                    ),
                    'button' => F::line('Knop', 'Voorwaarden en uitleg', ['max' => 40]),
                ]),
                'faq' => F::section('Veelgestelde vragen', [
                    'eyebrow' => F::line('Kleine kop', 'Veelgestelde vragen', ['max' => 60]),
                    'title' => F::title('Titel', 'Goed om te weten', ['max' => 100]),
                    'questions' => F::items(
                        'Vragen',
                        'Vraag',
                        ['q' => F::line('Vraag', '', ['max' => 160]), 'a' => F::text('Antwoord', '', ['max' => 800])],
                        [
                            [
                                'q' => 'Heb ik een sportschool nodig?',
                                'a' => 'Nee. Je schema wordt afgestemd op de plek waar jij traint: in de gym, thuis met beperkt materiaal of buiten.',
                            ],
                            [
                                'q' => 'Hoe snel hoor ik iets na mijn aanmelding?',
                                'a' => 'Steyn streeft ernaar om binnen 24 uur contact met je op te nemen om de intake in te plannen. Je betaalt pas als je na de intake besluit te starten.',
                            ],
                            [
                                'q' => 'Kan ik online coaching combineren met personal training?',
                                'a' => 'Zeker. Veel sporters combineren een paar 1-op-1 sessies in Amsterdam met online begeleiding voor de dagen ertussen. Bespreek het tijdens je intake.',
                            ],
                            [
                                'q' => 'Hoe lang duurt een traject?',
                                'a' => 'Dat stemmen we af op jouw doel. Voor blijvend resultaat adviseert Steyn om minimaal drie maanden te rekenen.',
                            ],
                            [
                                'q' => 'Hoe werkt de vriendenactie?',
                                'a' => 'Iedere klant heeft een persoonlijke uitnodigingslink. Een vriend die zich via die link aanmeldt krijgt {vriendkorting}. Start je vriend, dan krijg jij {jouwkorting}.',
                            ],
                        ],
                        ['min' => 1, 'max' => 15],
                    ),
                ]),
                'afsluiter' => F::ctaSection([
                    'title' => 'Start vandaag nog',
                    'text' => 'Maak je gratis account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.',
                    'primary' => 'Account aanmaken',
                    'secondary' => 'Gratis kennismaking',
                ]),
                'seo' => F::seoSection(
                    'Online coaching',
                    'Online coaching door Steyn van Leeuwen: trainingsschema en voedingsplan op maat, wekelijkse check-ins in je eigen dashboard en persoonlijke bijsturing. Nodig een vriend uit en krijg samen korting.',
                ),
            ],
        ];
    }
}
