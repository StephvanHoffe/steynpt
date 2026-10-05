<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Over Steyn' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class OverSteyn
{
    public const SLUG = 'over-steyn';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'over-steyn',
            'title' => 'Over Steyn',
            'path' => '/over-steyn',
            'description' => 'Het verhaal van Steyn en de werkwijze.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Kom alles te weten', ['max' => 60]),
                    'title' => F::title('Titel', 'Over *Steyn*', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Fulltime personal trainer en voedingscoach. Geboren in Hoevelaken, werkzaam in Amsterdam, en elke dag nog aan het doorleren.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Knop', 'Vraag een kennismaking aan', ['max' => 40]),
                ]),
                'verhaal' => F::section('Mijn verhaal', [
                    'eyebrow' => F::line('Kleine kop', 'Mijn verhaal', ['max' => 60]),
                    'title' => F::title('Titel', 'Van hernia naar passie', ['max' => 100]),
                    'body' => F::text(
                        'Tekst',
                        implode("\n\n", [
                            'Ik ben Steyn van Leeuwen, fulltime personal trainer en voedingscoach. Ik ben geboren in Hoevelaken en nu werkzaam als PT in Amsterdam. Toen ik 15 was, begon ik met krachttraining: door het hockeyen had ik een beginnende hernia in mijn onderrug, en de fysiotherapeut raadde het me aan.',
                            'Ik vond hierin mijn passie en kwam er al snel achter dat er veel onduidelijkheid is in de fitnesswereld: iedereen vindt er iets anders van. Daar is mijn interesse begonnen. Ik heb meerdere opleidingen gevolgd in personal training en voeding, en ik blijf elke dag doorleren.',
                            'Ik geef persoonlijke trainingen, maak voedingsplannen op maat, begeleid sporters naar specifieke doelen en geef ademcoaching, 1-op-1 en in groepsverband. Samen werken we aan jouw doelen: binnen, buiten, in de gym, thuis, op kantoor of online.',
                            'Wil je serieus aan de slag met je gezondheid en weten wat ik voor je kan betekenen? Neem dan contact met mij op!',
                        ]),
                        ['max' => 3000, 'paragraphs' => true],
                    ),
                    'expertisesTitle' => F::line('Kop boven de expertises', 'Expertises', ['max' => 40, 'hint' => "De expertises zelf pas je aan onder 'Op elke pagina'."]),
                ]),
                'werkwijze' => F::section(
                    'Werkwijze',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Werkwijze', ['max' => 60]),
                        'title' => F::title('Titel', 'Zo werken we samen', ['max' => 100]),
                        'intro' => F::text(
                            'Introductie',
                            'Ik coach je niet alleen tijdens de trainingen. Ook daarbuiten hebben we contactmomenten om je gezondheid naar een hoger niveau te tillen.',
                            ['max' => 400],
                        ),
                    ],
                    "De stappen zelf pas je aan onder 'Op elke pagina'.",
                ),
                'afsluiter' => F::ctaSection([
                    'title' => 'Leuk om eens kennis te maken!',
                    'text' => 'We ontvangen je graag bij Gymbase, of start direct online.',
                    'primary' => 'Gratis kennismaking',
                    'secondary' => 'Start online coaching',
                ]),
                'seo' => F::seoSection(
                    'Over Steyn van Leeuwen, personal trainer in Amsterdam',
                    'Maak kennis met Steyn van Leeuwen: fulltime personal trainer, voedingscoach en orthomoleculair voedingstherapeut in Amsterdam.',
                ),
            ],
        ];
    }
}
