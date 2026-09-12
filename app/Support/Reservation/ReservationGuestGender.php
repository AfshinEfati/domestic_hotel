<?php

namespace App\Support\Reservation;

final class ReservationGuestGender
{
    public const MALE = 1;
    public const FEMALE = 2;

    public static function all(): array
    {
        return [
            self::MALE,
            self::FEMALE,
        ];
    }

    public static function isValid(int $gender): bool
    {
        return in_array($gender, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::MALE => ['name' => 'male', 'code' => self::MALE],
            self::FEMALE => ['name' => 'female', 'code' => self::FEMALE],
        ];
    }

    public static function get(int $gender): ?array
    {
        return self::options()[$gender] ?? null;
    }
}
