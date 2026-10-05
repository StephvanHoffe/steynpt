<?php

declare(strict_types=1);

namespace App\Support;

use Normalizer;

/**
 * Vriendenactie voor online coaching. Beloningen zijn een voorstel en hier centraal aan te passen.
 */
final class ReferralProgram
{
    /** Wat de nieuwe klant krijgt bij aanmelding via een uitnodiging. */
    public const FRIEND_REWARD = '50% korting op de eerste maand online coaching';

    /** Wat de uitnodiger krijgt zodra de vriend daadwerkelijk start. */
    public const REFERRER_REWARD = '50% korting op een maand online coaching';

    public const HEADLINE = 'Samen 50% korting';

    /** Zelfde gegevens als het REFERRAL-object in TypeScript. */
    public const REFERRAL = [
        'friendReward' => self::FRIEND_REWARD,
        'referrerReward' => self::REFERRER_REWARD,
        'headline' => self::HEADLINE,
    ];

    /** Cookie met de uitnodigingscode (src/lib/constants.ts: REFERRAL_COOKIE). */
    public const REFERRAL_COOKIE = 'steynpt_ref';

    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Uitnodigingscode op basis van de voornaam, bijvoorbeeld "ZOEANNE-K7QM".
     *
     * @param  (callable(): float)|null  $random  geeft een getal in [0, 1), zoals Math.random
     */
    public static function makeReferralCode(string $firstName, ?callable $random = null): string
    {
        $random ??= static fn (): float => random_int(0, (1 << 53) - 1) / (1 << 53);
        $decomposed = class_exists(Normalizer::class) ? (Normalizer::normalize($firstName, Normalizer::FORM_D) ?: $firstName) : $firstName;
        $base = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $decomposed) ?? ''), 0, 8);
        if ($base === '') {
            $base = 'STEYN';
        }
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= self::CODE_ALPHABET[(int) floor($random() * strlen(self::CODE_ALPHABET))];
        }

        return "{$base}-{$suffix}";
    }

    public static function normalizeReferralCode(?string $code): ?string
    {
        $clean = mb_strtoupper(Js::trim($code ?? ''), 'UTF-8');

        return preg_match('/^[A-Z]{1,8}-[A-Z0-9]{4}$/D', $clean) ? $clean : null;
    }
}
