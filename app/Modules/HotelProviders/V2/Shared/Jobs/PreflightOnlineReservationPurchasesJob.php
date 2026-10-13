<?php

namespace App\Modules\HotelProviders\V2\Shared\Jobs;

use App\Models\Reservation;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PreflightOnlineReservationPurchasesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public int $uniqueFor = 3600;

    public function __construct(
        public int $reservationId,
        public int $providerId,
    ) {
    }

    public function uniqueId(): string
    {
        return 'online-reservation-purchase-preflight:'.$this->reservationId.':'.$this->providerId;
    }

    public function handle(
        ReservationRepositoryInterface $reservations,
        ReservationPurchaseRepositoryInterface $purchases,
        ReservationManualPurchaseRepositoryInterface $manualPurchases,
        ReservationManualReasonRepositoryInterface $manualReasons,
        HotelProviderRegistry $registry,
        Container $container,
        TelegramAlertService $alerts,
    ): void {
        $reservation = $reservations->findForPurchase($this->reservationId);
        if ($reservation === null) {
            return;
        }

        $onlinePurchases = $this->onlinePurchases($reservation);
        if ($onlinePurchases->isEmpty()) {
            return;
        }

        $provider = $onlinePurchases->first()?->provider;
        if ($provider === null || (int) $provider->id !== $this->providerId) {
            $this->markUnexpectedReview($reservations, $purchases);
            return;
        }

        $handler = $registry->resolveOnlinePurchaseHandler($provider, $container);
        if ($handler === null) {
            $this->routeToManual(
                $reservations,
                $purchases,
                $manualPurchases,
                $manualReasons,
                PurchaseManualReason::PROVIDER_MANUAL_ONLY,
                'خرید آفلاین شد؛ اجرای خرید آنلاین این تأمین‌کننده در GDS در دسترس نیست.',
            );
            return;
        }

        $preflight = $registry->resolveOnlinePurchasePreflightHandler($provider, $container);
        if ($preflight !== null) {
            try {
                $result = $preflight->check($onlinePurchases);
            } catch (Throwable $exception) {
                $alerts->internalFailure($exception);
                $this->markUnexpectedReview($reservations, $purchases);
                return;
            }

            if (!$result->allowed) {
                $this->routeToManual(
                    $reservations,
                    $purchases,
                    $manualPurchases,
                    $manualReasons,
                    $result->manualReason ?? PurchaseManualReason::CREDIT_UNAVAILABLE,
                    $result->manualReasonText ?? 'خرید آفلاین شد؛ preflight خرید آنلاین تأیید نشد.',
                );
                return;
            }
        }

        foreach ($onlinePurchases as $purchase) {
            ExecuteOnlineReservationPurchaseJob::dispatch((int) $purchase->id)
                ->onQueue($handler->queue());
        }
    }

    /** @return Collection<int, \App\Models\ReservationPurchase> */
    private function onlinePurchases(Reservation $reservation): Collection
    {
        return $reservation->hotels
            ->flatMap(fn ($hotel) => $hotel->purchases)
            ->filter(fn ($purchase): bool =>
                (int) $purchase->provider_id === $this->providerId
                && (int) $purchase->purchase_mode === PurchaseMethod::ONLINE
                && (int) $purchase->status === ReservationStatus::BOOK_REQUESTED
            )
            ->unique('id')
            ->values();
    }

    private function routeToManual(
        ReservationRepositoryInterface $reservations,
        ReservationPurchaseRepositoryInterface $purchases,
        ReservationManualPurchaseRepositoryInterface $manualPurchases,
        ReservationManualReasonRepositoryInterface $manualReasons,
        int $manualReason,
        string $manualReasonText,
    ): void {
        DB::transaction(function () use (
            $reservations,
            $purchases,
            $manualPurchases,
            $manualReasons,
            $manualReason,
            $manualReasonText,
        ): void {
            $reservation = $reservations->findForPurchase($this->reservationId, true);
            if ($reservation === null) {
                return;
            }

            $onlinePurchases = $this->onlinePurchases($reservation);
            if ($onlinePurchases->isEmpty()) {
                return;
            }

            foreach ($onlinePurchases as $purchase) {
                $purchaseId = (int) $purchase->id;

                $purchases->update($purchaseId, [
                    'purchase_mode' => PurchaseMethod::OFFLINE,
                    'status' => ReservationStatus::BOOK_REQUESTED,
                    'manual_reason' => $manualReason,
                    'manual_rule_id' => null,
                ]);
                $manualPurchases->firstOrCreateForPurchase($purchaseId);
            }

            $manualReasons->store([
                'reservation_id' => $this->reservationId,
                'reason' => $manualReasonText,
            ]);
        });
    }

    private function markUnexpectedReview(
        ReservationRepositoryInterface $reservations,
        ReservationPurchaseRepositoryInterface $purchases,
    ): void {
        DB::transaction(function () use ($reservations, $purchases): void {
            $reservation = $reservations->findForPurchase($this->reservationId, true);
            if ($reservation === null) {
                return;
            }

            $onlinePurchases = $this->onlinePurchases($reservation);
            foreach ($onlinePurchases as $purchase) {
                $purchases->update((int) $purchase->id, [
                    'status' => ReservationStatus::UNDER_REVIEW,
                    'provider_status' => 'preflight_internal_error',
                ]);
            }

            if ($onlinePurchases->isNotEmpty()) {
                $reservations->update($this->reservationId, [
                    'status' => ReservationStatus::UNDER_REVIEW,
                ]);
            }
        });
    }
}
