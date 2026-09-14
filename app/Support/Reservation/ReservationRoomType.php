<?php

namespace App\Support\Reservation;

final class ReservationRoomType
{
    public const int REQUESTED = 1;
    public const int ALTERNATIVE = 2;

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
            self::REQUESTED => ['name' => 'requested', 'fa_name' => 'اتاق درخواستی', 'code' => self::REQUESTED],
            self::ALTERNATIVE => ['name' => 'alternative', 'fa_name' => 'اتاق جایگزین', 'code' => self::ALTERNATIVE],
        ];
    }
}
