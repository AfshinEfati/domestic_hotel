<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationManualPurchase;
use App\Models\ReservationManualReason;
use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\ExecuteOnlineReservationPurchaseJob;
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnlineReservationPurchasePreflightJobTest extends TestCase
{
    public function test_successful_preflight_dispatches_all_provider_purchases_only_after_check(): void
    {
        Queue::fake();

        $reservation = $this->reservationWithPurchases();
        $repositories = $this->repositories($reservation);

        $registry = new HotelProviderRegistry();
        $container = new Container();
        $registry->registerOnlinePurchaseHandler('snapptrip', TestOnlinePurchaseHandler::class);
        $registry->registerOnlinePurchasePreflightHandler('snapptrip', TestOnlinePurchasePreflightHandler::class);
        $container->instance(TestOnlinePurchaseHandler::class, new TestOnlinePurchaseHandler());
        $container->instance(
            TestOnlinePurchasePreflightHandler::class,
            new TestOnlinePurchasePreflightHandler(OnlinePurchasePreflightResult::allowed()),
        );

        (new PreflightOnlineReservationPurchasesJob(77, 9))->handle(
            $repositories['reservations'],
            $repositories['purchases'],
            $repositories['manualPurchases'],
            $repositories['manualReasons'],
            $registry,
            $container,
            new TelegramAlertService(),
        );

        Queue::assertPushed(ExecuteOnlineReservationPurchaseJob::class, 2);
        Queue::assertPushed(
            ExecuteOnlineReservationPurchaseJob::class,
            fn (ExecuteOnlineReservationPurchaseJob $job): bool =>
                in_array($job->purchaseId, [41, 42], true)
                && $job->queue === 'provider-online',
        );
    }

    public function test_insufficient_balance_converts_all_purchases_to_manual_and_dispatches_no_booking(): void
    {
        Queue::fake();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

        $reservation = $this->reservationWithPurchases();
        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations->method('findForPurchase')->willReturn($reservation);

        $updatedIds = [];
        $purchases = $this->createMock(ReservationPurchaseRepositoryInterface::class);
        $purchases->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function (int $id, array $data) use (&$updatedIds): bool {
                $updatedIds[] = $id;
                $this->assertSame(PurchaseMethod::OFFLINE, $data['purchase_mode']);
                $this->assertSame(ReservationStatus::BOOK_REQUESTED, $data['status']);
                $this->assertSame(PurchaseManualReason::INSUFFICIENT_CREDIT, $data['manual_reason']);
                $this->assertNull($data['manual_rule_id']);

                return true;
            });

        $manualPurchases = $this->createMock(ReservationManualPurchaseRepositoryInterface::class);
        $manualPurchases->expects($this->exactly(2))
            ->method('firstOrCreateForPurchase')
            ->willReturnCallback(fn (int $id): ReservationManualPurchase => new ReservationManualPurchase([
                'reservation_purchase_id' => $id,
            ]));

        $manualReasons = $this->createMock(ReservationManualReasonRepositoryInterface::class);
        $manualReasons->expects($this->once())
            ->method('store')
            ->with($this->callback(fn (array $data): bool =>
                $data['reservation_id'] === 77
                && str_contains($data['reason'], 'اعتبار')
            ))
            ->willReturn(new ReservationManualReason());

        $registry = new HotelProviderRegistry();
        $container = new Container();
        $registry->registerOnlinePurchaseHandler('snapptrip', TestOnlinePurchaseHandler::class);
        $registry->registerOnlinePurchasePreflightHandler('snapptrip', TestOnlinePurchasePreflightHandler::class);
        $container->instance(TestOnlinePurchaseHandler::class, new TestOnlinePurchaseHandler());
        $container->instance(
            TestOnlinePurchasePreflightHandler::class,
            new TestOnlinePurchasePreflightHandler(OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::INSUFFICIENT_CREDIT,
                'اعتبار به‌روز برای کل رزرو کافی نیست.',
            )),
        );

        (new PreflightOnlineReservationPurchasesJob(77, 9))->handle(
            $reservations,
            $purchases,
            $manualPurchases,
            $manualReasons,
            $registry,
            $container,
            new TelegramAlertService(),
        );

        sort($updatedIds);
        $this->assertSame([41, 42], $updatedIds);
        Queue::assertNotPushed(ExecuteOnlineReservationPurchaseJob::class);
    }

    /** @return array<string, object> */
    private function repositories(Reservation $reservation): array
    {
        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations->method('findForPurchase')->willReturn($reservation);

        return [
            'reservations' => $reservations,
            'purchases' => $this->createMock(ReservationPurchaseRepositoryInterface::class),
            'manualPurchases' => $this->createMock(ReservationManualPurchaseRepositoryInterface::class),
            'manualReasons' => $this->createMock(ReservationManualReasonRepositoryInterface::class),
        ];
    }

    private function reservationWithPurchases(): Reservation
    {
        $provider = new Provider();
        $provider->forceFill([
            'id' => 9,
            'code' => 'snapptrip',
        ]);

        $first = $this->purchase(41, $provider);
        $second = $this->purchase(42, $provider);

        $hotel = new ReservationHotel();
        $hotel->setRelation('purchases', new Collection([$first, $second]));

        $reservation = new Reservation();
        $reservation->forceFill(['id' => 77]);
        $reservation->setRelation('hotels', new Collection([$hotel]));

        return $reservation;
    }

    private function purchase(int $id, Provider $provider): ReservationPurchase
    {
        $purchase = new ReservationPurchase();
        $purchase->forceFill([
            'id' => $id,
            'provider_id' => 9,
            'purchase_mode' => PurchaseMethod::ONLINE,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'provider_quoted_amount' => 100_000,
        ]);
        $purchase->setRelation('provider', $provider);

        return $purchase;
    }
}

final class TestOnlinePurchaseHandler implements OnlinePurchaseHandler
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

final readonly class TestOnlinePurchasePreflightHandler implements OnlinePurchasePreflightHandler
{
    public function __construct(private OnlinePurchasePreflightResult $result)
    {
    }

    public function check(Collection $purchases): OnlinePurchasePreflightResult
    {
        return $this->result;
    }
}
