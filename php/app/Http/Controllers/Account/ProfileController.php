<?php

namespace App\Http\Controllers\Account;

use App\Auth\Accounts;
use App\Auth\Passwords;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Site\Locale;
use App\Site\Site;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $changed = Accounts::passwordChangedAt($user);

        return view('account.profile', [
            'user' => $user,
            'expires' => Totp::passwordExpiresAt($changed),
            'daysLeft' => Totp::passwordDaysLeft($changed),
            'codesLeft' => $user->totp_enabled_at ? Accounts::remainingRecoveryCodes($user) : 0,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('profile', [
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'goal' => ['required', Rule::in(array_column(Site::GOALS, 'id'))],
            // Taal van Mijn omgeving; zonder dit veld (oudere formulieren) blijft de taal gelijk.
            'locale' => ['nullable', Rule::in(Locale::SUPPORTED)],
            'marketing' => ['nullable', 'in:on'],
        ], [
            'firstName.required' => __('Vul je voornaam in'),
            'firstName.max' => __('Je voornaam mag maximaal 60 tekens hebben'),
            'lastName.required' => __('Vul je achternaam in'),
            'lastName.max' => __('Je achternaam mag maximaal 80 tekens hebben'),
            'phone.max' => __('Je telefoonnummer mag maximaal 30 tekens hebben'),
            'goal.required' => __('Kies je belangrijkste doel'),
            'goal.in' => __('Kies je belangrijkste doel'),
            'locale.in' => __('Kies een taal'),
        ]);
        $user = $request->user();
        $locale = ($data['locale'] ?? null) ?: ($user->locale ?: Locale::DEFAULT);
        $user->forceFill([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'phone' => ($data['phone'] ?? null) ?: null,
            'goal' => $data['goal'],
            'locale' => $locale,
            'marketing_opt_in' => ($data['marketing'] ?? null) === 'on',
        ])->save();

        // De melding al in de (nieuwe) taal; de taalkeuze ook onthouden voor als het lid uitlogt.
        return back()->with('profile_success', __('Je profiel is bijgewerkt.', [], $locale))
            ->withCookie(SetLocale::cookie($locale));
    }

    public function password(Request $request): RedirectResponse
    {
        try {
            Passwords::setNew($request->user(), $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors(), 'password');
        }

        return back()->with('password_success', __('Je wachtwoord is gewijzigd. Op andere apparaten moet je opnieuw inloggen.'));
    }

    /** Nieuwe herstelcodes, na bevestiging met een code uit de app. */
    public function regenerateCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (Accounts::codeLocked($user)) {
            return back()->with('regen_error', __(Accounts::LOCKED));
        }
        $code = (string) $request->input('code', '');
        if (! preg_match('/^\d{3}\s?\d{3}$/', trim($code)) || ! Accounts::checkCode($user, $code)) {
            Accounts::registerCodeFailure($user);

            return back()->withErrors(['code' => __('Vul de code in die je app nu toont.')], 'regen');
        }

        return back()->with('regen_codes', Accounts::replaceRecoveryCodes($user))
            ->with('regen_success', __('Nieuwe herstelcodes gemaakt. De oude werken niet meer.'));
    }

    /** Andere telefoon: tweestapsverificatie uit; bij de volgende keer inloggen stel je hem opnieuw in. */
    public function resetTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (Accounts::codeLocked($user)) {
            return back()->with('reset_error', __(Accounts::LOCKED));
        }
        if (! Accounts::checkCode($user, (string) $request->input('code', ''))) {
            Accounts::registerCodeFailure($user);

            return back()->withErrors(['code' => __('Deze code klopt niet.')], 'reset');
        }
        Accounts::clearTwoFactor($user);
        Accounts::logout();

        return redirect('/inloggen?melding=2fa-opnieuw');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($request->input('confirm') !== 'on') {
            return back()->withErrors(['confirm' => __('Bevestig dat je je account wilt verwijderen')], 'delete');
        }
        if (! Hash::check((string) $request->input('password'), $user->password)) {
            return back()->withErrors(['password' => __('Je wachtwoord klopt niet')], 'delete');
        }

        // Expliciet verwijderen, ook als foreign keys op de database uit staan.
        DB::transaction(function () use ($user) {
            foreach (['recovery_codes', 'check_ins', 'appointments', 'measurements', 'plans', 'intakes', 'sessions'] as $table) {
                DB::table($table)->where('user_id', $user->id)->delete();
            }
            User::query()->where('referred_by_id', $user->id)->update(['referred_by_id' => null]);
            $user->delete();
        });
        Accounts::logout();

        return redirect(Locale::path('/').'?account=verwijderd');
    }
}
