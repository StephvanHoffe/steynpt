<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Accounts;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Site\Invitation;
use App\Site\Locale;
use App\Site\Site;
use App\Site\Texts;
use App\Support\ReferralProgram;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::user()?->totp_enabled_at) {
            return redirect('/account');
        }
        $plan = $request->query('plan');

        return view('auth.register', [
            'invitation' => Invitation::resolve($request->query('ref')),
            'plan' => is_string($plan) && Site::isOnlinePlan($plan) ? $plan : '',
            'plans' => Texts::onlinePlans(),
            'friendReward' => Texts::get('algemeen')['vriendenactie']['friendReward'],
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
            'goal' => ['required', Rule::in(array_column(Site::GOALS, 'id'))],
            'plan' => ['nullable', 'string'],
            'referralCode' => ['nullable', 'string'],
            'terms' => ['required', 'in:on'],
            'marketing' => ['nullable', 'in:on'],
        ], [
            'firstName.required' => __('Vul je voornaam in'),
            'firstName.max' => __('Je voornaam mag maximaal 60 tekens hebben'),
            'lastName.required' => __('Vul je achternaam in'),
            'lastName.max' => __('Je achternaam mag maximaal 80 tekens hebben'),
            'email.required' => __('Vul een geldig e-mailadres in'),
            'email.email' => __('Vul een geldig e-mailadres in'),
            'email.max' => __('Vul een geldig e-mailadres in'),
            'phone.max' => __('Je telefoonnummer mag maximaal 30 tekens hebben'),
            'password.required' => __('Kies een wachtwoord van minimaal 8 tekens'),
            'password.min' => __('Kies een wachtwoord van minimaal 8 tekens'),
            'password.max' => __('Je wachtwoord mag maximaal 200 tekens hebben'),
            'goal.required' => __('Kies je belangrijkste doel'),
            'goal.in' => __('Kies je belangrijkste doel'),
            'terms.required' => __('Geef toestemming om je account aan te maken'),
            'terms.in' => __('Geef toestemming om je account aan te maken'),
        ]);
        $email = strtolower($data['email']);
        $back = fn (array $errors) => back()->withInput($request->except('password'))->withErrors($errors);

        if (User::query()->where('email', $email)->exists()) {
            return $back(['email' => __('Er bestaat al een account met dit e-mailadres. Log in om verder te gaan.')]);
        }

        $code = ReferralProgram::normalizeReferralCode($data['referralCode'] ?? null);
        $referrer = $code ? User::query()->where('referral_code', $code)->first() : null;
        if (trim((string) ($data['referralCode'] ?? '')) !== '' && ! $referrer) {
            return $back(['referralCode' => __('Deze uitnodigingscode kennen we niet. Controleer de code of laat het veld leeg.')]);
        }

        $plan = Site::isOnlinePlan($data['plan'] ?? null) ? $data['plan'] : null;
        $user = null;
        // Een nieuwe uitnodigingscode kan (heel zelden) al bestaan: dan een andere proberen.
        for ($attempt = 0; $attempt < 5 && ! $user; $attempt++) {
            try {
                $user = User::query()->create([
                    'email' => $email,
                    'password' => $data['password'],
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'phone' => ($data['phone'] ?? null) ?: null,
                    'goal' => $data['goal'],
                    // Wie zich op de Engelse site aanmeldt, krijgt Mijn omgeving in het Engels.
                    'locale' => Locale::current(),
                    'plan' => $plan,
                    'coaching_status' => $plan ? 'aangevraagd' : 'geen',
                    'referral_code' => ReferralProgram::makeReferralCode($data['firstName']),
                    'referred_by_id' => $referrer?->id,
                    'role' => Accounts::isAdminEmail($email) ? 'admin' : 'member',
                    'marketing_opt_in' => ($data['marketing'] ?? null) === 'on',
                    'password_changed_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Botsing op e-mail (gelijktijdige registratie) of op de uitnodigingscode.
                if (User::query()->where('email', $email)->exists()) {
                    return $back(['email' => __('Er bestaat al een account met dit e-mailadres.')]);
                }
            }
        }
        if (! $user) {
            return back()->withInput($request->except('password'))->with('error', __('Er ging iets mis bij het aanmaken van je account. Probeer het opnieuw.'));
        }

        Cookie::queue(Cookie::forget(Invitation::COOKIE));
        // Eerst de tweestapsverificatie instellen, daarna is het account klaar voor gebruik.
        Accounts::startChallenge($user, '/account?welkom=1');

        return redirect('/inloggen/verificatie');
    }
}
