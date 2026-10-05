<?php

namespace Tests\Unit;

use App\Support\Totp;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /** Geheim uit de testvectoren van RFC 6238/4226: "12345678901234567890". */
    private static function rfcSecret(): string
    {
        return Totp::base32Encode('12345678901234567890');
    }

    public function test_totp_base32_heen_en_terug(): void
    {
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', self::rfcSecret());
        $this->assertSame('12345678901234567890', Totp::base32Decode(self::rfcSecret()));
        $this->assertSame('12345678901234567890', Totp::base32Decode('gezd gnbv gy3t qojq gezd gnbv gy3t qojq'), 'spaties en kleine letters mogen');
        $secret = Totp::generateTotpSecret();
        $this->assertSame(32, strlen($secret));
        $this->assertSame(20, strlen(Totp::base32Decode($secret)));
    }

    public function test_totp_ongeldige_base32_en_opmaak(): void
    {
        $this->assertSame('foo', Totp::base32Decode('MZXW6==='));
        $this->assertSame('foo', Totp::base32Decode('MZ-XW6'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ongeldige base32-tekst');
        Totp::base32Decode('ABC1');
    }

    public function test_totp_geheim_in_groepjes(): void
    {
        $this->assertSame('GEZD GNBV GY3T QOJQ GEZD GNBV GY3T QOJQ', Totp::formatSecret('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'));
        $this->assertSame('ABC', Totp::formatSecret('ABC'));
    }

    public function test_totp_testvectoren_uit_de_rfc(): void
    {
        $this->assertSame('755224', Totp::hotp(self::rfcSecret(), 0));
        $this->assertSame('520489', Totp::hotp(self::rfcSecret(), 9));
        $this->assertSame('287082', Totp::totp(self::rfcSecret(), 59_000));
        $this->assertSame('081804', Totp::totp(self::rfcSecret(), 1111111109_000));
        $this->assertSame('279037', Totp::totp(self::rfcSecret(), 2000000000_000));
        $this->assertSame('081804', Totp::totp(self::rfcSecret(), CarbonImmutable::createFromTimestampUTC(1111111109)), 'ook met een Carbon-tijdstip');
    }

    public function test_totp_controleren_met_speling_en_zonder_hergebruik(): void
    {
        $secret = self::rfcSecret();
        $now = 1111111109_000;
        $step = intdiv($now, 30_000);
        $this->assertSame($step, Totp::verifyTotp($secret, '081804', now: $now));
        $this->assertSame($step, Totp::verifyTotp($secret, '081 804', now: $now), 'spatie mag');
        $this->assertSame($step - 1, Totp::verifyTotp($secret, Totp::totp($secret, $now - 30_000), now: $now), 'vorige code nog geldig');
        $this->assertSame($step + 1, Totp::verifyTotp($secret, Totp::totp($secret, $now + 30_000), now: $now), 'klok iets achter');
        $this->assertNull(Totp::verifyTotp($secret, Totp::totp($secret, $now - 90_000), now: $now), 'te oud');
        $this->assertNull(Totp::verifyTotp($secret, '081804', now: $now, lastStep: $step), 'al gebruikt');
        $this->assertNull(Totp::verifyTotp($secret, '12345', now: $now));
        $this->assertNull(Totp::verifyTotp($secret, 'abcdef', now: $now));
        $this->assertSame($step, Totp::verifyTotp($secret, " 081804\n", now: $now), 'witruimte wordt weggehaald');
        $this->assertNull(Totp::verifyTotp($secret, '0818045', now: $now));
    }

    public function test_totp_herstelcodes_qr_link_en_wachtwoordtermijn(): void
    {
        $codes = Totp::generateRecoveryCodes();
        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
        $this->assertMatchesRegularExpression('/^[a-z2-9]{5}-[a-z2-9]{5}$/', $codes[0]);
        $this->assertTrue(Totp::isRecoveryCodeFormat(str_replace('-', ' ', strtoupper($codes[0]))));
        $this->assertSame(Totp::hashRecoveryCode($codes[0]), Totp::hashRecoveryCode(str_replace('-', '', strtoupper($codes[0]))));
        $this->assertFalse(Totp::isRecoveryCodeFormat('123456'));

        $this->assertSame(
            'otpauth://totp/SteynPT%3Alisa%40example.com?secret=ABC&issuer=SteynPT&algorithm=SHA1&digits=6&period=30',
            Totp::otpauthUri('ABC', 'lisa@example.com'),
        );

        $changed = CarbonImmutable::parse('2026-08-01T10:00:00Z');
        $this->assertSame(56, Totp::passwordDaysLeft($changed, CarbonImmutable::parse('2026-08-01T10:00:00Z')));
        $this->assertSame(1, Totp::passwordDaysLeft($changed, CarbonImmutable::parse('2026-09-25T11:00:00Z')), 'nog 23 uur');
        $this->assertSame(0, Totp::passwordDaysLeft($changed, CarbonImmutable::parse('2026-09-26T10:00:00Z')), 'precies 8 weken: verlopen');
        $this->assertSame('2026-09-26T10:00:00+00:00', Totp::passwordExpiresAt($changed)->toIso8601String());
    }
}
