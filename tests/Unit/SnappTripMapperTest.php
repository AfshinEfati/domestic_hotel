<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper\SnappTripMapper;
use PHPUnit\Framework\TestCase;

class SnappTripMapperTest extends TestCase
{
    public function test_calendar_prices_are_normalized_to_irr_and_explicit_zero_child_price_is_preserved(): void
    {
        $mapped = (new SnappTripMapper())->hotelCalendar([
            'rooms' => [[
                'id' => 18607,
                'daily' => [
                    '2026-10-10' => [
                        'availability' => 2,
                        'price' => 1_000_000,
                        'original_sell_price' => 1_100_000,
                        'child_price' => 0,
                        'extra_bed_price' => 200_000,
                        'extra_foreigner_price' => 300_000,
                        'min_stay' => 2,
                    ],
                ],
            ]],
        ], false);

        $row = $mapped['rows'][0];
        $this->assertSame(10_000_000, $row['daily_rate']);
        $this->assertSame(11_000_000, $row['rack_rate']);
        $this->assertSame(0, $row['child_daily_rate']);
        $this->assertNull($row['infant_daily_rate']);
        $this->assertSame(2_000_000, $row['extend_bed_daily_rate']);
        $this->assertSame(2, $row['min_stay']);
    }

    public function test_foreign_calendar_uses_the_price_returned_for_the_foreign_rate_plan_without_repricing(): void
    {
        $mapped = (new SnappTripMapper())->hotelCalendar([
            'rooms' => [[
                'id' => 18607,
                'daily' => [
                    '2026-10-10' => [
                        'availability' => 1,
                        'price' => 1_000_000,
                        'original_sell_price' => 1_000_000,
                        'extra_foreigner_price' => 300_000,
                    ],
                ],
            ]],
        ], true);

        $row = $mapped['rows'][0];
        $this->assertTrue($row['foreigner']);
        $this->assertSame(10_000_000, $row['daily_rate']);
        $this->assertArrayNotHasKey('foreign_guest_daily_rate', $row);
    }
}
