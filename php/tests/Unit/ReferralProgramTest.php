<?php

namespace Tests\Unit;

use App\Support\ReferralProgram;
use PHPUnit\Framework\TestCase;

class ReferralProgramTest extends TestCase
{
    public function test_uitnodigingscodes(): void
    {
        $this->assertSame('ZOEANNE-AAAA', ReferralProgram::makeReferralCode('Zoë-Anne', fn () => 0));
        $this->assertSame('ZOEANNE-AAAA', ReferralProgram::normalizeReferralCode(' zoeanne-aaaa '));
        $this->assertNull(ReferralProgram::normalizeReferralCode('bad code'));
        $this->assertSame('STEYN-AAAA', ReferralProgram::makeReferralCode('', fn () => 0));
    }

    public function test_uitnodigingscodes_randgevallen(): void
    {
        $this->assertSame('ABCDEFGH-A9SB', ReferralProgram::makeReferralCode('abcdefghij', (function () {
            $values = [0, 0.999999, 0.5, 0.03125];

            return function () use (&$values) {
                return array_shift($values);
            };
        })()));
        $this->assertSame('STEYN-AAAA', ReferralProgram::makeReferralCode('李', fn () => 0));
        $this->assertMatchesRegularExpression('/^LISA-[A-HJ-NP-Z2-9]{4}$/', ReferralProgram::makeReferralCode('Lisa'));
        $this->assertNull(ReferralProgram::normalizeReferralCode(null));
        $this->assertNull(ReferralProgram::normalizeReferralCode("ZOEANNE-AAAA\n1"));
        $this->assertNull(ReferralProgram::normalizeReferralCode('ABCDEFGHI-AAAA'));
        $this->assertSame('A-1234', ReferralProgram::normalizeReferralCode('a-1234'));
    }

    public function test_beloningen(): void
    {
        $this->assertSame('Samen 50% korting', ReferralProgram::REFERRAL['headline']);
        $this->assertSame(ReferralProgram::FRIEND_REWARD, ReferralProgram::REFERRAL['friendReward']);
        $this->assertSame('50% korting op een maand online coaching', ReferralProgram::REFERRER_REWARD);
        $this->assertSame('steynpt_ref', ReferralProgram::REFERRAL_COOKIE);
    }
}
