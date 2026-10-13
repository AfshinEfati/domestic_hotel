<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\OnlineReservationPurchaseRecoveryServiceInterface;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class OnlineReservationPurchaseRecoveryService implements OnlineReservationPurchaseRecoveryServiceInterface
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ReservationPurchaseRepositoryInterface $purchases,
        private ReservationManualPurchaseRepositoryInterface $manualPurchases,
        private ReservationManualReasonRepositoryInterface $manualReasons,
        private HotelProviderRegistry $providerRegistry,
        private Container $container,
    ) {
    }

    public function retry(int $reservationId, int $purchaseId): Reservation
    {
        [$reservation, $providerId, $queue] = DB::transaction(function () use ($reservationId, $purchaseId): array {
            $purchase = $this->lockedPurchase($reservationId, $purchaseId);
            $this->assertRetryable($purchase);

            $provider = $purchase->provider
                ?? throw new InvalidArgumentException('Purchase provider was not found.');
            $handler = $this->providerRegistry->resolveOnlinePurchaseHandler($provider, $this->container)
                ?? throw new InvalidArgumentException('Provider has no online purchase handler.');

            $updates = $handler->prepareRetry($purchase);
            $updates['purchase_mode'] = PurchaseMethod::ONLINE;
            $updates['manual_reason'] = null;
            $updates['manual_rule_id'] = null;
            $this->purchases->update($purchaseId, $updates);

            $this->reconcilePendingReservation($reservationId);

            return [
                $this->reload($reservationId),
                (int) $provider->id,
                $handler->queue(),
            ];
        });

        // Retry must pass through the same reservation/provider preflight as the initial
        // purchase request. This prevents a fresh provider create/confirm from bypassing
        // the current balance check after an operator retries an under-review purchase.
        PreflightOnlineReservationPurchasesJob::dispatch($reservationId, $providerId)
            ->onQueue($queue);

        return $reservation;
    }

    public function convertToManual(int $reservationId, int $purchaseId): Reservation
    {
        return DB::transaction(function () use ($reservationId, $purchaseId): Reservation {
            $purchase = $this->lockedPurchase($reservationId, $purchaseId);

            if ((int) $purchase->purchase_mode === PurchaseMethod::OFFLINE) {
                return $this->reload($reservationId);
            }

            if ((int) $purchase->status !== ReservationStatus::UNDER_REVIEW) {
                throw new InvalidArgumentException(
                    'Only an online purchase under review can be converted to manual.'
                );
            }

            $this->purchases->update($purchaseId, [
                'purchase_mode' => PurchaseMethod::OFFLINE,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'manual_reason' => PurchaseManualReason::ONLINE_FAILED,
                'manual_rule_id' => null,
            ]);
            $this->manualPurchases->firstOrCreateForPurchase($purchaseId);
            $this->manualReasons->store([
                'reservation_id' => $reservationId,
                'reason' => 'خرید آنلاین ناموفق یا نامشخص بود و برای ادامه به خرید دستی تبدیل شد.',
            ]);

            $this->reconcilePendingReservation($reservationId);

            return $this->reload($reservationId);
        });
    }

    private function lockedPurchase(int $reservationId, int $purchaseId): ReservationPurchase
    {
        $purchase = $this->purchases->findForOnlineExecution($purchaseId, true);
        if (
            $purchase === null
            || (int) ($purchase->reservationHotel?->reservation_id ?? 0) !== $reservationId
        ) {
            throw (new ModelNotFoundException())->setModel(ReservationPurchase::class, [$purchaseId]);
        }

        return $purchase;
    }

    private function assertRetryable(ReservationPurchase $purchase): void
    {
        if ((int) $purchase->purchase_mode !== PurchaseMethod::ONLINE) {
            throw new InvalidArgumentException('Only an online purchase can be retried online.');
        }

        if ((int) $purchase->status !== ReservationStatus::UNDER_REVIEW) {
            throw new InvalidArgumentException('Only an online purchase under review can be retried.');
        }
    }

    private function reconcilePendingReservation(int $reservationId): void
    {
        $reservation = $this->reservations->findForPurchase($reservationId);
        if ($reservation === null) {
            return;
        }

        $purchases = $reservation->hotels
            ->flatMap(fn ($hotel) => $hotel->purchases)
            ->unique('id')
            ->values();

        $status = $purchases->contains(
            fn ($purchase): bool => (int) $purchase->status === ReservationStatus::UNDER_REVIEW
        )
            ? ReservationStatus::UNDER_REVIEW
            : ReservationStatus::BOOK_REQUESTED;

        $this->reservations->update($reservationId, ['status' => $status]);
    }

    private function reload(int $reservationId): Reservation
    {
        return $this->reservations->findForPurchase($reservationId)
            ?? throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
    }
}
