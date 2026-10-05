<?php

declare(strict_types=1);

namespace App\Content;

use App\Content\Pages\Ademcoaching;
use App\Content\Pages\Algemeen;
use App\Content\Pages\Contact;
use App\Content\Pages\Home;
use App\Content\Pages\OnlineCoaching;
use App\Content\Pages\OverSteyn;
use App\Content\Pages\Pakketten;
use App\Content\Pages\PersonalTraining;
use App\Content\Pages\Privacy;
use App\Content\Pages\Tarieven;
use App\Content\Pages\Voedingscoaching;
use App\Content\Pages\VriendUitnodigen;
use InvalidArgumentException;

/**
 * Alle pagina's met aanpasbare teksten, in de volgorde van het beheer.
 * Elke pagina is een klasse in App\Content\Pages met ::page() (de definitie) en ::SLUG.
 */
final class Registry
{
    /** Pagina's van de site, in de volgorde van het menu. */
    public const SITE_PAGES = [
        Home::class,
        OnlineCoaching::class,
        PersonalTraining::class,
        Ademcoaching::class,
        Voedingscoaching::class,
        Tarieven::class,
        OverSteyn::class,
        VriendUitnodigen::class,
        Contact::class,
        Privacy::class,
    ];

    /** Teksten die op meerdere pagina's staan. */
    public const SHARED_PAGES = [Algemeen::class, Pakketten::class];

    /** @return list<array> definities van de pagina's van de site */
    public static function sitePages(): array
    {
        return array_map(fn (string $class) => $class::page(), self::SITE_PAGES);
    }

    /** @return list<array> definities van de teksten die op meerdere pagina's staan */
    public static function sharedPages(): array
    {
        return array_map(fn (string $class) => $class::page(), self::SHARED_PAGES);
    }

    /** @return list<array> alle definities: eerst de site, dan de gedeelde teksten */
    public static function all(): array
    {
        return [...self::sitePages(), ...self::sharedPages()];
    }

    public static function find(string $slug): ?array
    {
        foreach ([...self::SITE_PAGES, ...self::SHARED_PAGES] as $class) {
            if ($class::SLUG === $slug) {
                return $class::page();
            }
        }

        return null;
    }

    /** Zoals find(), maar een onbekende pagina is een fout in de code. */
    public static function get(string $slug): array
    {
        return self::find($slug) ?? throw new InvalidArgumentException("Onbekende pagina met teksten: {$slug}");
    }
}
