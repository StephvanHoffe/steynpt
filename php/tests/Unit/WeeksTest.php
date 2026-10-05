<?php

namespace Tests\Unit;

use App\Support\Weeks;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class WeeksTest extends TestCase
{
    public function test_iso_week_key_volgt_iso_8601(): void
    {
        $this->assertSame('2026-W40', Weeks::isoWeekKey(CarbonImmutable::create(2026, 10, 1)));
        $this->assertSame('2026-W53', Weeks::isoWeekKey(CarbonImmutable::create(2027, 1, 1)));
        $this->assertSame('2026-W01', Weeks::isoWeekKey(CarbonImmutable::create(2025, 12, 29)));
    }

    public function test_iso_week_key_gebruikt_de_datum_in_de_eigen_tijdzone(): void
    {
        // Maandag 00:30 in Amsterdam is in UTC nog zondag (vorige week).
        $this->assertSame('2026-W41', Weeks::isoWeekKey(CarbonImmutable::create(2026, 10, 5, 0, 30, 0, 'Europe/Amsterdam')));
        $this->assertSame('2026-W40', Weeks::isoWeekKey(CarbonImmutable::create(2026, 10, 5, 0, 30, 0, 'Europe/Amsterdam')->utc()));
    }

    public function test_streak_telt_aaneengesloten_weken(): void
    {
        $now = CarbonImmutable::create(2026, 10, 1);
        $this->assertSame(0, Weeks::checkInStreak([], $now));
        $this->assertSame(3, Weeks::checkInStreak(['2026-W40', '2026-W39', '2026-W38'], $now));
        $this->assertSame(2, Weeks::checkInStreak(['2026-W39', '2026-W38'], $now));
        $this->assertSame(1, Weeks::checkInStreak(['2026-W40', '2026-W38'], $now));
    }
}
