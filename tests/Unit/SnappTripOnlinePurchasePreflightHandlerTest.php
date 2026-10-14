<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\SnappTrip\Application\Contracts\BalanceSynchronizer;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripOnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripPurchaseBalancePreflight;
use App\Support\Reservation\PurchaseManualReason;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SnappTripOnlinePurchasePreflightHandlerTest extends TestCase
{
    public function test_sufficient_fresh_balance_allows_whole_online_purchase_set(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->once())
            ->method('execute')
            ->willReturn(300_000);

        $result = $this->handler($sync)->check(new Collection([
            $this->purchase(11, 100_000),
            $this->purchase(12, 150_000),
        ]));

        $this->assertTrue($result->allowed);
        $this->assertNull($result->manualReason);
        $this->assertNull($result->manualReasonText);
    }

    public function test_insufficient_fresh_balance_routes_whole_purchase_set_to_manual(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->once())
            ->method('execute')
            ->willReturn(200_000);

        $result = $this->handler($sync)->check(new Collection([
            $this->purchase(11, 100_000),
            $this->purchase(12, 150_000),
        ]));

        $this->assertFalse($result->allowed);
        $this->assertSame(PurchaseManualReason::INSUFFICIENT_CREDIT, $result->manualReason);
        $this->assertStringContainsString('200000', (string) $result->manualReasonText);
        $this->assertStringContainsString('250000', (string) $result->manualReasonText);
    }

    public function test_unavailable_fresh_balance_routes_purchase_set_to_manual(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->once())
            ->method('execute')
            ->willThrowException(new RuntimeException('balance unavailable'));

        $result = $this->handler($sync)->check(new Collection([
            $this->purchase(11, 100_000),
        ]));

        $this->assertFalse($result->allowed);
        $this->assertSame(PurchaseManualReason::CREDIT_UNAVAILABLE, $result->manualReason);
        $this->assertNotSame('', trim((string) $result->manualReasonText));
    }

    public function test_effective_default_disabled_purchase_routes_to_manual_without_balance_io(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->never())->method('execute');

        $purchase = $this->purchase(11, 100_000);
        $purchase->provider->config = [];

        $result = $this->handler($sync)->check(new Collection([$purchase]));

        $this->assertFalse($result->allowed);
        $this->assertSame(PurchaseManualReason::PROVIDER_MANUAL_ONLY, $result->manualReason);
    }

    private function handler(BalanceSynchronizer $sync): SnappTripOnlinePurchasePreflightHandler
    {
        return new SnappTripOnlinePurchasePreflightHandler(
            new SnappTripPurchaseBalancePreflight($sync),
        );
    }

    private function purchase(int $id, int $quotedAmount): ReservationPurchase
    {
        $provider = new Provider();
        $provider->forceFill([
            'id' => 9,
            'code' => 'snap',
            'config' => [
                'purchase' => [
                    'online_enabled' => true,
                ],
            ],
            'is_active' => true,
            'is_online' => true,
        ]);

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => $id,
            'provider_id' => 9,
            'provider_quoted_amount' => $quotedAmount,
        ]);
        $purchase->setRelation('provider', $provider);

        return $purchase;
    }
}
