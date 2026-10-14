<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\ExecuteOnlineReservationPurchaseJob;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Tests\TestCase;

class ExecuteOnlineReservationPurchaseRetryExhaustionTest extends TestCase
{
    public function test_last_retry_attempt_moves_purchase_and_reservation_under_review(): void
    {
        $purchase = $this->purchase();

        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases->expects($this->once())
            ->method('findForOnlineExecution')
            ->with(41)
            ->willReturn($purchase);
        $purchases->expects($this->once())
            ->method('update')
            ->with(41, [
                'status' => ReservationStatus::UNDER_REVIEW,
                'provider_status' => 'retry_exhausted',
            ])
            ->willReturn(true);

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations->expects($this->once())
            ->method('update')
            ->with(77, ['status' => ReservationStatus::UNDER_REVIEW])
            ->willReturn(true);

        $registry = new HotelProviderRegistry();
        $registry->registerOnlinePurchaseHandler('retry-provider', AlwaysRetryOnlinePurchaseHandler::class);

        $container = new Container();
        $container->instance(AlwaysRetryOnlinePurchaseHandler::class, new AlwaysRetryOnlinePurchaseHandler());

        $queueJob = $this->createMock(QueueJob::class);
        $queueJob->method('attempts')->willReturn(30);
        $queueJob->expects($this->never())->method('release');

        $job = new ExecuteOnlineReservationPurchaseJob(41);
        $job->setJob($queueJob);
        $job->handle(
            $purchases,
            $reservations,
            $registry,
            $container,
            new TelegramAlertService(),
        );
    }

    public function test_retry_before_last_attempt_is_released_normally(): void
    {
        $purchase = $this->purchase();

        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases->method('findForOnlineExecution')->willReturn($purchase);
        $purchases->expects($this->never())->method('update');

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations->expects($this->never())->method('update');

        $registry = new HotelProviderRegistry();
        $registry->registerOnlinePurchaseHandler('retry-provider', AlwaysRetryOnlinePurchaseHandler::class);

        $container = new Container();
        $container->instance(AlwaysRetryOnlinePurchaseHandler::class, new AlwaysRetryOnlinePurchaseHandler());

        $queueJob = $this->createMock(QueueJob::class);
        $queueJob->method('attempts')->willReturn(29);
        $queueJob->expects($this->once())->method('release')->with(17);

        $job = new ExecuteOnlineReservationPurchaseJob(41);
        $job->setJob($queueJob);
        $job->handle(
            $purchases,
            $reservations,
            $registry,
            $container,
            new TelegramAlertService(),
        );
    }

    private function purchase(): ReservationPurchase
    {
        $provider = new Provider();
        $provider->forceFill([
            'id' => 9,
            'code' => 'retry-provider',
        ]);

        $reservation = new Reservation();
        $reservation->forceFill(['id' => 77]);

        $hotel = new ReservationHotel();
        $hotel->forceFill([
            'id' => 31,
            'reservation_id' => 77,
        ]);
        $hotel->setRelation('reservation', $reservation);

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => 41,
            'provider_id' => 9,
            'purchase_mode' => PurchaseMethod::ONLINE,
            'status' => ReservationStatus::BOOK_REQUESTED,
        ]);
        $purchase->setRelation('provider', $provider);
        $purchase->setRelation('reservationHotel', $hotel);

        return $purchase;
    }
}

final class AlwaysRetryOnlinePurchaseHandler implements OnlinePurchaseHandler
{
    public function groupingKey(ReservationRoom $room): string
    {
        return 'retry';
    }

    public function queue(): string
    {
        return 'retry-provider';
    }

    public function execute(ReservationPurchase $purchase, int $attempt): OnlinePurchaseExecutionResult
    {
        return OnlinePurchaseExecutionResult::retry(17);
    }

    public function prepareRetry(ReservationPurchase $purchase): array
    {
        return [];
    }
}
