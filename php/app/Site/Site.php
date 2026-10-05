<?php

namespace App\Site;

/**
 * Vaste gegevens van de site die niet via Website-teksten worden aangepast: het menu, de pakket-id's,
 * de doelen bij registreren en de onderwerpen van het contactformulier.
 */
final class Site
{
    /** `desktop => false` = alleen in het mobiele menu en de footer. */
    public const NAV = [
        ['href' => '/online-coaching', 'label' => 'Online coaching', 'highlight' => true],
        ['href' => '/personal-training', 'label' => 'Personal training'],
        ['href' => '/ademcoaching', 'label' => 'Ademcoaching'],
        ['href' => '/voedingscoaching', 'label' => 'Voeding'],
        ['href' => '/tarieven', 'label' => 'Tarieven'],
        ['href' => '/account/agenda', 'label' => 'Afspraak maken'],
        ['href' => '/over-steyn', 'label' => 'Over Steyn'],
    ];

    /** Id's van de online pakketten (vast: ze staan bij leden opgeslagen). Naam en prijs staan in Website-teksten. */
    public const ONLINE_PLAN_IDS = ['online-start', 'online-pro', 'online-performance'];

    public const GOALS = [
        ['id' => 'afvallen', 'label' => 'Afvallen'],
        ['id' => 'spieropbouw', 'label' => 'Spiermassa opbouwen'],
        ['id' => 'fitter', 'label' => 'Fitter en meer energie'],
        ['id' => 'leefstijl', 'label' => 'Gezondere leefstijl'],
        ['id' => 'prestatie', 'label' => 'Sportprestatie / topsport'],
        ['id' => 'herstel', 'label' => 'Sterker na blessure of klachten'],
    ];

    public const INTERESTS = [
        ['id' => 'online-coaching', 'label' => 'Online coaching'],
        ['id' => 'personal-training', 'label' => 'Personal training (1-op-1)'],
        ['id' => 'topsport', 'label' => 'Begeleiding specifiek doel / topsport'],
        ['id' => 'ademcoaching', 'label' => 'Ademsessie 1-op-1'],
        ['id' => 'ademcoaching-groep', 'label' => 'Ademcoaching in groepsverband (op aanvraag)'],
        ['id' => 'voedingscoaching', 'label' => 'Voedingsbegeleiding'],
    ];

    public static function isOnlinePlan(?string $id): bool
    {
        return $id !== null && in_array($id, self::ONLINE_PLAN_IDS, true);
    }

    public static function goalLabel(?string $id): ?string
    {
        return collect(self::GOALS)->firstWhere('id', $id)['label'] ?? null;
    }

    public static function interestLabel(?string $id): ?string
    {
        return collect(self::INTERESTS)->firstWhere('id', $id)['label'] ?? null;
    }

    /** Routelink naar Google Maps voor een adres. */
    public static function mapsUrl(string $street, string $city): string
    {
        return 'https://maps.google.com/?q='.str_replace(['%20', '%2C'], ['+', ','], rawurlencode("{$street}, {$city}"));
    }

    /** Publiek adres van de site, voor uitnodigingslinks en de sitemap. */
    public static function url(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Alleen interne paden toestaan als doel na inloggen. */
    public static function safeNext(mixed $next, string $fallback = '/account'): string
    {
        return is_string($next) && str_starts_with($next, '/') && ! str_starts_with($next, '//') ? $next : $fallback;
    }
}
