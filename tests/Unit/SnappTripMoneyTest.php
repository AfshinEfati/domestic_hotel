<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripMoney;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SnappTripMoneyTest extends TestCase
{
    public function test_toman_is_converted_to_internal_irr_at_provider_boundary(): void
    {
        $this->assertSame(173_286_200, SnappTripMoney::toInternal(17_328_620));
        $this->assertSame(0, SnappTripMoney::toInternal(0));
        $this->assertNull(SnappTripMoney::toInternal(null));
    }

    public function test_internal_irr_is_converted_to_toman_for_provider_payloads(): void
    {
        $this->assertSame(17_328_620, SnappTripMoney::toProvider(173_286_200));
        $this->assertSame(0, SnappTripMoney::toProvider(0));
        $this->assertNull(SnappTripMoney::toProvider(null));
    }

    public function test_negative_provider_amount_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        SnappTripMoney::toInternal(-1);
    }
}
