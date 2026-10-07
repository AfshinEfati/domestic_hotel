<?php

namespace App\Domain\Hotel\Support;

final class ProviderRefreshStateStatus
{
    public const PENDING = 1;
    public const QUEUED = 2;
    public const PROCESSING = 3;
    public const RETRY = 4;
    public const DONE = 5;
    public const ATTEMPTED = 6;

    /** @return int[] */
    public static function terminal(): array
    {
        return [self::DONE, self::ATTEMPTED];
    }

    /** @return int[] */
    public static function claimable(): array
    {
        return [self::PENDING, self::RETRY];
    }

    public static function name(int $status): string
    {
        return match ($status) {
            self::PENDING => 'pending',
            self::QUEUED => 'queued',
            self::PROCESSING => 'processing',
            self::RETRY => 'retry',
            self::DONE => 'done',
            self::ATTEMPTED => 'attempted',
            default => 'unknown',
        };
    }
}
