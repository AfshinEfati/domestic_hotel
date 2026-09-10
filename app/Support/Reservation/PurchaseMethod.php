<?php

namespace App\Support\Reservation;

final class PurchaseMethod
{
    public const ONLINE = 1;
    public const OFFLINE = 2;

    public static function all(): array
    {
        return [self::ONLINE, self::OFFLINE];
    }

    public static function isValid(int $method): bool
    {
        return in_array($method, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::ONLINE => ['name' => 'online', 'fa_name' => 'آنلاین', 'code' => self::ONLINE],
            self::OFFLINE => ['name' => 'offline', 'fa_name' => 'آفلاین', 'code' => self::OFFLINE],
        ];
    }
}
