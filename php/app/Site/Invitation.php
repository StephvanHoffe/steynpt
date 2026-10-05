<?php

namespace App\Site;

use App\Models\User;
use App\Support\ReferralProgram;

/** Uitnodigingen van de vriendenactie: de code uit de URL of uit de uitnodigingscookie (60 dagen). */
final class Invitation
{
    public const COOKIE = 'steynpt_ref';

    public const COOKIE_MINUTES = 60 * 24 * 60;

    /** @return array{code: string, firstName: string}|null */
    public static function resolve(mixed $codeFromUrl = null): ?array
    {
        $raw = is_string($codeFromUrl) ? $codeFromUrl : request()->cookie(self::COOKIE);
        $code = ReferralProgram::normalizeReferralCode(is_string($raw) ? $raw : null);
        if (! $code) {
            return null;
        }
        $firstName = User::query()->where('referral_code', $code)->value('first_name');

        return $firstName ? ['code' => $code, 'firstName' => $firstName] : null;
    }
}
