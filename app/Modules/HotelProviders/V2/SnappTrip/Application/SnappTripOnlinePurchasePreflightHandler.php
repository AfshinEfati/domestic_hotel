<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use App\Support\Reservation\PurchaseManualReason;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use RuntimeException;

final readonly class SnappTripOnlinePurchasePreflightHandler implements OnlinePurchasePreflightHandler
{
    public function __construct(
        private SnappTripPurchaseBalancePreflight $balancePreflight,
    ) {
    }

    /** @param Collection<int, ReservationPurchase> $purchases */
    public function check(Collection $purchases): OnlinePurchasePreflightResult
    {
        try {
            $result = $this->balancePreflight->check($purchases);
        } catch (ConnectionException|RequestException|RuntimeException) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::CREDIT_UNAVAILABLE,
                'خرید آفلاین شد؛ دریافت اعتبار به‌روز اسنپ‌تریپ پیش از رزرو ممکن نبود.',
            );
        }

        if (!$result->sufficient()) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::INSUFFICIENT_CREDIT,
                sprintf(
                    'خرید آفلاین شد؛ اعتبار به‌روز اسنپ‌تریپ (%d ریال) از مبلغ کل خرید (%d ریال) کمتر است.',
                    $result->balance,
                    $result->requiredAmount,
                ),
            );
        }

        return OnlinePurchasePreflightResult::allowed();
    }
}
