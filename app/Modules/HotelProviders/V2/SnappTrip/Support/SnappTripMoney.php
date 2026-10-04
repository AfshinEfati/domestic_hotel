<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use RuntimeException;

final class SnappTripMoney
{
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
}
