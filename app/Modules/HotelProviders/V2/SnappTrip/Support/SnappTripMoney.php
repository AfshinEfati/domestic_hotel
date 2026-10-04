<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use RuntimeException;

final class SnappTripMoney
{
    /** @var string[] */
    private const PROVIDER_MONEY_FIELDS = [
        'price',
        'price_off',
        'child_price',
        'infant_price',
        'extra_bed_price',
        'extra_foreigner_price',
        'discount',
        'discount_amount',
        'original_sell_price',
        'balance',
        'service_fee',
        'user_penalty',
        'user_penalty_total',
        'user_refund_amount',
    ];

    public static function toInternal(mixed $toman): ?int
    {
        if ($toman === null || $toman === '') {
            return null;
        }

        if (!is_numeric($toman) || (int) $toman < 0) {
            throw new RuntimeException('SnappTrip returned an invalid Toman amount.');
        }

        $amount = (int) $toman;

        if ($amount > intdiv(PHP_INT_MAX, 10)) {
            throw new RuntimeException('SnappTrip amount exceeds the supported IRR range.');
        }

        return $amount * 10;
    }

    public static function toProvider(mixed $rial): ?int
    {
        if ($rial === null || $rial === '') {
            return null;
        }

        if (!is_numeric($rial) || (int) $rial < 0) {
            throw new RuntimeException('Internal IRR amount is invalid for SnappTrip conversion.');
        }

        return intdiv((int) $rial, 10);
    }

    /**
     * Normalize monetary fields in raw SnappTrip response shapes that are exposed by
     * the module without a dedicated canonical mapper. Keys not listed as provider
     * money fields, such as discount_percent or user_penalty_percent, are untouched.
     */
    public static function normalizeProviderPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::normalizeProviderPayload($value);
                continue;
            }

            if (in_array((string) $key, self::PROVIDER_MONEY_FIELDS, true)) {
                $payload[$key] = self::toInternal($value);
            }
        }

        return $payload;
    }
}
