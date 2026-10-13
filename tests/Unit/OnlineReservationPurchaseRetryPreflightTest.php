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
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\OnlineReservationPurchaseRecoveryService;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnlineReservationPurchaseRetryPreflightTest extends TestCase
{
    public function test_online_retry_is_gated_by_preflight_instead_of_dispatching_booking_directly(): void
    {
        Queue::fake();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

        $provider = new Provider();
        $provider->forceFill([
            'id' => 9,
            'code' => 'snapptrip',
        ]);

        $hotel = new ReservationHotel();
        $hotel->forceFill([
            'id' => 21,
            'reservation_id' => 77,
        ]);

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => 41,
            'reservation_hotel_id' => 21,
            'provider_id' => 9,
            'purchase_mode' => PurchaseMethod::ONLINE,
            'status' => ReservationStatus::UNDER_REVIEW,
            'provider_status' => 'price_increased',
            'confirmation_code' => 'old-code',
        ]);
        $purchase->setRelation('provider', $provider);
        $purchase->setRelation('reservationHotel', $hotel);

        $hotel->setRelation('purchases', new Collection([$purchase]));

        $reservation = new Reservation();
        $reservation->forceFill([
            'id' => 77,
            'status' => ReservationStatus::UNDER_REVIEW,
        ]);
        $reservation->setRelation('hotels', new Collection([$hotel]));

        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases->expects($this->once())
            ->method('findForOnlineExecution')
            ->with(41, true)
            ->willReturn($purchase);
        $purchases->expects($this->once())
            ->method('update')
            ->with(41, $this->callback(function (array $data) use ($purchase): bool {
                $this->assertSame(PurchaseMethod::ONLINE, $data['purchase_mode']);
                $this->assertSame(ReservationStatus::BOOK_REQUESTED, $data['status']);
                $this->assertNull($data['manual_reason']);
                $this->assertNull($data['manual_rule_id']);

                $purchase->forceFill($data);

                return true;
            }))
            ->willReturn(true);

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations->expects($this->exactly(2))
            ->method('findForPurchase')
            ->with(77)
            ->willReturn($reservation);
        $reservations->expects($this->once())
            ->method('update')
            ->with(77, ['status' => ReservationStatus::BOOK_REQUESTED])
            ->willReturn(true);

        $registry = new HotelProviderRegistry();
        $registry->registerOnlinePurchaseHandler('snapptrip', RetryPreflightOnlinePurchaseHandler::class);

        $container = new Container();
        $container->instance(
            RetryPreflightOnlinePurchaseHandler::class,
            new RetryPreflightOnlinePurchaseHandler(),
        );

        $service = new OnlineReservationPurchaseRecoveryService(
            $reservations,
            $purchases,
            $this->createMock(ReservationManualPurchaseRepositoryInterface::class),
            $this->createMock(ReservationManualReasonRepositoryInterface::class),
            $registry,
            $container,
        );

        $result = $service->retry(77, 41);

        $this->assertSame(77, (int) $result->id);

        Queue::assertPushed(
            PreflightOnlineReservationPurchasesJob::class,
            fn (PreflightOnlineReservationPurchasesJob $job): bool =>
                $job->reservationId === 77
                && $job->providerId === 9
                && $job->queue === 'provider-online',
        );
        Queue::assertNotPushed(ExecuteOnlineReservationPurchaseJob::class);
    }
}

final class RetryPreflightOnlinePurchaseHandler implements OnlinePurchaseHandler
{
    public function groupingKey(ReservationRoom $room): string
    {
        return 'test';
    }

    public function queue(): string
    {
        return 'provider-online';
    }

    public function execute(ReservationPurchase $purchase, int $attempt): OnlinePurchaseExecutionResult
    {
        return OnlinePurchaseExecutionResult::done();
    }

    public function prepareRetry(ReservationPurchase $purchase): array
    {
        return [
            'status' => ReservationStatus::BOOK_REQUESTED,
            'confirmation_code' => null,
            'provider_status' => null,
            'purchase_amount' => null,
            'expires_at' => null,
            'issued_at' => null,
        ];
    }
}
