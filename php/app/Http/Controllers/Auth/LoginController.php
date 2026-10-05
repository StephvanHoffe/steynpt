<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Accounts;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Site\Locale;
use App\Site\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MELDING = [
        'verlopen' => 'Je inlogpoging is verlopen. Log opnieuw in met je e-mailadres en wachtwoord.',
        'te-veel-codes' => 'Te veel onjuiste codes. Log opnieuw in met je wachtwoord.',
        '2fa-opnieuw' => 'De tweestapsverificatie is uitgezet. Log opnieuw in om je (nieuwe) telefoon te koppelen.',
    ];

    public function show(Request $request): View|RedirectResponse
    {
        $next = $request->query('next');
        $target = Site::safeNext($next);
        $user = Auth::user();
        if ($user && $user->totp_enabled_at) {
            return redirect($target);
        }

        $melding = self::MELDING[(string) $request->query('melding')] ?? null;

        return view('auth.login', [
            'melding' => $melding !== null ? __($melding) : null,
            // Zonder next kiest het inloggen zelf: beheer voor Steyn, Mijn omgeving voor klanten.
            'next' => is_string($next) ? $target : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => __('Vul een geldig e-mailadres in'),
            'email.email' => __('Vul een geldig e-mailadres in'),
            'password.required' => __('Vul je wachtwoord in'),
        ]);
        $email = strtolower((string) $request->input('email'));
        $key = $email.'|'.$request->ip();
        if (Accounts::loginLimited($key)) {
            return back()->withInput($request->except('password'))->with('error', __('Te veel inlogpogingen. Probeer het over een kwartier opnieuw.'));
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            Accounts::registerLoginFailure($key);

            return back()->withInput($request->except('password'))->with('error', __('E-mailadres of wachtwoord klopt niet.'));
        }
        Accounts::clearLoginFailures($key);

        if (! $user->isAdmin() && Accounts::isAdminEmail($user->email)) {
            $user->forceFill(['role' => 'admin'])->save();
        }

        // Tweede stap: de code uit de authenticator-app (of eerst instellen).
        Accounts::startChallenge($user, Site::safeNext($request->input('next'), $user->isAdmin() ? '/admin' : '/account'));

        return redirect('/inloggen/verificatie');
    }

    /** Uitloggen via een gewone POST (met CSRF-token). Daarna naar de homepage in de taal van het lid. */
    public function logout(): RedirectResponse
    {
        Accounts::logout();

        return redirect(Locale::path('/'));
    }
}
