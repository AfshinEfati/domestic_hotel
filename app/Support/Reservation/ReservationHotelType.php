<?php

namespace App\Support\Reservation;

final class ReservationHotelType
{
    public const REQUESTED = 1;
    public const ALTERNATIVE = 2;

    public static function all(): array
    {
        return [self::REQUESTED, self::ALTERNATIVE];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::REQUESTED => ['name' => 'requested', 'fa_name' => 'هتل درخواستی', 'code' => self::REQUESTED],
            self::ALTERNATIVE => ['name' => 'alternative', 'fa_name' => 'هتل جایگزین', 'code' => self::ALTERNATIVE],
        ];
    }
}
