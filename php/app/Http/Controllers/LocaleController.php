<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Site\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** De taalknop op pagina's met één adres (inloggen, registreren, Mijn omgeving). */
class LocaleController extends Controller
{
    public function switch(Request $request, string $taal): RedirectResponse
    {
        $back = (string) $request->query('terug', '/');
        // Alleen terug naar een pagina van deze site.
        if (! str_starts_with($back, '/') || str_starts_with($back, '//') || str_contains($back, '\\')) {
            $back = '/';
        }
        // Openbare pagina: naar dezelfde pagina in de gekozen taal.
        $split = strcspn($back, '?#');
        $dutch = Locale::dutchPath(substr($back, 0, $split));
        if ($dutch !== null) {
            $back = Locale::path($dutch, $taal).substr($back, $split);
        }

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $taal])->save();
        }

        return redirect($back)->withCookie(SetLocale::cookie($taal));
    }
}
