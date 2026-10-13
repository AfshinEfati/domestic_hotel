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
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\ReservationPurchaseRequestService;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Tests\TestCase;

class ReservationPurchasePreflightDispatchTest extends TestCase
{
    public function test_online_purchases_are_gated_by_one_reservation_provider_preflight_job(): void
    {
        Queue::fake();

        $provider = new Provider();
        $provider->forceFill([
            'id' => 9,
            'code' => 'snapptrip',
        ]);

        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => 41,
            'provider_id' => 9,
            'purchase_mode' => PurchaseMethod::ONLINE,
            'status' => ReservationStatus::BOOK_REQUESTED,
        ]);
        $purchase->setRelation('provider', $provider);

        $hotel = new ReservationHotel();
        $hotel->setRelation('purchases', new Collection([$purchase]));

        $reservation = new Reservation();
        $reservation->forceFill(['id' => 77]);
        $reservation->setRelation('hotels', new Collection([$hotel]));

        $registry = new HotelProviderRegistry();
        $registry->registerOnlinePurchaseHandler('snapptrip', ReservationDispatchOnlinePurchaseHandler::class);

        $container = new Container();
        $container->instance(
            ReservationDispatchOnlinePurchaseHandler::class,
            new ReservationDispatchOnlinePurchaseHandler(),
        );

        $service = new ReservationPurchaseRequestService(
            $this->createMock(ReservationRepositoryInterface::class),
            $this->createMock(ReservationPurchaseRepositoryInterface::class),
            $this->createMock(ReservationPurchaseSegmentRepositoryInterface::class),
            $this->createMock(ReservationManualPurchaseRepositoryInterface::class),
            $this->createMock(ReservationManualReasonRepositoryInterface::class),
            $this->createMock(ProviderRepositoryInterface::class),
            $this->createMock(PurchaseResolverInterface::class),
            $registry,
            $container,
        );

        $method = new ReflectionMethod($service, 'dispatchQueuedOnlinePurchases');
        $method->setAccessible(true);
        $method->invoke($service, $reservation);

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

final class ReservationDispatchOnlinePurchaseHandler implements OnlinePurchaseHandler
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
        return [];
    }
}
