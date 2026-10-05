<?php

declare(strict_types=1);

namespace App\Content\Pages;

use App\Content\Fields as F;

/**
 * Teksten van 'Privacyverklaring' in het beheer (overgenomen uit src/lib/content/pages/overige.ts).
 * Volgorde van onderdelen en velden is de volgorde in het beheer.
 */
final class Privacy
{
    public const SLUG = 'privacy';

    private static ?array $page = null;

    /** De paginadefinitie (opbouw: zie App\Content\Fields). */
    public static function page(): array
    {
        return self::$page ??= self::define();
    }

    private static function define(): array
    {
        return [
            'slug' => 'privacy',
            'title' => 'Privacyverklaring',
            'path' => '/privacy',
            'description' => 'De privacyverklaring.',
            'sections' => [
                'intro' => F::section('Bovenaan', [
                    'eyebrow' => F::line('Kleine kop', 'SteynPT', ['max' => 60]),
                    'title' => F::title('Titel', 'Privacyverklaring', ['max' => 80]),
                    'intro' => F::text(
                        'Introductie',
                        'SteynPT gaat zorgvuldig om met je persoonsgegevens en houdt zich aan de Algemene Verordening Gegevensbescherming (AVG). Hieronder lees je welke gegevens we verwerken en waarom.',
                        ['max' => 600],
                    ),
                ]),
                'onderdelen' => F::section('Onderdelen', [
                    'sections' => F::items(
                        'Onderdelen',
                        'Onderdeel',
                        ['title' => F::line('Kop', '', ['max' => 100]), 'body' => F::text('Tekst', '', ['max' => 4000, 'paragraphs' => true])],
                        [
                            [
                                'title' => 'Wie zijn wij?',
                                'body' => 'SteynPT is de onderneming van Steyn van Leeuwen, personal trainer in Amsterdam. SteynPT is verantwoordelijk voor de verwerking van je persoonsgegevens zoals beschreven in deze verklaring. Heb je een vraag over je privacy of wil je een verzoek doen? Neem contact op via het contactformulier op deze website.',
                            ],
                            [
                                'title' => 'Welke gegevens verwerken we?',
                                'body' => implode("\n\n", [
                                    'Contactaanvragen: je naam, e-mailadres, (optioneel) telefoonnummer, je interesse en je bericht.',
                                    'Account: je naam, e-mailadres, telefoonnummer, doel, gekozen pakket, door wie je bent uitgenodigd en wie jij hebt uitgenodigd (vriendenactie).',
                                    'Afspraken: type, datum, tijd, locatie en je eventuele opmerking. Steyn zet deze afspraken, met je naam en contactgegevens, via een beveiligde, geheime link in zijn eigen agenda (bijvoorbeeld Google of Apple Agenda).',
                                    'Metingen: gewicht, vetpercentage, spiermassa en omtrekmaten die Steyn met je bijhoudt. Dit zijn gezondheidsgegevens; je ziet ze zelf in Mijn omgeving.',
                                    'Check-ins: je wekelijkse scores voor energie, slaap en voeding, aantal trainingen, eventueel je gewicht en opmerkingen. Dit zijn gezondheidsgegevens; we verwerken ze alleen met jouw uitdrukkelijke toestemming en uitsluitend voor je coaching.',
                                    'Intake: je doel, geslacht, geboortejaar, lengte, gewicht, activiteit, trainingservaring en -wensen, blessures, eetstijl, allergieën en eventuele medische aandachtspunten. Ook dit zijn gezondheidsgegevens; we gebruiken ze alleen met jouw uitdrukkelijke toestemming en alleen om je trainings- en voedingsschema te maken.',
                                ]),
                            ],
                            [
                                'title' => 'Gebruik van AI voor je schema',
                                'body' => implode("\n\n", [
                                    'Voor een eerste opzet van je trainings- en voedingsschema gebruiken we het AI-model Claude van Anthropic. We sturen daarvoor alleen de intakegegevens die nodig zijn voor het schema, zonder je naam, e-mailadres of telefoonnummer.',
                                    'De AI neemt geen beslissingen over jou: Steyn controleert en past elk schema aan voordat je het te zien krijgt. Anthropic verwerkt de gegevens als verwerker en gebruikt ze volgens zijn zakelijke voorwaarden niet om AI-modellen te trainen. Anthropic is gevestigd in de Verenigde Staten; de doorgifte gebeurt op basis van passende waarborgen, zoals de standaardcontractbepalingen van de Europese Commissie.',
                                    'Je kunt je toestemming altijd intrekken. Neem dan contact met ons op; Steyn maakt je schema dan volledig zelf.',
                                ]),
                            ],
                            [
                                'title' => 'Waarom?',
                                'body' => 'Om contact met je op te nemen, afspraken te plannen, je coaching te verzorgen, je voortgang te volgen en de vriendenactie uit te voeren. Nieuwsbrieven en acties ontvang je alleen als je daar zelf voor kiest.',
                            ],
                            [
                                'title' => 'Hoe lang bewaren we je gegevens?',
                                'body' => 'Zolang je account bestaat of zolang nodig is voor je traject. Contactaanvragen zonder vervolg verwijderen we uiterlijk na 12 maanden. Wettelijke bewaarplichten (zoals voor facturen) blijven gelden.',
                            ],
                            [
                                'title' => 'Delen met anderen',
                                'body' => 'We verkopen je gegevens nooit. We delen ze alleen met partijen die nodig zijn om de website en je coaching te laten werken (zoals de hosting, de AI-dienst hierboven en de agenda-app van Steyn), onder passende afspraken.',
                            ],
                            [
                                'title' => 'Beveiliging',
                                'body' => 'Wachtwoorden worden versleuteld opgeslagen en je sessie is beveiligd met een veilige cookie. Alleen Steyn heeft toegang tot je coachinggegevens en intake.',
                            ],
                            [
                                'title' => 'Jouw rechten',
                                'body' => 'Je kunt je gegevens altijd inzien en aanpassen in je profiel, en je account met alle bijbehorende gegevens zelf verwijderen. Voor andere verzoeken (zoals een kopie van je gegevens of het intrekken van toestemming) kun je contact met ons opnemen. Ben je het niet eens met hoe we met je gegevens omgaan? Dan kun je een klacht indienen bij de Autoriteit Persoonsgegevens.',
                            ],
                            [
                                'title' => 'Cookies',
                                'body' => 'We gebruiken alleen functionele cookies: om je ingelogd te houden en om bij te houden via wiens uitnodiging je binnenkomt. Er worden geen tracking- of advertentiecookies geplaatst.',
                            ],
                        ],
                        ['min' => 1, 'max' => 20],
                    ),
                ]),
                'seo' => F::seoSection('Privacyverklaring', 'Hoe SteynPT omgaat met je persoonsgegevens.'),
            ],
        ];
    }
}
