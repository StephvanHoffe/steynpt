<?php

namespace App\Http\Middleware;

use App\Site\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inloggen, registreren en Mijn omgeving: een ingelogd lid ziet zijn eigen taal (users.locale), ook op een nieuw
 * apparaat. Openbare pagina's volgen het adres en het beheer blijft Nederlands (zie SetLocale).
 */
class UserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.trim($request->path(), '/');
        $user = $request->user();
        if ($user && Locale::fromPath($path) === null && ! ($path === '/admin' || str_starts_with($path, '/admin/')) && Locale::supported($user->locale)) {
            App::setLocale($user->locale);
        }

        return $next($request);
    }
}
