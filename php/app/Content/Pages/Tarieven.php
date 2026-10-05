<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Tarieven' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Tarieven
{
    public const SLUG = 'tarieven';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'tarieven',
            'title' => 'Tarieven',
            'path' => '/tarieven',
            'description' => "De koppen en uitleg op de tarievenpagina. De prijzen zelf staan onder 'Prijzen en pakketten'.",
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Prijzen en pakketten', ['max' => 60]),
                    'title' => F::title('Titel', 'Tarieven', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Transparante prijzen, geen verrassingen. Kies het pakket dat bij jouw doel past, of plan eerst een gratis kennismaking.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Knop', 'Gratis kennismaking', ['max' => 40]),
                ]),
                'menu' => F::section(
                    'Snelmenu',
                    [
                        'online' => F::line('Online coaching', 'Online coaching', ['max' => 30]),
                        'pt' => F::line('Personal training', '1-op-1-training', ['max' => 30]),
                        'adem' => F::line('Ademcoaching', 'Ademcoaching', ['max' => 30]),
                    ],
                    'De knoppen onder de titel die naar elk blok springen.',
                ),
                'online' => F::section('Online coaching', [
                    'eyebrow' => F::line('Kleine kop', 'Nieuw · Online coaching', ['max' => 60]),
                    'title' => F::title('Titel', 'Online coaching', ['max' => 100]),
                    'intro' => F::text('Introductie', 'Maandelijkse begeleiding met je eigen dashboard, wekelijkse check-ins en je metingen in één overzicht.', ['max' => 400]),
                ]),
                'pt' => F::section('Personal training', [
                    'eyebrow' => F::line('Kleine kop', '1-op-1-trainingen', ['max' => 60]),
                    'title' => F::title('Titel', 'Personal training', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Train je graag 1-op-1 en wil je samen met Steyn alles uit je sessie halen? Kies dan een van de pakketten.',
                        ['max' => 400],
                    ),
                    'button' => F::line('Knop onder elk pakket', 'Vraag dit pakket aan', ['max' => 40]),
                    'note' => F::line('Kleine tekst onder de pakketten', '', ['max' => 200, 'optional' => true]),
                ]),
                'adem' => F::section('Ademcoaching', [
                    'eyebrow' => F::line('Kleine kop', '1-op-1 en in groepsverband', ['max' => 60]),
                    'title' => F::title('Titel', 'Ademcoaching', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Een persoonlijke ademsessie met een vast tarief. Voor bedrijven, sportteams en vriendengroepen zijn groepssessies op aanvraag.',
                        ['max' => 400],
                    ),
                    'soloButton' => F::line('1-op-1: knop', 'Plan een ademsessie', ['max' => 40]),
                    'groupLabel' => F::line('Groep: kleine kop', 'In groepsverband', ['max' => 30]),
                    'groupTitle' => F::line('Groep: titel', 'Groepssessie', ['max' => 40]),
                    'groupPrice' => F::line('Groep: prijs', 'Op aanvraag', ['max' => 30]),
                    'groupText' => F::text(
                        'Groep: tekst',
                        'Opzet, duur en tarief stemmen we af op jullie groepsgrootte en locatie.',
                        ['max' => 300],
                    ),
                    'groupButton' => F::line('Groep: knop', 'Vraag een groepssessie aan', ['max' => 40]),
                    'moreLink' => F::line('Groep: link eronder', 'Meer over ademcoaching', ['max' => 40]),
                ]),
                'reviews' => F::section('Reviews', [
                    'eyebrow' => F::line('Kleine kop', 'Reviews', ['max' => 60]),
                    'title' => F::title('Titel', 'Wat sporters zeggen', ['max' => 100]),
                ]),
                'seo' => F::seoSection(
                    'Tarieven personal training en coaching in Amsterdam',
                    'Alle tarieven van SteynPT: 1-op-1 personal training bij Gymbase in Amsterdam Oud-West, online coaching en ademcoaching (1-op-1 en in groepsverband).',
                ),
            ],
        ];
    }
}
