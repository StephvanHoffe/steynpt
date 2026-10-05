<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Centrale gegevens van SteynPT die de paginadefinities als standaardwaarde gebruiken (overgenomen uit
 * src/lib/site.ts en src/lib/referral-program.ts). Pakketten, prijzen, adres en vriendenactie past Steyn aan in het
 * beheer onder Website-teksten: toon ze op de site daarom via de teksten (Values::resolvePage), niet rechtstreeks
 * vanuit deze klasse. Alleen de vaste gegevens (zoals de id's van de online pakketten) zijn hier leidend.
 */
final class Defaults
{
    /** 'url' is de standaard; op de site geldt het adres uit de configuratie als dat anders is. */
    public const SITE = [
        'name' => 'SteynPT',
        'url' => 'https://www.steynpt.nl',
        'description' => 'Personal trainer bij Gymbase aan de Overtoom in Amsterdam Oud-West, vlak bij het Vondelpark. 1-op-1 training, online coaching, voedingscoaching en ademcoaching.',
        'instagram' => ['url' => 'https://www.instagram.com/bigtimesteyn/', 'handle' => '@bigtimesteyn'],
    ];

    // Standaardadres; Steyn past het aan in het beheer onder Website-teksten.
    public const LOCATIONS = [
        ['name' => 'Gymbase', 'street' => 'Overtoom 371-w', 'city' => '1054 JN Amsterdam'],
    ];

    // Ademcoaching: 1-op-1 een vast tarief, groepssessies op aanvraag.
    public const BREATHWORK_SESSION = [
        'minutes' => 90,
        'duration' => '1,5 uur',
        'price' => '210',
        'features' => [
            'Persoonlijke begeleiding door Steyn',
            'Afgestemd op jouw vraag: stress, slaap, sport of herstel',
            'Oefeningen om zelf mee verder te gaan',
            'Geen ervaring nodig',
        ],
    ];

    // Personal training. 'unit' en 'note' zijn null als ze er niet zijn.
    public const PT_PRICES = [
        [
            'name' => 'Losse training',
            'label' => 'Personal training',
            'price' => '120',
            'unit' => 'per uur',
            'features' => ['Verbeter je gezondheid', 'Flexibele tijden', 'Stap voor stap naar je doel', 'Op maat gemaakt'],
            'note' => 'Duo-training: € 15 toeslag per sessie',
            'featured' => false,
        ],
        [
            'name' => 'Introductiepakket',
            'label' => '10× 1-op-1-training',
            'price' => '1.050',
            'unit' => null,
            'features' => ['Intakegesprek', 'Start- en eindmeting', 'Bewegingsassessment', 'Wekelijks voedingsadvies'],
            'note' => null,
            'featured' => false,
        ],
        [
            'name' => 'Gezondheidspakket',
            'label' => '20× 1-op-1-training',
            'price' => '2.000',
            'unit' => null,
            'features' => [
                'Intakegesprek',
                'Start-, tussen- en eindmeting',
                'Bewegingsassessment',
                'Verbeter je gezondheid',
                'Verander je leefstijl',
            ],
            'note' => null,
            'featured' => true,
        ],
        [
            'name' => 'Lifechanger-pakket',
            'label' => '40× 1-op-1-training',
            'price' => '3.800',
            'unit' => null,
            'features' => [
                'Intakegesprek',
                'Start-, tussen- en eindmeting',
                'Bewegingsassessment',
                'Verbeter je gezondheid',
                'Verander je leefstijl',
            ],
            'note' => null,
            'featured' => false,
        ],
    ];

    // Online coaching is nieuw; deze prijzen zijn een voorstel. De id's zijn vast (ze staan bij leden opgeslagen),
    // net als de volgorde: de teksten van de pakketten horen op volgorde bij deze id's.
    public const ONLINE_PLANS = [
        [
            'id' => 'online-start',
            'name' => 'Start',
            'tagline' => 'Zelfstandig trainen met een plan dat klopt',
            'price' => '79',
            'features' => [
                'Trainingsschema op maat',
                'Voedingsrichtlijnen op basis van je doel',
                'Maandelijkse evaluatie en schema-update',
                'Wekelijkse check-in in je dashboard',
                'Je voortgang en metingen in je dashboard',
            ],
            'featured' => false,
        ],
        [
            'id' => 'online-pro',
            'name' => 'Pro',
            'tagline' => 'Wekelijkse sturing voor maximaal resultaat',
            'price' => '129',
            'features' => [
                'Alles uit Start',
                "Persoonlijk voedingsplan (macro's & micro's)",
                'Wekelijkse feedback van Steyn op je check-in',
                'Tussentijds contact op werkdagen',
                'Schema-updates wanneer jij ze nodig hebt',
            ],
            'featured' => true,
        ],
        [
            'id' => 'online-performance',
            'name' => 'Performance',
            'tagline' => 'Voor specifieke doelen en (top)sporters',
            'price' => '199',
            'features' => [
                'Alles uit Pro',
                'Twee videocalls per maand',
                'Sportspecifieke periodisering',
                "Techniekanalyse op basis van je video's",
                'Ademhalingsprotocol voor focus en herstel',
            ],
            'featured' => false,
        ],
    ];

    // Vriendenactie voor online coaching. Beloningen zijn een voorstel.
    public const REFERRAL = [
        // Wat de nieuwe klant krijgt bij aanmelding via een uitnodiging.
        'friendReward' => '50% korting op de eerste maand online coaching',
        // Wat de uitnodiger krijgt zodra de vriend daadwerkelijk start.
        'referrerReward' => '50% korting op een maand online coaching',
        'headline' => 'Samen 50% korting',
    ];
}
