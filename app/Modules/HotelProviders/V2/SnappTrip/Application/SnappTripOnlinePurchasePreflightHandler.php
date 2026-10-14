<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
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
        $provider = $purchases->first()?->provider;

        if ($provider === null) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::PROVIDER_MANUAL_ONLY,
                'خرید آفلاین شد؛ تأمین‌کننده اسنپ‌تریپ برای preflight خرید در دسترس نیست.',
            );
        }

        // Re-check the provider capability immediately before any external booking I/O.
        // The purchase plan may have been created before an operator disabled the provider
        // or online procurement, and SnappTrip intentionally defaults online purchase to off.
        if (!$provider->is_active) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::INACTIVE_PROVIDER,
                'خرید آفلاین شد؛ تأمین‌کننده اسنپ‌تریپ غیرفعال است.',
            );
        }

        if (!$provider->is_online) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::OFFLINE_PROVIDER,
                'خرید آفلاین شد؛ تأمین‌کننده اسنپ‌تریپ در وضعیت آفلاین است.',
            );
        }

        if (data_get(SnappTripSettings::from($provider), 'purchase.online_enabled') !== true) {
            return OnlinePurchasePreflightResult::manual(
                PurchaseManualReason::PROVIDER_MANUAL_ONLY,
                'خرید آفلاین شد؛ خرید آنلاین اسنپ‌تریپ در GDS فعال نیست.',
            );
        }

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
