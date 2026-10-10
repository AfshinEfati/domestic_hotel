<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarWindows;
use PHPUnit\Framework\TestCase;

class SnappTripCalendarWindowsTest extends TestCase
{
    public function test_forty_days_stays_in_one_window(): void
    {
        $this->assertSame([
            ['from' => '2026-10-10', 'to' => '2026-11-19'],
        ], SnappTripCalendarWindows::split('2026-10-10', '2026-11-19'));

        $this->assertSame(2, SnappTripCalendarWindows::requestCountForDays(40));
    }

    public function test_ninety_days_is_split_into_three_provider_safe_windows(): void
    {
        $this->assertSame([
            ['from' => '2026-10-10', 'to' => '2026-11-19'],
            ['from' => '2026-11-19', 'to' => '2026-12-29'],
            ['from' => '2026-12-29', 'to' => '2027-01-08'],
        ], SnappTripCalendarWindows::split('2026-10-10', '2027-01-08'));

        $this->assertSame(3, SnappTripCalendarWindows::chunkCountForDays(90));
        $this->assertSame(6, SnappTripCalendarWindows::requestCountForDays(90));
    }
}
