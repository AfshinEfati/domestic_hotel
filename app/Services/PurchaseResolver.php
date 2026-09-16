<?php

namespace App\Services;

use App\DTOs\PurchaseResolutionDTO;
use App\Models\Reservation;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PurchaseResolver implements PurchaseResolverInterface
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly AccommodationProviderMapRepositoryInterface $accommodationProviderMapRepository,
        private readonly PurchaseManualRuleRepositoryInterface $manualRuleRepository,
    ) {}

    public function resolve(string $reservationNumber, int $providerId): PurchaseResolutionDTO
    {
        $reservation = $this->reservationRepository->findByReservationNumber($reservationNumber);

        if ($reservation === null) {
            throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationNumber]);
        }

        $finalHotels = $reservation->hotels->where('is_final', true);

        if ($finalHotels->count() !== 1) {
            throw new InvalidArgumentException('Reservation must have exactly one final hotel.');
        }

        $hotel = $finalHotels->first();
        $provider = $this->providerRepository->find($providerId);

        if ($provider === null || !$provider->is_active) {
            throw new InvalidArgumentException('Selected provider is missing or inactive.');
        }

        if ($provider->is_online) {
            if (!$this->accommodationProviderMapRepository->existsForAccommodationAndProvider(
                $hotel->accommodation_id,
                $providerId
            )) {
                throw new InvalidArgumentException('Selected provider is not mapped to the reservation hotel.');
            }
        } elseif ($provider->code !== 'hotel-'.$hotel->accommodation_id) {
            throw new InvalidArgumentException('Offline provider does not belong to the reservation hotel.');
        }

        $rule = $this->manualRuleRepository->findMatching(
            $providerId,
            $hotel->accommodation_id,
            (int) $reservation->sale_amount,
            now()->format('H:i:s')
        );

        if ($rule !== null) {
            return new PurchaseResolutionDTO(
                reservationNumber: $reservation->reservation_number,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::MATCHED_RULE,
                manualRuleId: $rule->id,
            );
        }

        if (!$provider->is_online) {
            return new PurchaseResolutionDTO(
                reservationNumber: $reservation->reservation_number,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::OFFLINE_PROVIDER,
            );
        }

        // Online eligibility is not a booking call. Fulfillment is implemented separately.
        return new PurchaseResolutionDTO(
            reservationNumber: $reservation->reservation_number,
            reservationHotelId: $hotel->id,
            providerId: $providerId,
            purchaseMode: PurchaseMethod::ONLINE,
        );
    }
}
