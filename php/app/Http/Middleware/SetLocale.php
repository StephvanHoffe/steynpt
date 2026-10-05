<?php

namespace App\Http\Middleware;

use App\Site\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kiest de taal van het verzoek (zie App\Site\Locale). Draait als globale middleware, zodat ook een 404 van een
 * Engels adres in het Engels is; na het inloggen kiest App\Http\Middleware\UserLocale de taal van het lid.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.trim($request->path(), '/');
        $fromPath = Locale::fromPath($path);
        $cookie = $request->cookies->get(Locale::COOKIE);

        $locale = match (true) {
            $path === '/admin' || str_starts_with($path, '/admin/') => Locale::DEFAULT,
            $fromPath !== null => $fromPath,
            Locale::supported($cookie) => $cookie,
            default => Locale::DEFAULT,
        };
        App::setLocale($locale);

        $response = $next($request);

        // Een openbare pagina in een taal bekijken = die taal kiezen (voor inloggen en registreren daarna).
        if ($fromPath !== null && $cookie !== $fromPath) {
            $response->headers->setCookie(self::cookie($fromPath));
        }

        return $response;
    }

    public static function cookie(string $locale): Cookie
    {
        return Cookie::create(Locale::COOKIE, $locale, now()->addYear(), '/', null, config('session.secure'), false, false, 'lax');
    }
}
