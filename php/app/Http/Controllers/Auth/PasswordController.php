<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Accounts;
use App\Auth\Passwords;
use App\Http\Controllers\Controller;
use App\Site\Site;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Om de 8 weken een nieuw wachtwoord; bij een verlopen wachtwoord kom je hier automatisch terecht. */
class PasswordController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->totp_enabled_at) {
            return redirect('/inloggen?next=/wachtwoord-vernieuwen');
        }
        $changed = Accounts::passwordChangedAt($user);

        return view('auth.renew-password', [
            'expired' => Accounts::passwordExpired($user),
            'changed' => $changed,
            'expires' => Totp::passwordExpiresAt($changed),
            'weeks' => intdiv(Totp::PASSWORD_MAX_AGE_DAYS, 7),
            'next' => Site::safeNext($request->query('next'), $user->isAdmin() ? '/admin' : '/account'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Hier geen 'member'-middleware: die stuurt bij een verlopen wachtwoord juist naar deze pagina.
        $user = Auth::user();
        if (! $user || ! $user->totp_enabled_at) {
            return redirect('/inloggen');
        }
        Passwords::setNew($user, $request);

        return redirect(Site::safeNext($request->input('next'), $user->isAdmin() ? '/admin' : '/account'));
    }
}
