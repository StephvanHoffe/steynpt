<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Tweestapsverificatie met een authenticator-app (TOTP, RFC 6238): 6 cijfers, elke 30 seconden een nieuwe code.
 * Puur (geen database), zodat het los te testen is.
 *
 * Tijdstippen ($now) mogen een Carbon-object zijn of milliseconden sinds 1970 (zoals Date.now()).
 */
final class Totp
{
    public const TOTP_PERIOD = 30;

    /** Wachtwoordbeleid: om de 8 weken een nieuw wachtwoord. */
    public const PASSWORD_MAX_AGE_DAYS = 56;

    private const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Herstelcodes: eenmalig te gebruiken als de telefoon met de app kwijt is. */
    private const RECOVERY_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const DAY = 24 * 60 * 60 * 1000;

    /** @param  string  $bytes  ruwe bytes */
    public static function base32Encode(string $bytes): string
    {
        $bits = 0;
        $value = 0;
        $out = '';
        foreach (str_split($bytes) as $char) {
            if ($char === '') {
                continue;
            }
            $value = (($value << 8) | ord($char)) & 0xFFFFFFFF;
            $bits += 8;
            while ($bits >= 5) {
                $out .= self::ALPHABET[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        if ($bits > 0) {
            $out .= self::ALPHABET[($value << (5 - $bits)) & 31];
        }

        return $out;
    }

    /**
     * @return string ruwe bytes
     *
     * @throws InvalidArgumentException bij tekens die geen base32 zijn
     */
    public static function base32Decode(string $input): string
    {
        $clean = preg_replace('/[\s'.Js::SPACE.'=-]/u', '', mb_strtoupper($input, 'UTF-8')) ?? '';
        $bits = 0;
        $value = 0;
        $out = '';
        foreach (mb_str_split($clean, 1, 'UTF-8') as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false || strlen($char) !== 1) {
                throw new InvalidArgumentException('Ongeldige base32-tekst');
            }
            $value = (($value << 5) | $index) & 0xFFFFFFFF;
            $bits += 5;
            if ($bits >= 8) {
                $out .= chr(($value >> ($bits - 8)) & 255);
                $bits -= 8;
            }
        }

        return $out;
    }

    /** Nieuw geheim van 160 bits, zoals authenticator-apps verwachten. */
    public static function generateTotpSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** Geheim in groepjes van vier, makkelijker over te typen. */
    public static function formatSecret(string $secret): string
    {
        return Js::trim(preg_replace('/(.{4})/u', '$1 ', $secret) ?? $secret);
    }

    public static function hotp(string $secret, int $counter, int $digits = self::DIGITS): string
    {
        $hmac = hash_hmac('sha1', pack('J', $counter), self::base32Decode($secret), true);
        $offset = ord($hmac[strlen($hmac) - 1]) & 15;
        $binary = (unpack('N', substr($hmac, $offset, 4))[1] & 0x7FFFFFFF) % (10 ** $digits);

        return str_pad((string) $binary, $digits, '0', STR_PAD_LEFT);
    }

    /** @param  CarbonInterface|int|null  $now  tijdstip, of milliseconden sinds 1970 */
    public static function totpStep(CarbonInterface|int|null $now = null): int
    {
        return (int) floor(self::nowMs($now) / 1000 / self::TOTP_PERIOD);
    }

    public static function totp(string $secret, CarbonInterface|int|null $now = null): string
    {
        return self::hotp($secret, self::totpStep($now));
    }

    /**
     * Controleert een code met een halve minuut speling naar voren en achteren (klokverschil).
     * Een code die al eens is gebruikt (stap <= lastStep) wordt geweigerd. Geeft de gebruikte stap terug, of null.
     */
    public static function verifyTotp(string $secret, string $code, CarbonInterface|int|null $now = null, ?int $lastStep = null): ?int
    {
        $clean = preg_replace('/['.Js::SPACE.']/u', '', $code) ?? $code;
        if (! preg_match('/^\d{6}$/D', $clean)) {
            return null;
        }
        $current = self::totpStep($now);
        foreach ([$current, $current - 1, $current + 1] as $step) {
            if ($lastStep !== null && $step <= $lastStep) {
                continue;
            }
            $expected = self::hotp($secret, $step);
            if (hash_equals($expected, $clean)) {
                return $step;
            }
        }

        return null;
    }

    /** Link voor de QR-code: de app toont "SteynPT" met het e-mailadres. */
    public static function otpauthUri(string $secret, string $account, string $issuer = 'SteynPT'): string
    {
        $label = Js::encodeURIComponent("{$issuer}:{$account}");

        return "otpauth://totp/{$label}?secret={$secret}&issuer=".Js::encodeURIComponent($issuer)
            .'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::TOTP_PERIOD;
    }

    /** @return list<string> codes als "abcde-fghjk" */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $chars = '';
            foreach (str_split(random_bytes(10)) as $byte) {
                $chars .= self::RECOVERY_ALPHABET[ord($byte) % strlen(self::RECOVERY_ALPHABET)];
            }
            $codes[] = substr($chars, 0, 5).'-'.substr($chars, 5);
        }

        return $codes;
    }

    public static function normalizeRecoveryCode(string $code): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($code, 'UTF-8')) ?? '';
    }

    public static function isRecoveryCodeFormat(string $code): bool
    {
        return strlen(self::normalizeRecoveryCode($code)) === 10;
    }

    public static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', self::normalizeRecoveryCode($code));
    }

    public static function passwordExpiresAt(CarbonInterface $changedAt): CarbonImmutable
    {
        return Js::fromMs(Js::ms($changedAt) + self::PASSWORD_MAX_AGE_DAYS * self::DAY);
    }

    /** Dagen tot het wachtwoord verloopt (0 of minder = verlopen). */
    public static function passwordDaysLeft(CarbonInterface $changedAt, ?CarbonInterface $now = null): int
    {
        $now ??= CarbonImmutable::now('UTC');

        return (int) ceil((Js::ms(self::passwordExpiresAt($changedAt)) - Js::ms($now)) / self::DAY);
    }

    private static function nowMs(CarbonInterface|int|null $now): int
    {
        return match (true) {
            $now === null => Js::ms(CarbonImmutable::now('UTC')),
            is_int($now) => $now,
            default => Js::ms($now),
        };
    }
}
