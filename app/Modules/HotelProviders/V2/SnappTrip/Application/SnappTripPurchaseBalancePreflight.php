<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\SnappTrip\Application\Contracts\BalanceSynchronizer;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final readonly class SnappTripPurchaseBalancePreflight
{
    public function __construct(private BalanceSynchronizer $balanceSynchronizer)
    {
    }

    /** @param Collection<int, ReservationPurchase> $purchases */
    public function check(Collection $purchases): SnappTripPurchaseBalancePreflightResult
    {
        if ($purchases->isEmpty()) {
            throw new InvalidArgumentException('SnappTrip purchase balance preflight requires at least one purchase.');
        }

        $requiredAmount = 0;

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof ReservationPurchase) {
                throw new InvalidArgumentException('SnappTrip purchase balance preflight received an invalid purchase.');
            }

            if ($purchase->provider_quoted_amount === null) {
                throw new InvalidArgumentException(
                    "Reservation purchase {$purchase->id} has no quoted provider amount."
                );
            }

            $amount = (int) $purchase->provider_quoted_amount;
            if ($amount < 0) {
                throw new InvalidArgumentException(
                    "Reservation purchase {$purchase->id} has an invalid quoted provider amount."
                );
            }

            $requiredAmount += $amount;
        }

        return new SnappTripPurchaseBalancePreflightResult(
            balance: $this->balanceSynchronizer->execute(),
            requiredAmount: $requiredAmount,
        );
    }
}
