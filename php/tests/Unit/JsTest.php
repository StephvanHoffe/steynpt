<?php

namespace Tests\Unit;

use App\Support\Js;
use PHPUnit\Framework\TestCase;

/** De JavaScript-hulpjes waarop de overgezette Support-klassen leunen. */
class JsTest extends TestCase
{
    public function test_number_coercion_zoals_javascript(): void
    {
        $cases = [
            ['', 0.0], [' 12 ', 12.0], ['1.', 1.0], ['.5', 0.5], ['1e3', 1000.0], ['+5', 5.0], ['0x1F', 31.0], ['0o17', 15.0], ['0b101', 5.0],
            ["\u{00A0}12\u{2028}", 12.0], ['0012', 12.0],
        ];
        foreach ($cases as [$in, $out]) {
            $this->assertSame($out, Js::toNumber($in), json_encode($in));
        }
        foreach (['.', '-0x1F', '1_000', '1e', 'e3', '12abc', '1,5', 'NaN', 'infinity'] as $in) {
            $this->assertNan(Js::toNumber($in), $in);
        }
        $this->assertSame(INF, Js::toNumber('Infinity'));
        $this->assertSame(0.0, Js::toNumber(null));
        $this->assertSame(1.0, Js::toNumber(true));
        $this->assertSame(0.0, Js::toNumber([]));
        $this->assertSame(75.0, Js::toNumber(['75']));
        $this->assertNan(Js::toNumber([1, 2]));
        $this->assertNan(Js::toNumber(['a' => 1]));
    }

    public function test_getal_naar_tekst_zoals_javascript(): void
    {
        $this->assertSame('0.30000000000000004', Js::numberToString(0.1 + 0.2));
        $this->assertSame('72.5', Js::numberToString(72.5));
        $this->assertSame('100', Js::numberToString(100.0));
        $this->assertSame('1e+21', Js::numberToString(1e21));
        $this->assertSame('100000000000000000000', Js::numberToString(1e20));
        $this->assertSame('1.5e-7', Js::numberToString(1.5e-7));
        $this->assertSame('0.000001', Js::numberToString(0.000001));
        $this->assertSame('-3.5', Js::numberToString(-3.5));
        $this->assertSame('NaN', Js::numberToString(NAN));
        $this->assertSame('0', Js::numberToString(-0.0));
    }

    public function test_afronden_trim_en_uri(): void
    {
        $this->assertSame(3.0, Js::round(2.5));
        $this->assertSame(-2.0, Js::round(-2.5));
        $this->assertSame(0.0, Js::round(0.49999999999999994));
        $this->assertSame('x', Js::trim("\u{00A0} x \u{2028}\u{FEFF}"));
        $this->assertSame("a%20b%2Bc!'()*~%40x", Js::encodeURIComponent("a b+c!'()*~@x"));
    }

    public function test_datums_zoals_date_utc(): void
    {
        $this->assertSame('2027-01-01', Js::isoDate(Js::dateUtc(2026, 12, 1)));
        $this->assertSame('2025-12-01', Js::isoDate(Js::dateUtc(2026, -1, 1)));
        $this->assertSame('2026-03-02', Js::isoDate(Js::dateUtc(2026, 1, 30)));
        $this->assertSame('1950-01-02', Js::isoDate(Js::dateUtc(50, 0, 2)), 'jaar 0-99 wordt 1900-1999');
        $this->assertSame(4, Js::utcDay(0), '1 januari 1970 was een donderdag');
        $this->assertNull(Js::parseDay('2026-02-32'));
        $this->assertSame(Js::dateUtc(2026, 2, 2), Js::parseDay('2026-02-30'));
    }
}
