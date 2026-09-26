<?php

namespace App\Support\Provider;

final class ProviderRequestStatus
{
    public const SUCCESS = 1;
    public const HTTP_FAILED = 2;
    public const CONNECTION_FAILED = 3;

    public static function isValid(int $status): bool
    {
        return in_array($status, [
            self::SUCCESS,
            self::HTTP_FAILED,
            self::CONNECTION_FAILED,
        ], true);
    }
}
