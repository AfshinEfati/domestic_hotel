<?php

namespace App\Services;

use App\DTOs\PurchaseResolutionDTO;
use App\Models\Reservation;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderCreditBalanceRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
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
        private readonly ProviderCreditBalanceRepositoryInterface $creditBalanceRepository,
        private readonly ReservationManualReasonRepositoryInterface $manualReasonRepository,
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

        if ($provider === null) {
            throw new InvalidArgumentException('Selected provider was not found.');
        }

        // Provider flags may change. A previously online provider can be disabled without
        // becoming a dedicated hotel-{id} provider. Validate hotel ownership separately.
        $isHotelProvider = $provider->code === 'hotel-'.$hotel->accommodation_id;

        if (!$isHotelProvider && !$this->accommodationProviderMapRepository->existsForAccommodationAndProvider(
            $hotel->accommodation_id,
            $providerId
        )) {
            throw new InvalidArgumentException('Selected provider is not mapped to the reservation hotel.');
        }

        // Provider availability takes priority over credit and manual purchase rules.
        if (!$provider->is_active || !$provider->is_online) {
            $inactive = !$provider->is_active;

            return new PurchaseResolutionDTO(
                reservationNumber: $reservation->reservation_number,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: $inactive
                    ? PurchaseManualReason::INACTIVE_PROVIDER
                    : PurchaseManualReason::OFFLINE_PROVIDER,
                manualReasonText: $inactive
                    ? 'خرید آفلاین شد؛ تأمین‌کننده غیرفعال است.'
                    : 'خرید آفلاین شد؛ تأمین‌کننده امکان خرید آنلاین ندارد.',
            );
        }

        // A missing or never-synced credit snapshot must not be treated as sufficient.
        // All values here are IRR. Before the provider quote exists, the reservation sale
        // amount is the only persisted order amount; recheck the actual quote before buying.
        $credit = $this->creditBalanceRepository->findByProviderId($providerId);
        $orderAmount = (int) $reservation->sale_amount;

        if ($credit === null || $credit->synced_at === null) {
            return new PurchaseResolutionDTO(
                reservationNumber: $reservation->reservation_number,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::CREDIT_UNAVAILABLE,
                manualReasonText: 'خرید آفلاین شد؛ اطلاعات معتبر اعتبار تأمین‌کننده موجود نیست.',
            );
        }

        if ((int) $credit->balance < $orderAmount) {
            return new PurchaseResolutionDTO(
                reservationNumber: $reservation->reservation_number,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::INSUFFICIENT_CREDIT,
                manualReasonText: sprintf(
                    'خرید آفلاین شد؛ اعتبار تأمین‌کننده (%d ریال) از مبلغ سفارش (%d ریال) کمتر است.',
                    (int) $credit->balance,
                    $orderAmount,
                ),
            );
        }

        $rule = $this->manualRuleRepository->findMatching(
            $providerId,
            $hotel->accommodation_id,
            $orderAmount,
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
                manualReasonText: sprintf('خرید به دلیل اعمال قانون خرید دستی شماره %d آفلاین شد.', $rule->id),
            );
        }

        // This is only eligibility: no reservation purchase or provider API call happens here.
        return new PurchaseResolutionDTO(
            reservationNumber: $reservation->reservation_number,
            reservationHotelId: $hotel->id,
            providerId: $providerId,
            purchaseMode: PurchaseMethod::ONLINE,
        );
    }

    /**
     * Call when the purchase decision is committed, not for a preview. Each call that
     * selects offline purchase records a reason; purchase orchestration must make its
     * own command idempotent before invoking this method on retried requests.
     */
    public function resolveAndRecord(string $reservationNumber, int $providerId): PurchaseResolutionDTO
    {
        $resolution = $this->resolve($reservationNumber, $providerId);

        if ($resolution->purchaseMode === PurchaseMethod::OFFLINE) {
            $this->manualReasonRepository->store([
                'reservation_number' => $resolution->reservationNumber,
                'provider_id' => $resolution->providerId,
                'reason' => $resolution->manualReasonText ?? 'خرید آفلاین شد.',
            ]);
        }

        return $resolution;
    }
}
