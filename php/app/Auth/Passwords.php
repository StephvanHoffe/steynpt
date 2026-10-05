<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Nieuw wachtwoord opslaan: de termijn van 8 weken begint opnieuw en andere apparaten worden uitgelogd. */
final class Passwords
{
    /** @throws ValidationException */
    public static function setNew(User $user, Request $request): void
    {
        $validator = Validator::make($request->only('current', 'password', 'confirm'), [
            'current' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
            'confirm' => ['nullable', 'string'],
        ], [
            'current.required' => 'Vul je huidige wachtwoord in',
            'password.required' => 'Kies een wachtwoord van minimaal 8 tekens',
            'password.min' => 'Kies een wachtwoord van minimaal 8 tekens',
            'password.max' => 'Je wachtwoord mag maximaal 200 tekens hebben',
        ]);
        $validator->after(function ($v) use ($request) {
            if ($v->errors()->has('password')) {
                return;
            }
            if ($request->input('password') !== $request->input('confirm')) {
                $v->errors()->add('confirm', 'De wachtwoorden komen niet overeen');
            } elseif ($request->input('password') === $request->input('current')) {
                $v->errors()->add('password', 'Kies een ander wachtwoord dan je huidige');
            }
        });
        $validator->validate();

        if (! Hash::check((string) $request->input('current'), $user->password)) {
            throw ValidationException::withMessages(['current' => 'Je huidige wachtwoord klopt niet']);
        }
        $user->forceFill(['password' => $request->input('password'), 'password_changed_at' => now()])->save();
        Accounts::endOtherSessions($user);
    }
}
