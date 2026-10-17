<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use RuntimeException;

final class SnappTripMoney
{
    /** @var string[] */
    private const PROVIDER_MONEY_FIELDS = [
        'price',
        'price_off',
        'min_price',
        'max_price',
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

    /** @var string[] */
    private const PROVIDER_REQUEST_MONEY_FIELDS = [
        'min_price',
        'max_price',
    ];

    public static function toInternal(mixed $toman): ?int
    {
        if ($toman === null || $toman === '') {
            return null;
        }

        if (!is_numeric($toman) || (int) $toman < 0) {
            throw new RuntimeException('SnappTrip returned an invalid Toman amount.');
        }

        return self::tomanToIrr((int) $toman);
    }

    /**
     * Convert a provider amount that is allowed to be negative, such as account balance.
     * Normal prices, penalties and refund amounts continue to use toInternal() and must
     * remain non-negative.
     */
    public static function toInternalSigned(mixed $toman): ?int
    {
        if ($toman === null || $toman === '') {
            return null;
        }

        if (!is_numeric($toman)) {
            throw new RuntimeException('SnappTrip returned an invalid signed Toman amount.');
        }

        return self::tomanToIrr((int) $toman);
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
     * the module without a dedicated canonical mapper. Percentage fields are untouched.
     */
    public static function normalizeProviderPayload(array $payload): array
    {
        return self::normalize($payload, self::PROVIDER_MONEY_FIELDS, true);
    }

    /**
     * Convert internal IRR request filters to the Toman amounts required by SnappTrip.
     * Currently the documented City Search request is the only SnappTrip request body
     * that accepts monetary filters.
     */
    public static function normalizeRequestPayload(array $payload): array
    {
        return self::normalize($payload, self::PROVIDER_REQUEST_MONEY_FIELDS, false);
    }

    private static function normalize(array $payload, array $fields, bool $toInternal): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::normalize($value, $fields, $toInternal);
                continue;
            }

            if (in_array((string) $key, $fields, true)) {
                if ($toInternal && (string) $key === 'balance') {
                    $payload[$key] = self::toInternalSigned($value);
                    continue;
                }

                $payload[$key] = $toInternal
                    ? self::toInternal($value)
                    : self::toProvider($value);
            }
        }

        return $payload;
    }

    private static function tomanToIrr(int $amount): int
    {
        if (
            $amount > intdiv(PHP_INT_MAX, 10)
            || $amount < intdiv(PHP_INT_MIN, 10)
        ) {
            throw new RuntimeException('SnappTrip amount exceeds the supported IRR range.');
        }

        return $amount * 10;
    }
}
