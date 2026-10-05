<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Voedingscoaching' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Voedingscoaching
{
    public const SLUG = 'voedingscoaching';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'voedingscoaching',
            'title' => 'Voedingscoaching',
            'path' => '/voedingscoaching',
            'description' => 'Voedingsbegeleiding, waarbij Steyn helpt en hoe er gemeten wordt.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Voedingscoach in Amsterdam Oud-West', ['max' => 60]),
                    'title' => F::title('Titel', 'Voeding die *werkt* voor jou', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Aan de hand van jouw doelen maak ik een gericht voedingsplan voor je. Stap voor stap verbeteren we je voeding en je gezondheid. Orthomoleculaire, leefstijl- en vitaliteitscoaching.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Knop', 'Vraag een gratis kennismaking aan', ['max' => 40]),
                ]),
                'begeleiding' => F::section('Voedingsbegeleiding', [
                    'eyebrow' => F::line('Kleine kop', 'Voedingsbegeleiding', ['max' => 60]),
                    'title' => F::title('Titel', "De juiste balans in macro's én micro's", ['max' => 100]),
                    'body' => F::text(
                        'Tekst',
                        "Ik ben orthomoleculair voedingstherapeut en help je aan de juiste balans in macro- en micronutriënten. Geen crashdiëten, maar een plan dat past bij jouw leven en dat je volhoudt.\n\nWil je afvallen, aankomen of heb je klachten? Door je voeding aan te passen kunnen we samen veel bereiken.",
                        ['max' => 2000, 'paragraphs' => true],
                    ),
                    'cardTitle' => F::line('Kaart: titel', 'Ik help je onder andere bij', ['max' => 60]),
                    'cardList' => F::list('Kaart: opsomming', ['Afvallen of aankomen', 'Darmklachten', 'Verhoogd cholesterol', 'Verhoogde bloeddruk', 'Acne', 'Weinig energie'], [
                        'max' => 12,
                        'hint' => 'Eén punt per regel.',
                    ]),
                ]),
                'meten' => F::section('Meten is weten', [
                    'eyebrow' => F::line('Kleine kop', 'Meten is weten', ['max' => 60]),
                    'title' => F::title('Titel', 'Resultaat dat je kunt zien', ['max' => 100]),
                    'intro' => F::text('Introductie', 'We starten met een nulmeting en meten tussentijds je voortgang, zodat we precies weten wat werkt.', ['max' => 400]),
                    'points' => F::list(
                        'Opsomming',
                        ['Intake en analyse van je eetpatroon', 'Voedingsplan op maat', 'Tussentijdse metingen en bijsturing', 'Onderdeel van elk PT- en online pakket'],
                        ['max' => 8, 'hint' => 'Eén punt per regel.'],
                    ),
                ]),
                'faq' => F::section('Veelgestelde vragen', [
                    'eyebrow' => F::line('Kleine kop', 'Veelgestelde vragen', ['max' => 60]),
                    'title' => F::title('Titel', 'Vragen over voedingscoaching', ['max' => 100]),
                    'questions' => F::items(
                        'Vragen',
                        'Vraag',
                        ['q' => F::line('Vraag', '', ['max' => 160]), 'a' => F::text('Antwoord', '', ['max' => 800])],
                        [
                            [
                                'q' => 'Wat doet een orthomoleculair voedingstherapeut?',
                                'a' => 'Een orthomoleculair voedingstherapeut kijkt niet alleen naar calorieën en macronutriënten (eiwitten, koolhydraten en vetten), maar ook naar micronutriënten zoals vitamines en mineralen. Zo werken we aan je doel én aan hoe je je voelt.',
                            ],
                            [
                                'q' => 'Hoe verloopt voedingscoaching?',
                                'a' => 'We beginnen met een intake en een analyse van je eetpatroon. Daarna krijg je een voedingsplan op maat. Met tussentijdse metingen zien we wat werkt en sturen we bij.',
                            ],
                            [
                                'q' => 'Moet ik een streng dieet volgen?',
                                'a' => 'Nee. Geen crashdiëten, maar een plan dat past bij jouw leven en dat je volhoudt. Stap voor stap verbeteren we je voeding.',
                            ],
                            [
                                'q' => 'Kan voedingscoaching ook online?',
                                'a' => 'Ja. Voedingscoaching is onderdeel van online coaching: je voeding en je wekelijkse check-ins staan in je eigen dashboard. Woon je in Amsterdam, dan kun je ook langskomen bij Gymbase in Oud-West.',
                            ],
                            [
                                'q' => 'Ik heb een medische aandoening. Is voedingscoaching dan geschikt?',
                                'a' => 'Voedingscoaching vervangt geen behandeling door je arts. Gebruik je medicijnen of heb je een aandoening, vertel het dan tijdens de intake. Waar nodig stemmen we het plan af met je arts.',
                            ],
                        ],
                        ['min' => 1, 'max' => 15],
                    ),
                ]),
                'afsluiter' => F::ctaSection([
                    'title' => 'Ook online mogelijk',
                    'text' => 'Voedingscoaching is onderdeel van online coaching. Je voeding en je wekelijkse check-ins staan gewoon in je eigen dashboard.',
                    'primary' => 'Bekijk online coaching',
                    'secondary' => 'Gratis kennismaking',
                ]),
                'seo' => F::seoSection(
                    'Voedingscoach in Amsterdam Oud-West',
                    'Voedingscoaching door orthomoleculair voedingstherapeut Steyn van Leeuwen, in Amsterdam Oud-West of online. Bij afvallen, aankomen, energie en darmklachten.',
                ),
            ],
        ];
    }
}
