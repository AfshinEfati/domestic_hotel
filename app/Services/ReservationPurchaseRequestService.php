<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\Contracts\ReservationPurchaseRequestServiceInterface;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ReservationPurchaseRequestService implements ReservationPurchaseRequestServiceInterface
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ReservationPurchaseRepositoryInterface $purchases,
        private ReservationPurchaseSegmentRepositoryInterface $segments,
        private ReservationManualPurchaseRepositoryInterface $manualPurchases,
        private ReservationManualReasonRepositoryInterface $manualReasons,
        private ProviderRepositoryInterface $providers,
        private PurchaseResolverInterface $resolver,
        private HotelProviderRegistry $providerRegistry,
        private Container $container,
    ) {}

    public function request(int $reservationId): Reservation
    {
        $reservation = DB::transaction(function () use ($reservationId): Reservation {
            $reservation = $this->reservations
                ->findForPurchase($reservationId, true);

            if ($reservation === null) {
                throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
            }

            $finalHotels = $reservation->hotels->where('is_final', true);
            if ($finalHotels->count() !== 1) {
                throw new InvalidArgumentException('Reservation must have exactly one final hotel before purchase.');
            }

            $hotel = $finalHotels->first();
            $rooms = $hotel->rooms->where('is_final', true)->values();

            if ($rooms->isEmpty()) {
                throw new InvalidArgumentException('Reservation has no final room to purchase.');
            }

            // A repeated whitelist callback after a successful first request is harmless.
            // Online preflight is re-dispatched after the transaction; uniqueness prevents
            // a parallel duplicate while allowing a lost queue dispatch to recover later.
            if ((int) $reservation->status === ReservationStatus::BOOK_REQUESTED
                && $hotel->purchases->isNotEmpty()) {
                return $this->reload($reservationId);
            }

            if ((int) $reservation->status !== ReservationStatus::READY_FOR_PAYMENT) {
                throw new InvalidArgumentException('Reservation is not ready to receive a purchase request.');
            }

            $providerId = $this->singleProviderId($rooms);
            $quotedAmount = $this->quotedAmount($rooms);

            $resolution = $this->resolver->resolveAndRecord(
                $reservationId,
                $providerId,
                $quotedAmount,
            );

            $provider = $this->providers->find($providerId)
                ?? throw new InvalidArgumentException('Selected provider was not found.');

            $handler = $resolution->purchaseMode === PurchaseMethod::ONLINE
                ? $this->providerRegistry->resolveOnlinePurchaseHandler($provider, $this->container)
                : null;

            // ONLINE only means something when the provider has an executable booking
            // handler. Until a legacy provider is migrated, safely route it to the
            // already-tested manual workflow instead of leaving an unprocessed purchase.
            if ($resolution->purchaseMode === PurchaseMethod::ONLINE && $handler === null) {
                $this->createPurchase(
                    $hotel->id,
                    $providerId,
                    $rooms,
                    $quotedAmount,
                    PurchaseMethod::OFFLINE,
                    PurchaseManualReason::PROVIDER_MANUAL_ONLY,
                    null,
                    $reservation->check_in->format('Y-m-d'),
                    $reservation->check_out->format('Y-m-d'),
                );
                $this->manualReasons->store([
                    'reservation_id' => $reservationId,
                    'reason' => 'خرید آفلاین شد؛ اجرای خرید آنلاین این تأمین‌کننده هنوز در GDS ثبت نشده است.',
                ]);
            } elseif ($resolution->purchaseMode === PurchaseMethod::OFFLINE) {
                $this->createPurchase(
                    $hotel->id,
                    $providerId,
                    $rooms,
                    $quotedAmount,
                    PurchaseMethod::OFFLINE,
                    $resolution->manualReason,
                    $resolution->manualRuleId,
                    $reservation->check_in->format('Y-m-d'),
                    $reservation->check_out->format('Y-m-d'),
                );
            } else {
                // SnappTrip does not allow different provider room IDs in one create
                // request. Handler grouping keeps that provider rule outside the core.
                foreach ($rooms->groupBy(
                    fn ($room): string => $handler->groupingKey($room)
                ) as $bookingRooms) {
                    $this->createPurchase(
                        $hotel->id,
                        $providerId,
                        $bookingRooms,
                        $this->quotedAmount($bookingRooms),
                        PurchaseMethod::ONLINE,
                        null,
                        null,
                        $reservation->check_in->format('Y-m-d'),
                        $reservation->check_out->format('Y-m-d'),
                    );
                }
            }

            $this->reservations->update($reservation->id, [
                'status' => ReservationStatus::BOOK_REQUESTED,
            ]);

            return $this->reload($reservationId);
        });

        // Provider I/O is never executed in the request transaction. A reservation/provider
        // preflight runs first; only a successful preflight may dispatch provider bookings.
        $this->dispatchQueuedOnlinePurchases($reservation);

        return $reservation;
    }

    private function singleProviderId(Collection $rooms): int
    {
        $providerIds = [];

        foreach ($rooms as $room) {
            if ($room->provider_id === null) {
                throw new InvalidArgumentException(
                    "Reservation room {$room->id} has no validated provider."
                );
            }

            $providerIds[(int) $room->provider_id] = true;
        }

        if (count($providerIds) !== 1) {
            throw new InvalidArgumentException(
                'Reservation final rooms must belong to exactly one provider.'
            );
        }

        return (int) array_key_first($providerIds);
    }

    private function createPurchase(
        int $reservationHotelId,
        int $providerId,
        Collection $rooms,
        int $quotedAmount,
        int $purchaseMode,
        ?int $manualReason,
        ?int $manualRuleId,
        string $checkIn,
        string $checkOut,
    ): ReservationPurchase {
        $purchase = $this->purchases->store([
            'reservation_hotel_id' => $reservationHotelId,
            'provider_id' => $providerId,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'quoted_provider_id' => $providerId,
            'provider_quoted_amount' => $quotedAmount,
            'purchase_amount' => null,
            'confirmation_code' => null,
            'provider_status' => null,
            'expires_at' => null,
            'issued_at' => null,
            'purchase_mode' => $purchaseMode,
            'manual_reason' => $manualReason,
            'manual_rule_id' => $manualRuleId,
        ]);

        foreach ($rooms as $room) {
            $this->segments->firstOrCreateForPurchaseRoom(
                (int) $purchase->id,
                (int) $room->id,
                $checkIn,
                $checkOut,
            );
        }

        if ($purchaseMode === PurchaseMethod::OFFLINE) {
            $this->manualPurchases->firstOrCreateForPurchase((int) $purchase->id);
        }

        return $purchase;
    }

    private function quotedAmount(Collection $rooms): int
    {
        return $rooms->sum(function ($room): int {
            $amount = $room->validated_price ?? $room->initial_price;

            if ($amount === null) {
                throw new InvalidArgumentException(
                    "Reservation room {$room->id} has no validated purchase amount."
                );
            }

            return (int) $amount;
        });
    }

    private function dispatchQueuedOnlinePurchases(Reservation $reservation): void
    {
        $purchases = $reservation->hotels
            ->flatMap(fn ($hotel) => $hotel->purchases)
            ->filter(fn ($purchase): bool =>
                (int) $purchase->purchase_mode === PurchaseMethod::ONLINE
                && (int) $purchase->status === ReservationStatus::BOOK_REQUESTED
                && $purchase->provider !== null
            )
            ->unique('id')
            ->values();

        foreach ($purchases->groupBy(fn ($purchase): int => (int) $purchase->provider_id) as $providerPurchases) {
            $purchase = $providerPurchases->first();
            $provider = $purchase?->provider;
            if ($provider === null) {
                continue;
            }

            $handler = $this->providerRegistry->resolveOnlinePurchaseHandler(
                $provider,
                $this->container,
            );
            if ($handler === null) {
                continue;
            }

            PreflightOnlineReservationPurchasesJob::dispatch(
                (int) $reservation->id,
                (int) $provider->id,
            )->onQueue($handler->queue());
        }
    }

    private function reload(int $reservationId): Reservation
    {
        return $this->reservations->findForPurchase($reservationId)
            ?? throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
    }
}
