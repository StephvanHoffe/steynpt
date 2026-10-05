<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Personal training' in het beheer (overgenomen uit src/lib/content/pages/personal-training.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class PersonalTraining
{
    public const SLUG = 'personal-training';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'personal-training',
            'title' => 'Personal training',
            'path' => '/personal-training',
            'description' => '1-op-1 training, topsport en de PT-pakketten.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Personal training in Amsterdam Oud-West', ['max' => 60]),
                    'title' => F::title('Titel', '1-op-1. *100%* voor jouw doel.', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Ongeacht jouw doel of sport: SteynPT gaat er 100% voor. Met een persoonlijk trainingsplan werken we zo efficiënt mogelijk naar jouw doel toe, met veel energie, aandacht voor de juiste uitvoering en een fijne sfeer. Je traint bij Gymbase aan de Overtoom, op een paar minuten van het Vondelpark, of op een plek die jou uitkomt.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Eerste knop', 'Vraag een gratis proefles aan', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Bekijk de pakketten', ['max' => 40]),
                ]),
                'leefstijl' => F::section('Gezondere leefstijl', [
                    'eyebrow' => F::line('Kleine kop', 'Voor een gezondere leefstijl', ['max' => 60]),
                    'title' => F::title('Titel', 'Een duidelijk plan, samen uitgevoerd', ['max' => 100]),
                    'body' => F::text(
                        'Tekst',
                        "Ik maak een gepersonaliseerd trainingsplan voor je, zodat we zo efficiënt mogelijk naar je doel toewerken. Met een duidelijk en overzichtelijk plan weet je precies wat je te wachten staat en wat je moet doen om jouw doel te bereiken.\n\nNaast ervaring in krachttraining heb ik een achtergrond in powerliften, boksen, CrossFit en sportspecifieke training. Samen maken we, indien gewenst, een mooie combinatie om jouw doel te bereiken.",
                        ['max' => 2000, 'paragraphs' => true],
                    ),
                    'cardTitle' => F::line('Kaart: titel', 'Altijd inbegrepen', ['max' => 60]),
                    'cardList' => F::list(
                        'Kaart: opsomming',
                        [
                            'Intakegesprek over je doelen en achtergrond',
                            'Nulmeting: wegen, meten en bewegen',
                            'Persoonlijk trainingsschema',
                            'Voedingsadvies op basis van jouw doel',
                            'Contactmomenten ook buiten de trainingen',
                            'Trainen bij Gymbase (Overtoom, Oud-West) of op locatie',
                        ],
                        ['max' => 10, 'hint' => 'Eén punt per regel.'],
                    ),
                ]),
                'topsport' => F::section('Specifieke doelen & topsport', [
                    'eyebrow' => F::line('Kleine kop', 'Specifieke doelen & topsport', ['max' => 60]),
                    'title' => F::title('Titel', 'Begeleiding voor sporters die *meer* willen', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Werk je naar een wedstrijd, wil je terugkomen na een blessure of zoek je die laatste procenten? Steyn is gespecialiseerd in het 1-op-1 begeleiden van specifieke doelen en (top)sporters.',
                        ['max' => 500],
                    ),
                    'cards' => F::items(
                        'Kaarten',
                        'Kaart',
                        ['title' => F::line('Titel', '', ['max' => 60]), 'text' => F::text('Tekst', '', ['max' => 300])],
                        [
                            ['title' => 'Doelgerichte periodisering', 'text' => 'Een plan dat toewerkt naar jouw wedstrijd, seizoen of moment suprême.'],
                            ['title' => 'Sportspecifieke kracht', 'text' => 'Kracht, snelheid en explosiviteit vertaald naar jouw sport.'],
                            ['title' => 'Blessurepreventie', 'text' => 'Bewegingsassessment en gerichte oefeningen om sterker én heler te blijven.'],
                            ['title' => 'Herstel & ademhaling', 'text' => 'Slaap, voeding en ademtechnieken voor optimaal herstel en focus onder druk.'],
                            ['title' => 'Techniekanalyse', 'text' => 'We analyseren je uitvoering en sturen bij, ook tussen de sessies door.'],
                            ['title' => 'Begeleiding rond je schema', 'text' => 'Afgestemd op trainingen bij je club, wedstrijden en reizen.'],
                        ],
                        ['fixed' => true],
                    ),
                    'primary' => F::line('Eerste knop', 'Bespreek jouw doel', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Combineer met online coaching', ['max' => 40]),
                ]),
                'tarieven' => F::section(
                    'Tarieven',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Tarieven', ['max' => 60]),
                        'title' => F::title('Titel', '1-op-1 pakketten', ['max' => 100]),
                        'intro' => F::text(
                            'Introductie',
                            'Sport je graag individueel en wil je samen met Steyn alles uit je sessie halen? Kies dan één van de 1-op-1 pakketten.',
                            ['max' => 400],
                        ),
                        'button' => F::line('Knop onder elk pakket', 'Plan een afspraak', ['max' => 40]),
                    ],
                    "De pakketten zelf pas je aan onder 'Prijzen en pakketten'.",
                ),
                'reviews' => F::section('Reviews', [
                    'eyebrow' => F::line('Kleine kop', 'Reviews', ['max' => 60]),
                    'title' => F::title('Titel', 'Resultaat dat blijft', ['max' => 100]),
                ]),
                'afsluiter' => F::ctaSection([
                    'title' => 'Zin om kennis te maken?',
                    'text' => 'Plan een gratis proefles of kennismaking. We ontvangen je graag bij Gymbase.',
                    'primary' => 'Gratis kennismaking',
                    'secondary' => 'Of start online',
                ]),
                'seo' => F::seoSection(
                    'Personal trainer in Amsterdam Oud-West',
                    '1-op-1 personal training bij Gymbase, Overtoom 371-w in Amsterdam Oud-West. Voor een gezondere leefstijl, specifieke doelen en topsport. Ook op locatie.',
                ),
            ],
        ];
    }
}
