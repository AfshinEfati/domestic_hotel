<?php

namespace App\Domain\Hotel\V2;

use App\Models\Provider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Shared quota gate for background GRS HTTP traffic.
 *
 * Availability and hotel-details requests share the same provider-wide
 * allowance. The configured provider value is used when present; otherwise
 * GRS keeps the historical default of 10 requests per minute.
 *
 * The existing providers.config.availability_rate_limit key is retained for
 * backwards compatibility, but it is treated as the global background GRS
 * API quota rather than an availability-only limiter.
 */
final class GrsApiQuota
{
    private const REQUEST_TIMES = 'grs-v2-api-request-times';
    private const COOLDOWN = 'grs-v2-api-cooldown';
    private const LOCK = 'grs-v2-api-quota-lock';

    private const DEFAULT_MAX_REQUESTS = 10;
    private const DEFAULT_WINDOW_MINUTES = 1;
    private const PROVIDER_429_COOLDOWN_SECONDS = 300;

    public static function acquire(Provider $provider): void
    {
        $maxRequests = self::maxRequests($provider);
        $windowSeconds = self::windowSeconds($provider);
        $requestTimesTtl = max(120, $windowSeconds * 2);

        try {
            Cache::lock(self::LOCK, 10)->block(
                5,
                function () use ($maxRequests, $windowSeconds, $requestTimesTtl): void {
                    $cooldown = self::cooldownSeconds();
                    if ($cooldown > 0) {
                        throw new GrsApiQuotaExceeded($cooldown);
                    }

                    $now = microtime(true);
                    $windowStart = $now - $windowSeconds;
                    $requestTimes = array_values(array_filter(
                        (array) Cache::get(self::REQUEST_TIMES, []),
                        static fn ($timestamp): bool =>
                            is_numeric($timestamp) && (float) $timestamp > $windowStart
                    ));

                    sort($requestTimes, SORT_NUMERIC);

                    if (count($requestTimes) >= $maxRequests) {
                        $oldest = (float) $requestTimes[0];
                        $retryAfter = max(
                            1,
                            (int) ceil(($oldest + $windowSeconds) - $now)
                        );

                        Cache::put(
                            self::REQUEST_TIMES,
                            $requestTimes,
                            $requestTimesTtl
                        );

                        throw new GrsApiQuotaExceeded($retryAfter);
                    }

                    $requestTimes[] = $now;

                    Cache::put(
                        self::REQUEST_TIMES,
                        $requestTimes,
                        $requestTimesTtl
                    );
                }
            );
        } catch (LockTimeoutException) {
            // Local contention must never turn into an extra provider request.
            throw new GrsApiQuotaExceeded(1);
        }
    }

    public static function maxRequests(Provider $provider): int
    {
        $configured = data_get(
            $provider->config,
            'availability_rate_limit.max_requests'
        );

        if (!is_numeric($configured) || (int) $configured < 1) {
            return self::DEFAULT_MAX_REQUESTS;
        }

        return (int) $configured;
    }

    public static function windowMinutes(Provider $provider): int
    {
        $configured = data_get(
            $provider->config,
            'availability_rate_limit.window_minutes'
        );

        if (!is_numeric($configured) || (int) $configured < 1) {
            return self::DEFAULT_WINDOW_MINUTES;
        }

        return (int) $configured;
    }

    public static function registerProvider429(): void
    {
        $seconds = self::PROVIDER_429_COOLDOWN_SECONDS;

        Cache::put(
            self::COOLDOWN,
            time() + $seconds,
            $seconds
        );
    }

    public static function cooldownSeconds(): int
    {
        return max(
            0,
            (int) Cache::get(self::COOLDOWN, 0) - time()
        );
    }

    private static function windowSeconds(Provider $provider): int
    {
        return self::windowMinutes($provider) * 60;
    }
}
