<?php

namespace Tests\Unit;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\SnappTrip\Application\Contracts\BalanceSynchronizer;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripPurchaseBalancePreflight;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SnappTripPurchaseBalancePreflightTest extends TestCase
{
    public function test_fresh_balance_covering_all_purchases_is_sufficient_without_provider_http(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->once())
            ->method('execute')
            ->willReturn(300_000);

        $result = (new SnappTripPurchaseBalancePreflight($sync))->check(new Collection([
            $this->purchase(11, 100_000),
            $this->purchase(12, 150_000),
        ]));

        $this->assertSame(300_000, $result->balance);
        $this->assertSame(250_000, $result->requiredAmount);
        $this->assertTrue($result->sufficient());
    }

    public function test_fresh_balance_below_whole_reservation_amount_is_insufficient(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->once())
            ->method('execute')
            ->willReturn(200_000);

        $result = (new SnappTripPurchaseBalancePreflight($sync))->check(new Collection([
            $this->purchase(11, 100_000),
            $this->purchase(12, 150_000),
        ]));

        $this->assertSame(250_000, $result->requiredAmount);
        $this->assertFalse($result->sufficient());
    }

    public function test_missing_purchase_amount_is_rejected_before_balance_sync(): void
    {
        $sync = $this->createMock(BalanceSynchronizer::class);
        $sync->expects($this->never())->method('execute');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reservation purchase 12 has no quoted provider amount.');

        (new SnappTripPurchaseBalancePreflight($sync))->check(new Collection([
            $this->purchase(11, 100_000),
            $this->purchase(12, null),
        ]));
    }

    private function purchase(int $id, ?int $quotedAmount): ReservationPurchase
    {
        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => $id,
            'provider_quoted_amount' => $quotedAmount,
        ]);

        return $purchase;
    }
}
