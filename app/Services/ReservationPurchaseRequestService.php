<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPurchase;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\Contracts\ReservationPurchaseRequestServiceInterface;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ReservationPurchaseRequestService implements ReservationPurchaseRequestServiceInterface
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ReservationPurchaseRepositoryInterface $purchases,
        private ReservationPurchaseSegmentRepositoryInterface $segments,
        private ReservationManualPurchaseRepositoryInterface $manualPurchases,
        private PurchaseResolverInterface $resolver,
    ) {}

    public function request(string $reservationNumber): Reservation
    {
        return DB::transaction(function () use ($reservationNumber): Reservation {
            $reservation = $this->reservations
                ->findByReservationNumberForPurchase($reservationNumber, true);

            if ($reservation === null) {
                throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationNumber]);
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

            // A repeated whitelist callback after a successful first request must be harmless.
            if ((int) $reservation->status === ReservationStatus::BOOK_REQUESTED
                && $hotel->purchases->isNotEmpty()) {
                return $this->reload($reservationNumber);
            }

            if ((int) $reservation->status !== ReservationStatus::READY_FOR_PAYMENT) {
                throw new InvalidArgumentException('Reservation is not ready to receive a purchase request.');
            }

            foreach ($rooms as $room) {
                if ($room->provider_id === null) {
                    throw new InvalidArgumentException(
                        "Reservation room {$room->id} has no validated provider."
                    );
                }
            }

            $roomsByProvider = $rooms->groupBy(
                fn ($room): int => (int) $room->provider_id
            );

            foreach ($roomsByProvider as $providerId => $providerRooms) {
                $providerId = (int) $providerId;

                $quotedAmount = $providerRooms->sum(function ($room): int {
                    $amount = $room->validated_price ?? $room->initial_price;

                    if ($amount === null) {
                        throw new InvalidArgumentException(
                            "Reservation room {$room->id} has no validated purchase amount."
                        );
                    }

                    return (int) $amount;
                });

                $purchase = $this->purchases->findByHotelAndProvider($hotel->id, $providerId);

                if ($purchase === null) {
                    $resolution = $this->resolver->resolveAndRecord(
                        $reservationNumber,
                        $providerId,
                        $quotedAmount,
                    );

                    $purchase = $this->purchases->store([
                        'reservation_hotel_id' => $hotel->id,
                        'provider_id' => $providerId,
                        'status' => ReservationStatus::BOOK_REQUESTED,
                        'quoted_provider_id' => $providerId,
                        'provider_quoted_amount' => $quotedAmount,
                        'purchase_amount' => null,
                        'confirmation_code' => null,
                        'provider_status' => null,
                        'expires_at' => null,
                        'issued_at' => null,
                        'purchase_mode' => $resolution->purchaseMode,
                        'manual_reason' => $resolution->manualReason,
                        'manual_rule_id' => $resolution->manualRuleId,
                    ]);
                }

                foreach ($providerRooms as $room) {
                    $this->segments->firstOrCreateForPurchaseRoom(
                        (int) $purchase->id,
                        (int) $room->id,
                        $reservation->check_in->format('Y-m-d'),
                        $reservation->check_out->format('Y-m-d'),
                    );
                }

                if ((int) $purchase->purchase_mode === PurchaseMethod::OFFLINE) {
                    $this->manualPurchases->firstOrCreateForPurchase((int) $purchase->id);
                }
            }

            $this->reservations->update($reservation->id, [
                'status' => ReservationStatus::BOOK_REQUESTED,
            ]);

            return $this->reload($reservationNumber);
        });
    }

    private function reload(string $reservationNumber): Reservation
    {
        return $this->reservations->findByReservationNumberForPurchase($reservationNumber)
            ?? throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationNumber]);
    }
}
