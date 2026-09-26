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

    public function resolve(int $reservationId, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO
    {
        $reservation = $this->reservationRepository->findWithDetails($reservationId);

        if ($reservation === null) {
            throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
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
                reservationId: (int) $reservation->id,
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

        // Rules apply to this concrete provider purchase amount, not blindly to the
        // whole reservation. A global rule (all nullable dimensions) matches every purchase.
        $orderAmount ??= (int) $reservation->sale_amount;

        $rule = $this->manualRuleRepository->findMatching(
            $providerId,
            $hotel->accommodation_id,
            $orderAmount,
            now('Asia/Tehran')->format('H:i:s')
        );

        if ($rule !== null) {
            return new PurchaseResolutionDTO(
                reservationId: (int) $reservation->id,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::MATCHED_RULE,
                manualRuleId: $rule->id,
                manualReasonText: sprintf('خرید به دلیل اعمال قانون خرید دستی شماره %d آفلاین شد.', $rule->id),
            );
        }

        // Credit matters only when no explicit manual rule already forced offline.
        // All values here are IRR.
        $credit = $this->creditBalanceRepository->findByProviderId($providerId);

        if ($credit === null || $credit->synced_at === null) {
            return new PurchaseResolutionDTO(
                reservationId: (int) $reservation->id,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::CREDIT_UNAVAILABLE,
                manualReasonText: 'خرید آفلاین شد؛ اطلاعات معتبر اعتبار تأمین‌کننده موجود نیست.',
            );
        }

        if ((int) $credit->balance < $orderAmount) {
            return new PurchaseResolutionDTO(
                reservationId: (int) $reservation->id,
                reservationHotelId: $hotel->id,
                providerId: $providerId,
                purchaseMode: PurchaseMethod::OFFLINE,
                manualReason: PurchaseManualReason::INSUFFICIENT_CREDIT,
                manualReasonText: sprintf(
                    'خرید آفلاین شد؛ اعتبار تأمین‌کننده (%d ریال) از مبلغ خرید (%d ریال) کمتر است.',
                    (int) $credit->balance,
                    $orderAmount,
                ),
            );
        }

        // This is only eligibility: no reservation purchase or provider API call happens here.
        return new PurchaseResolutionDTO(
            reservationId: (int) $reservation->id,
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
    public function resolveAndRecord(int $reservationId, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO
    {
        $resolution = $this->resolve($reservationId, $providerId, $orderAmount);

        if ($resolution->purchaseMode === PurchaseMethod::OFFLINE) {
            $this->manualReasonRepository->store([
                'reservation_id' => $resolution->reservationId,
                'reason' => $resolution->manualReasonText ?? 'خرید آفلاین شد.',
            ]);
        }

        return $resolution;
    }
}
