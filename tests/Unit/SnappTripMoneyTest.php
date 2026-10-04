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
            'filter' => ['min_price' => 100, 'max_price' => 900],
            'items' => [[
                'room' => [
                    'price' => 1_000_000,
                    'child_price' => 0,
                    'extra_foreigner_price' => 200_000,
                    'discount_percent' => 10,
                ],
            ]],
        ]);

        $this->assertSame(1_000, $normalized['filter']['min_price']);
        $this->assertSame(9_000, $normalized['filter']['max_price']);
        $this->assertSame(10_000_000, $normalized['items'][0]['room']['price']);
        $this->assertSame(0, $normalized['items'][0]['room']['child_price']);
        $this->assertSame(2_000_000, $normalized['items'][0]['room']['extra_foreigner_price']);
        $this->assertSame(10, $normalized['items'][0]['room']['discount_percent']);
    }

    public function test_city_search_request_price_filters_are_converted_from_irr_to_toman(): void
    {
        $payload = SnappTripMoney::normalizeRequestPayload([
            'city_id' => 1,
            'min_price' => 10_000_000,
            'max_price' => 25_000_000,
            'stars' => [4, 5],
        ]);

        $this->assertSame(1_000_000, $payload['min_price']);
        $this->assertSame(2_500_000, $payload['max_price']);
        $this->assertSame([4, 5], $payload['stars']);
    }
}
