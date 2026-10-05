<?php

namespace App\Http\Middleware;

use App\Auth\Accounts;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ingelogd met tweestapsverificatie. Een verlopen wachtwoord (8 weken) moet eerst vernieuwd worden.
 * Zonder sessie: naar de inlogpagina, met de pagina waar het lid heen wilde.
 */
class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $target = $request->isMethod('GET') ? $request->getRequestUri() : '/account';
        // Een sessie van vóór de tweestapsverificatie (of na een reset ervan) telt niet meer.
        if ($user && ! $user->totp_enabled_at) {
            Accounts::logout();
            $user = null;
        }
        if (! $user) {
            return redirect('/inloggen?next='.rawurlencode($target));
        }
        if (Accounts::passwordExpired($user)) {
            return redirect('/wachtwoord-vernieuwen?next='.rawurlencode($target));
        }

        return $next($request);
    }
}
