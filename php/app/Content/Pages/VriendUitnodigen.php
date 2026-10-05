<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Vriendenactie' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class VriendUitnodigen
{
    public const SLUG = 'vriend-uitnodigen';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'vriend-uitnodigen',
            'title' => 'Vriendenactie',
            'path' => '/vriend-uitnodigen',
            'description' => 'Uitleg en voorwaarden van de vriendenactie.',
            'sections' => [
                'hero' => F::section(
                    'Bovenaan',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Vriendenactie online coaching', ['max' => 60]),
                        'title' => F::title('Titel', 'Breng een vriend mee. *{actie}.*', ['max' => 100]),
                        'intro' => F::text(
                            'Introductie',
                            'Train je al bij Steyn? Nodig een vriend uit voor online coaching. Je vriend krijgt {vriendkorting} en jij krijgt {jouwkorting} zodra je vriend start.',
                            ['max' => 500],
                        ),
                        'primary' => F::line('Eerste knop', 'Naar mijn uitnodigingslink', ['max' => 40]),
                        'secondary' => F::line('Tweede knop', 'Nog geen account? Meld je aan', ['max' => 40]),
                    ],
                    "De korting zelf en de drie stappen pas je aan onder 'Op elke pagina'.",
                ),
                'stappen' => F::section('Zo werkt het', [
                    'eyebrow' => F::line('Kleine kop', 'Zo werkt het', ['max' => 60]),
                    'title' => F::title('Titel', 'In drie stappen', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Je persoonlijke link staat in Mijn omgeving. Wie zich via jouw link aanmeldt, wordt automatisch aan jou gekoppeld; in je dashboard zie je wie zich heeft aangemeld en wie al is gestart.',
                        ['max' => 500],
                    ),
                ]),
                'voorwaarden' => F::section('Voorwaarden', [
                    'title' => F::title('Titel', 'Voorwaarden vriendenactie', ['max' => 100]),
                    'points' => F::list(
                        'Voorwaarden',
                        [
                            'De actie geldt voor nieuwe klanten van online coaching die zich aanmelden via een persoonlijke uitnodigingslink of -code.',
                            'De nieuwe klant krijgt {vriendkorting}.',
                            'De uitnodiger krijgt {jouwkorting} per vriend die daadwerkelijk start; Steyn verrekent dit met een volgende factuur.',
                            'Kortingen zijn niet inwisselbaar voor geld en niet te combineren met andere acties.',
                            'Jezelf uitnodigen of meerdere accounts aanmaken is niet toegestaan.',
                            'SteynPT kan de actie aanpassen of beëindigen; reeds verdiende kortingen blijven geldig.',
                        ],
                        ['max' => 15, 'hint' => 'Eén voorwaarde per regel.'],
                    ),
                ]),
                'afsluiter' => F::ctaSection([
                    'title' => 'Nog geen klant?',
                    'text' => 'Maak een gratis account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.',
                    'primary' => 'Bekijk online coaching',
                    'secondary' => 'Gratis kennismaking',
                ]),
                'seo' => F::seoSection('Vriend uitnodigen', 'Nodig een vriend uit voor online coaching bij SteynPT. {actie}: je vriend krijgt {vriendkorting}.'),
            ],
        ];
    }
}
