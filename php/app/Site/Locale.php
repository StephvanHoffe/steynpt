<?php

namespace App\Site;

use App\Content\Values;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Taal van de site: Nederlands (standaard) of Engels.
 *
 * - Openbare pagina's hebben per taal een eigen adres (/personal-training en /en/personal-training), zodat Google
 *   beide versies kan vinden. Het adres bepaalt de taal.
 * - Inloggen, registreren en Mijn omgeving hebben één adres; daar geldt de taal van het lid (users.locale) of,
 *   zonder account, de laatst gekozen taal (cookie).
 * - Het beheer is altijd Nederlands.
 */
final class Locale
{
    public const SUPPORTED = ['nl', 'en'];

    public const DEFAULT = 'nl';

    public const COOKIE = 'taal';

    /** Openbare pagina's: Nederlands adres => Engels adres. */
    public const PATHS = [
        '/' => '/en',
        '/online-coaching' => '/en/online-coaching',
        '/personal-training' => '/en/personal-training',
        '/ademcoaching' => '/en/breathwork',
        '/voedingscoaching' => '/en/nutrition-coaching',
        '/tarieven' => '/en/pricing',
        '/over-steyn' => '/en/about-steyn',
        '/contact' => '/en/contact',
        '/vriend-uitnodigen' => '/en/refer-a-friend',
        '/privacy' => '/en/privacy',
    ];

    public static function current(): string
    {
        return App::getLocale() === 'en' ? 'en' : 'nl';
    }

    public static function isEnglish(): bool
    {
        return self::current() === 'en';
    }

    public static function supported(?string $locale): bool
    {
        return is_string($locale) && in_array($locale, self::SUPPORTED, true);
    }

    /**
     * Adres in de gevraagde (of huidige) taal: '/contact' → '/en/contact', '/personal-training#topsport' →
     * '/en/personal-training#topsport'. Adressen die geen openbare pagina zijn (zoals /registreren) blijven gelijk.
     */
    public static function path(string $path, ?string $locale = null): string
    {
        $locale ??= self::current();
        if ($locale !== 'en') {
            return $path;
        }
        $split = strcspn($path, '?#');
        $base = substr($path, 0, $split);

        return (self::PATHS[$base] ?? $base).substr($path, $split);
    }

    /** Het Nederlandse adres bij een openbaar adres in welke taal dan ook, of null als het geen openbare pagina is. */
    public static function dutchPath(string $path): ?string
    {
        $path = '/'.trim($path, '/');
        if (isset(self::PATHS[$path])) {
            return $path;
        }
        $dutch = array_search($path, self::PATHS, true);

        return $dutch === false ? null : $dutch;
    }

    /** De taal die bij een adres hoort: 'en' voor /en/…, 'nl' voor een Nederlandse openbare pagina, anders null. */
    public static function fromPath(string $path): ?string
    {
        $path = '/'.trim($path, '/');
        if ($path === '/en' || str_starts_with($path, '/en/')) {
            return 'en';
        }

        return isset(self::PATHS[$path]) ? 'nl' : null;
    }

    /** Het adres van deze pagina in de andere taal (voor de taalknop en hreflang). */
    public static function switchUrl(Request $request, string $to): string
    {
        $dutch = self::dutchPath($request->path());
        if ($dutch !== null) {
            return self::path($dutch, $to);
        }

        // Inloggen, registreren en Mijn omgeving: de taal wordt onthouden en je blijft op dezelfde pagina.
        return '/taal/'.$to.'?terug='.rawurlencode($request->getRequestUri());
    }

    /** Bedrag zoals het in de huidige taal hoort: "1.050" en "79,50" worden in het Engels "1,050" en "79.50". */
    public static function price(string $raw, ?string $locale = null): string
    {
        if (($locale ?? self::current()) !== 'en') {
            return $raw;
        }
        $n = Values::priceNumber($raw);
        if (is_nan($n)) {
            return $raw;
        }

        return number_format($n, floor($n) == $n ? 0 : 2, '.', ',');
    }

    /** Taalcode voor Open Graph en gestructureerde gegevens. */
    public static function tag(?string $locale = null): string
    {
        return ($locale ?? self::current()) === 'en' ? 'en' : 'nl-NL';
    }
}
