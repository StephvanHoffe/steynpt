<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Contact' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Contact
{
    public const SLUG = 'contact';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'contact',
            'title' => 'Contact',
            'path' => '/contact',
            'description' => 'De contactpagina met het aanvraagformulier.',
            'sections' => [
                'hero' => F::section(
                    'Bovenaan',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Kom direct met Steyn in contact', ['max' => 60]),
                        'title' => F::title('Titel', 'Leuk om eens *kennis* te maken!', ['max' => 80]),
                        'intro' => F::text(
                            'Introductie',
                            'We nodigen je graag uit bij Gymbase voor een gratis kennismaking. We vertellen je meer over onze werkwijze, geven je een rondleiding en horen graag meer over jouw verwachtingen en doelen.',
                            ['max' => 500],
                        ),
                        'tipTitle' => F::line('Tip: vetgedrukt begin', 'Wist je dat', ['max' => 40]),
                        'tipText' => F::line('Tip: tekst', 'SteynPT ook trainingen op locatie aanbiedt?', ['max' => 160, 'hint' => "Daarna volgt automatisch de tekst 'op locatie' van 'Op elke pagina'."]),
                    ],
                    "Adres, reactietijd en Instagram pas je aan onder 'Op elke pagina'.",
                ),
                'formulier' => F::section('Formulier', [
                    'title' => F::title('Titel', 'Vraag een kennismaking aan', ['max' => 80]),
                    'intro' => F::text('Introductie', 'Vul je gegevens in, dan nemen we contact met je op om een moment te plannen.', ['max' => 300]),
                ]),
                'seo' => F::seoSection(
                    'Contact & gratis kennismaking',
                    'Vraag een gratis kennismaking aan bij SteynPT. We nodigen je graag uit bij Gymbase aan de Overtoom in Amsterdam.',
                ),
            ],
        ];
    }
}
