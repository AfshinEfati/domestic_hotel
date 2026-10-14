<?php

namespace App\Modules\HotelProviders\V2\Shared\Jobs;

use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ExecuteOnlineReservationPurchaseJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $timeout = 90;
    public int $uniqueFor = 3600;

    public function __construct(public int $purchaseId)
    {
    }

    public function uniqueId(): string
    {
        return 'online-reservation-purchase:'.$this->purchaseId;
    }

    public function handle(
        ReservationPurchaseRepositoryInterface $purchases,
        ReservationRepositoryInterface $reservations,
        HotelProviderRegistry $registry,
        Container $container,
        TelegramAlertService $alerts,
    ): void {
        $purchase = $purchases->findForOnlineExecution($this->purchaseId);
        if (
            $purchase === null
            || (int) $purchase->purchase_mode !== PurchaseMethod::ONLINE
            || in_array((int) $purchase->status, [
                ReservationStatus::ISSUE_SUCCESS,
                ReservationStatus::PAYMENT_REQUIRED,
            ], true)
        ) {
            return;
        }

        $provider = $purchase->provider;
        if ($provider === null) {
            $this->markReview($purchase, $purchases, $reservations, 'internal_error');
            return;
        }

        $handler = $registry->resolveOnlinePurchaseHandler($provider, $container);
        if ($handler === null) {
            $this->markReview($purchase, $purchases, $reservations, 'internal_error');
            return;
        }

        try {
            $result = $handler->execute($purchase, max(1, $this->attempts()));
            if ($result->retry) {
                if ($this->attempts() >= $this->tries) {
                    $this->markReview($purchase, $purchases, $reservations, 'retry_exhausted');
                    return;
                }

                $this->release($result->retryAfterSeconds);
            }
        } catch (Throwable $exception) {
            $alerts->internalFailure($exception);
            $this->markReview($purchase, $purchases, $reservations, 'internal_error');
        }
    }

    private function markReview(
        $purchase,
        ReservationPurchaseRepositoryInterface $purchases,
        ReservationRepositoryInterface $reservations,
        string $providerStatus,
    ): void {
        $purchases->update((int) $purchase->id, [
            'status' => ReservationStatus::UNDER_REVIEW,
            'provider_status' => $providerStatus,
        ]);

        $reservationId = $purchase->reservationHotel?->reservation_id;
        if ($reservationId !== null) {
            $reservations->update((int) $reservationId, [
                'status' => ReservationStatus::UNDER_REVIEW,
            ]);
        }
    }
}
