<?php

namespace App\Support\Reservation;

final class ReservationGuestType
{
    public const int ADULT = 1;
    public const int CHILD = 2;
    public const int INFANT = 3;

    public static function all(): array
    {
        return [
            self::ADULT,
            self::CHILD,
            self::INFANT,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::ADULT => ['name' => 'adult', 'code' => self::ADULT],
            self::CHILD => ['name' => 'child', 'code' => self::CHILD],
            self::INFANT => ['name' => 'infant', 'code' => self::INFANT],
        ];
    }

    public static function get(int $type): ?array
    {
        return self::options()[$type] ?? null;
    }
}
