<?php

namespace App\Support\Reservation;

final class ReservationGuestService
{
    public const WITH_SERVICE = 'with_service';
    public const NO_SERVICE = 'no_service';

    public static function all(): array
    {
        return [
            self::WITH_SERVICE,
            self::NO_SERVICE,
        ];
    }

    public static function isValid(string $service): bool
    {
        return in_array($service, self::all(), true);
    }
}
