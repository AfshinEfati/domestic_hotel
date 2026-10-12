<?php

namespace Tests\Unit;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripBookingService;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripOnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncBalanceJob;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class SnappTripBalanceRefreshAfterPurchaseTest extends TestCase
{
    public function test_confirmed_purchase_queues_balance_refresh_without_provider_http(): void
    {
        Queue::fake();

        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases
            ->expects($this->once())
            ->method('update')
            ->with(
                41,
                $this->callback(function (array $data): bool {
                    return $data['status'] === ReservationStatus::ISSUE_SUCCESS
                        && $data['purchase_amount'] === 900_000
                        && $data['provider_status'] === 'confirmed'
                        && isset($data['issued_at']);
                }),
            );

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations
            ->expects($this->once())
            ->method('findForPurchase')
            ->with(77)
            ->willReturn(null);

        $handler = new SnappTripOnlinePurchaseHandler(
            (new ReflectionClass(SnappTripBookingService::class))->newInstanceWithoutConstructor(),
            $purchases,
            $reservations,
            $this->createMock(AccommodationProviderMapRepositoryInterface::class),
            $this->createMock(RoomTypeProviderMapRepositoryInterface::class),
        );

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => 41,
            'provider_quoted_amount' => 900_000,
        ]);

        $method = new ReflectionMethod($handler, 'issued');
        $method->setAccessible(true);
        $method->invoke($handler, $purchase, 77, 900_000);

        Queue::assertPushed(
            SyncBalanceJob::class,
            fn (SyncBalanceJob $job): bool => $job->queue === 'snapptrip-operations',
        );
    }

    public function test_confirmed_price_increase_still_queues_balance_refresh(): void
    {
        Queue::fake();

        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases
            ->expects($this->once())
            ->method('update')
            ->with(
                42,
                $this->callback(function (array $data): bool {
                    return $data['status'] === ReservationStatus::PAYMENT_REQUIRED
                        && $data['purchase_amount'] === 950_000
                        && $data['provider_status'] === 'confirmed';
                }),
            );

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations
            ->method('findForPurchase')
            ->willReturn(null);

        $handler = new SnappTripOnlinePurchaseHandler(
            (new ReflectionClass(SnappTripBookingService::class))->newInstanceWithoutConstructor(),
            $purchases,
            $reservations,
            $this->createMock(AccommodationProviderMapRepositoryInterface::class),
            $this->createMock(RoomTypeProviderMapRepositoryInterface::class),
        );

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => 42,
            'provider_quoted_amount' => 900_000,
        ]);

        $method = new ReflectionMethod($handler, 'issued');
        $method->setAccessible(true);
        $method->invoke($handler, $purchase, 78, 950_000);

        Queue::assertPushed(SyncBalanceJob::class);
    }
}
