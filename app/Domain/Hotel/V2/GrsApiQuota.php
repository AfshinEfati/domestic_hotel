<?php

namespace App\Domain\Hotel\V2;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Shared quota gate for background GRS HTTP traffic.
 *
 * Availability and hotel-details requests must share the same provider-wide
 * allowance. We keep a real rolling 60-second window instead of Laravel's
 * fixed decay bucket so a minute-boundary burst cannot exceed ten requests.
 */
final class GrsApiQuota
{
    private const REQUEST_TIMES = 'grs-v2-api-request-times';
    private const COOLDOWN = 'grs-v2-api-cooldown';
    private const LOCK = 'grs-v2-api-quota-lock';

    private const MAX_REQUESTS = 10;
    private const WINDOW_SECONDS = 60;
    private const REQUEST_TIMES_TTL_SECONDS = 120;
    private const PROVIDER_429_COOLDOWN_SECONDS = 300;

    public static function acquire(): void
    {
        try {
            Cache::store('redis')->lock(self::LOCK, 10)->block(5, function (): void {
                $cooldown = self::cooldownSeconds();
                if ($cooldown > 0) {
                    throw new GrsApiQuotaExceeded($cooldown);
                }

                $now = microtime(true);
                $windowStart = $now - self::WINDOW_SECONDS;
                $requestTimes = array_values(array_filter(
                    (array) Cache::store('redis')->get(self::REQUEST_TIMES, []),
                    static fn ($timestamp): bool =>
                        is_numeric($timestamp) && (float) $timestamp > $windowStart
                ));

                sort($requestTimes, SORT_NUMERIC);

                if (count($requestTimes) >= self::MAX_REQUESTS) {
                    $oldest = (float) $requestTimes[0];
                    $retryAfter = max(
                        1,
                        (int) ceil(($oldest + self::WINDOW_SECONDS) - $now)
                    );

                    Cache::store('redis')->put(
                        self::REQUEST_TIMES,
                        $requestTimes,
                        self::REQUEST_TIMES_TTL_SECONDS
                    );

                    throw new GrsApiQuotaExceeded($retryAfter);
                }

                $requestTimes[] = $now;

                Cache::store('redis')->put(
                    self::REQUEST_TIMES,
                    $requestTimes,
                    self::REQUEST_TIMES_TTL_SECONDS
                );
            });
        } catch (LockTimeoutException) {
            // Local contention must never turn into an extra provider request.
            throw new GrsApiQuotaExceeded(1);
        }
    }

    public static function registerProvider429(): void
    {
        $seconds = self::PROVIDER_429_COOLDOWN_SECONDS;

        Cache::store('redis')->put(
            self::COOLDOWN,
            time() + $seconds,
            $seconds
        );
    }

    public static function cooldownSeconds(): int
    {
        return max(
            0,
            (int) Cache::store('redis')->get(self::COOLDOWN, 0) - time()
        );
    }
}
