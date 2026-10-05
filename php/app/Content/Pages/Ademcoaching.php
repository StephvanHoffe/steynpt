<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Ademcoaching' in het beheer (overgenomen uit src/lib/content/pages/ademcoaching.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Ademcoaching
{
    public const SLUG = 'ademcoaching';

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
            'slug' => 'ademcoaching',
            'title' => 'Ademcoaching',
            'path' => '/ademcoaching',
            'description' => 'De ademsessie 1-op-1, groepssessies op aanvraag en veelgestelde vragen.',
            'sections' => [
                'hero' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'Ademcoaching in Amsterdam, 1-op-1 en in groepsverband', ['max' => 60]),
                    'title' => F::title('Titel', 'Adem in. *Kom tot rust.* Presteer beter.', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'Je ademhaling is het krachtigste gereedschap dat je altijd bij je hebt. In een persoonlijke ademsessie van {ademduur} leer je hoe je met je adem stress verlaagt, je focus vergroot en sneller herstelt. Met je team of groep kan het ook: groepssessies zijn op aanvraag.',
                        ['max' => 500],
                    ),
                    'primary' => F::line('Eerste knop', 'Plan een ademsessie', ['max' => 40]),
                    'secondary' => F::line('Tweede knop', 'Groepssessie aanvragen', ['max' => 40]),
                ]),
                'voordelen' => F::section('Wat het je oplevert', [
                    'eyebrow' => F::line('Kleine kop', 'Wat het je oplevert', ['max' => 60]),
                    'title' => F::title('Titel', 'Kleine verandering, groot effect', ['max' => 100]),
                    'intro' => F::text(
                        'Introductie',
                        'Ademcoaching sluit naadloos aan op de SteynPT-visie: een gezonde leefstijl draait niet alleen om trainen en voeding, maar ook om rust en herstel.',
                        ['max' => 400],
                    ),
                    'cards' => F::items(
                        'Kaarten',
                        'Kaart',
                        $card,
                        [
                            ['title' => 'Rust & focus', 'text' => 'Leer je zenuwstelsel kalmeren en helder te blijven onder druk.'],
                            ['title' => 'Beter slapen', 'text' => 'Ademtechnieken die je helpen ontspannen en dieper herstellen.'],
                            ['title' => 'Meer energie', 'text' => 'Een efficiëntere ademhaling geeft meer energie gedurende de dag.'],
                            ['title' => 'Sportprestatie', 'text' => 'Verbeter je uithoudingsvermogen, herstel tussen inspanningen en concentratie.'],
                        ],
                        ['fixed' => true],
                    ),
                ]),
                'vormen' => F::section(
                    '1-op-1 of in een groep',
                    [
                        'eyebrow' => F::line('Kleine kop', 'Twee vormen', ['max' => 60]),
                        'title' => F::title('Titel', '1-op-1 of met je groep', ['max' => 100]),
                        'soloLabel' => F::line('1-op-1: kleine kop', '1-op-1', ['max' => 30]),
                        'perSession' => F::line('1-op-1: na de prijs', 'per sessie', ['max' => 30]),
                        'soloButton' => F::line('1-op-1: knop', 'Plan een ademsessie', ['max' => 40]),
                        'groupLabel' => F::line('Groep: kleine kop', 'In groepsverband', ['max' => 30]),
                        'groupTitle' => F::line('Groep: titel', 'Groepssessie', ['max' => 40]),
                        'groupPrice' => F::line('Groep: prijs', 'Op aanvraag', ['max' => 30]),
                        'groupText' => F::text('Groep: tekst', 'Opzet, duur en tarief stemmen we af op jullie groepsgrootte en locatie.', ['max' => 300]),
                        'groups' => F::items(
                            'Groep: voor wie',
                            'Groep',
                            $card,
                            [
                                ['title' => 'Bedrijven', 'text' => 'Een vitaliteitssessie op kantoor of tijdens een teamdag. Werkt direct tegen werkstress.'],
                                ['title' => 'Sportteams', 'text' => 'Ademtraining als onderdeel van warming-up, herstel en mentale voorbereiding.'],
                                ['title' => 'Vrienden & groepen', 'text' => 'Samen iets nieuws ervaren, binnen in de studio of buiten in het park.'],
                            ],
                            ['fixed' => true],
                        ),
                        'groupButton' => F::line('Groep: knop', 'Vraag een groepssessie aan', ['max' => 40]),
                    ],
                    "Naam, prijs, duur en inhoud van de 1-op-1 sessie pas je aan onder 'Prijzen en pakketten'.",
                ),
                'sessie' => F::section('Zo ziet een sessie eruit', [
                    'title' => F::title('Titel', 'Zo ziet een sessie eruit', ['max' => 100]),
                    'steps' => F::items(
                        'Stappen',
                        'Stap',
                        $card,
                        [
                            ['title' => 'Uitleg', 'text' => 'Wat gebeurt er in je lichaam als je ademt, en waarom werkt dit?'],
                            ['title' => 'Oefenen', 'text' => 'Basistechnieken voor ontspanning, focus en energie die je overal kunt toepassen.'],
                            ['title' => 'Begeleide ademsessie', 'text' => 'Een langere sessie waarin Steyn je stap voor stap begeleidt.'],
                            ['title' => 'Meenemen', 'text' => 'Je gaat naar huis met concrete oefeningen voor je dagelijks leven of sport.'],
                        ],
                        ['min' => 2, 'max' => 6],
                    ),
                ]),
                'faq' => F::section('Veelgestelde vragen', [
                    'eyebrow' => F::line('Kleine kop', 'Veelgestelde vragen', ['max' => 60]),
                    'title' => F::title('Titel', 'Goed om te weten', ['max' => 100]),
                    'points' => F::list(
                        'Opsomming',
                        ['1-op-1: {ademduur} voor € {ademprijs},-', 'Groepssessies op aanvraag, vanaf 3 personen', 'Geen ervaring nodig', 'Op locatie, in de studio of buiten'],
                        ['max' => 8, 'hint' => 'Eén punt per regel.'],
                    ),
                    'questions' => F::items(
                        'Vragen',
                        'Vraag',
                        ['q' => F::line('Vraag', '', ['max' => 160]), 'a' => F::text('Antwoord', '', ['max' => 800])],
                        [
                            [
                                'q' => 'Hoe lang duurt een 1-op-1 ademsessie en wat kost het?',
                                'a' => 'Een 1-op-1 ademsessie duurt {ademduur} en kost € {ademprijs},-. In die tijd is er ruimte voor uitleg, oefenen en een langere begeleide ademsessie.',
                            ],
                            [
                                'q' => 'Hoe werkt een groepssessie?',
                                'a' => 'Groepssessies zijn op aanvraag. Neem contact op, dan stemmen we de opzet, duur en prijs af op jullie groepsgrootte en locatie. Een sessie werkt het best met kleine tot middelgrote groepen.',
                            ],
                            ['q' => 'Heb ik ervaring nodig?', 'a' => 'Nee. Iedere sessie start met uitleg en de oefeningen worden afgestemd op beginners én gevorderden.'],
                            [
                                'q' => 'Is ademcoaching voor iedereen geschikt?',
                                'a' => 'Voor de meeste mensen wel. Ben je zwanger, heb je epilepsie, hart- en vaatziekten of andere medische klachten? Meld het vooraf, dan passen we de oefeningen aan of overleggen we eerst met je arts.',
                            ],
                            ['q' => 'Waar vindt een sessie plaats?', 'a' => 'Bij Gymbase in Amsterdam, bij jullie op kantoor of buiten. Zo lang er rustig ruimte is om te liggen of te zitten.'],
                        ],
                        ['min' => 1, 'max' => 15],
                    ),
                ]),
                'afsluiter' => F::ctaSection([
                    'title' => 'Plan je ademsessie',
                    'text' => 'Kom voor een persoonlijke sessie, of vertel ons over je team of groep, dan stellen we een groepssessie op maat voor.',
                    'primary' => 'Plan een 1-op-1 sessie',
                    'secondary' => 'Groepssessie aanvragen',
                ]),
                'seo' => F::seoSection(
                    'Ademcoaching 1-op-1 en in groepsverband',
                    'Ademcoaching door Steyn van Leeuwen in Amsterdam: een 1-op-1 ademsessie van {ademduur} voor € {ademprijs}, of een groepssessie op aanvraag voor bedrijven, sportteams en vriendengroepen.',
                ),
            ],
        ];
    }
}
