<?php

namespace App\Auth;

use App\Models\RecoveryCode;
use App\Models\User;
use App\Support\Totp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Inloggen in twee stappen, herstelcodes, wachtwoordtermijn en de limieten op inlogpogingen.
 * Gelijk aan src/lib/auth.ts en src/lib/two-factor.ts van de Next.js-versie.
 */
final class Accounts
{
    // Tussenstap na het wachtwoord: de code uit de authenticator-app. Staat in de (server)sessie.
    private const CHALLENGE = 'login_challenge';

    public const CHALLENGE_MINUTES = 15;

    public const MAX_CODE_ATTEMPTS = 5;

    // Onjuiste codes per lid: hooguit 6 per uur, ook verspreid over meerdere inlogpogingen.
    private const MAX_CODE_FAILURES = 6;

    // Onjuiste wachtwoorden per e-mailadres en IP-adres: hooguit 8 per kwartier.
    private const MAX_LOGIN_ATTEMPTS = 8;

    public const LOCKED = 'Te veel onjuiste codes. Probeer het over een uur opnieuw, of neem contact op met SteynPT.';

    public static function isAdminEmail(string $email): bool
    {
        return in_array(strtolower($email), config('steynpt.admin_emails'), true);
    }

    public static function passwordChangedAt(User $user): CarbonImmutable
    {
        return CarbonImmutable::parse($user->password_changed_at ?? $user->created_at);
    }

    /** Om de 8 weken een nieuw wachtwoord: verlopen als er 0 of minder dagen over zijn. */
    public static function passwordExpired(User $user, ?CarbonImmutable $now = null): bool
    {
        return Totp::passwordDaysLeft(self::passwordChangedAt($user), $now ?? CarbonImmutable::now()) <= 0;
    }

    // --- Tussenstap bij het inloggen -------------------------------------------------------------

    public static function startChallenge(User $user, string $next): void
    {
        session()->put(self::CHALLENGE, [
            'user_id' => $user->id,
            'next' => $next,
            'pending_secret' => null,
            'attempts' => 0,
            'codes' => null,
            'expires_at' => now()->addMinutes(self::CHALLENGE_MINUTES)->getTimestamp(),
        ]);
    }

    /** @return array{user: User, next: string, pending_secret: ?string, attempts: int, codes: ?array}|null */
    public static function challenge(): ?array
    {
        $challenge = session(self::CHALLENGE);
        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }
        $user = User::query()->find($challenge['user_id']);

        return $user ? ['user' => $user] + $challenge : null;
    }

    public static function updateChallenge(array $changes): void
    {
        $challenge = session(self::CHALLENGE);
        if (is_array($challenge)) {
            session()->put(self::CHALLENGE, array_merge($challenge, $changes));
        }
    }

    public static function clearChallenge(): void
    {
        session()->forget(self::CHALLENGE);
    }

    /** Inloggen: nieuwe sessie-id tegen sessie-fixatie. */
    public static function login(User $user): void
    {
        self::clearChallenge();
        Auth::login($user);
        session()->regenerate();
    }

    public static function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    /** Alle andere sessies van een lid beëindigen (na een nieuw wachtwoord). */
    public static function endOtherSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', session()->getId())->delete();
    }

    // --- Codes -----------------------------------------------------------------------------------

    /** Code uit de app (6 cijfers) of een herstelcode. Werkt de laatst gebruikte tijdstap bij. */
    public static function checkCode(User $user, string $raw): bool
    {
        $code = trim($raw);
        if ($user->totp_secret && preg_match('/^\d{3}\s?\d{3}$/', $code)) {
            $step = Totp::verifyTotp($user->totp_secret, $code, null, $user->totp_last_step);
            if ($step === null) {
                return false;
            }

            // Alleen als niemand deze code intussen al gebruikte (twee keer tegelijk versturen).
            return User::query()->whereKey($user->id)
                ->where(fn ($q) => $q->whereNull('totp_last_step')->orWhere('totp_last_step', '<', $step))
                ->update(['totp_last_step' => $step]) > 0;
        }
        if (Totp::isRecoveryCodeFormat($code)) {
            return RecoveryCode::query()
                ->where('user_id', $user->id)
                ->where('code_hash', Totp::hashRecoveryCode($code))
                ->whereNull('used_at')
                ->update(['used_at' => now()]) > 0;
        }

        return false;
    }

    /** Nieuwe herstelcodes: de oude vervallen. Alleen de hashes worden bewaard; de codes zelf zie je één keer. */
    public static function replaceRecoveryCodes(User $user): array
    {
        $codes = Totp::generateRecoveryCodes();
        DB::transaction(function () use ($user, $codes) {
            RecoveryCode::query()->where('user_id', $user->id)->delete();
            foreach ($codes as $code) {
                RecoveryCode::query()->create(['user_id' => $user->id, 'code_hash' => Totp::hashRecoveryCode($code)]);
            }
        });

        return $codes;
    }

    /** Tweestapsverificatie uitzetten en overal uitloggen; bij de volgende keer inloggen stelt het lid hem opnieuw in. */
    public static function clearTwoFactor(User $user): void
    {
        DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->update(['totp_secret' => null, 'totp_enabled_at' => null, 'totp_last_step' => null]);
            RecoveryCode::query()->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

    public static function remainingRecoveryCodes(User $user): int
    {
        return RecoveryCode::query()->where('user_id', $user->id)->whereNull('used_at')->count();
    }

    // --- Limieten --------------------------------------------------------------------------------

    public static function codeLocked(User $user): bool
    {
        return RateLimiter::tooManyAttempts("2fa:{$user->id}", self::MAX_CODE_FAILURES);
    }

    public static function registerCodeFailure(User $user): void
    {
        RateLimiter::hit("2fa:{$user->id}", 3600);
    }

    public static function clearCodeFailures(User $user): void
    {
        RateLimiter::clear("2fa:{$user->id}");
    }

    public static function loginLimited(string $key): bool
    {
        return RateLimiter::tooManyAttempts("login:{$key}", self::MAX_LOGIN_ATTEMPTS);
    }

    public static function registerLoginFailure(string $key): void
    {
        RateLimiter::hit("login:{$key}", 15 * 60);
    }

    public static function clearLoginFailures(string $key): void
    {
        RateLimiter::clear("login:{$key}");
    }
}
