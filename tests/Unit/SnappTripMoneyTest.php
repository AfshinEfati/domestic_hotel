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

    public function test_nested_raw_provider_payload_converts_only_monetary_fields(): void
    {
        $normalized = SnappTripMoney::normalizeProviderPayload([
            'filter' => ['min_price' => 100],
            'items' => [[
                'room' => [
                    'price' => 1_000_000,
                    'child_price' => 0,
                    'extra_foreigner_price' => 200_000,
                    'discount_percent' => 10,
                ],
            ]],
        ]);

        // Unknown fields are intentionally untouched; only documented provider money
        // keys cross the module boundary through the automatic normalizer.
        $this->assertSame(100, $normalized['filter']['min_price']);
        $this->assertSame(10_000_000, $normalized['items'][0]['room']['price']);
        $this->assertSame(0, $normalized['items'][0]['room']['child_price']);
        $this->assertSame(2_000_000, $normalized['items'][0]['room']['extra_foreigner_price']);
        $this->assertSame(10, $normalized['items'][0]['room']['discount_percent']);
    }
}
