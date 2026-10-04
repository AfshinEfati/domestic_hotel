<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class SnappTripApiQuota
{
    public static function acquire(Provider $provider): void
    {
        $settings = SnappTripSettings::from($provider);
        $maxRequests = (int) data_get($settings, 'rate_limit.max_requests', 120);
        $windowSeconds = (int) data_get($settings, 'rate_limit.window_minutes', 1) * 60;
        $ttl = max(120, $windowSeconds * 2);
        $providerId = (int) $provider->id;

        try {
            Cache::store('redis')->lock(self::lockKey($providerId), 10)->block(
                5,
                function () use ($providerId, $maxRequests, $windowSeconds, $ttl): void {
                    $cooldown = self::cooldownSeconds($providerId);
                    if ($cooldown > 0) {
                        throw new SnappTripRateLimitExceeded($cooldown);
                    }

                    $now = microtime(true);
                    $windowStart = $now - $windowSeconds;
                    $requestTimes = array_values(array_filter(
                        (array) Cache::store('redis')->get(self::timesKey($providerId), []),
                        static fn ($timestamp): bool => is_numeric($timestamp) && (float) $timestamp > $windowStart,
                    ));

                    sort($requestTimes, SORT_NUMERIC);

                    if (count($requestTimes) >= $maxRequests) {
                        $retryAfter = max(
                            1,
                            (int) ceil(((float) $requestTimes[0] + $windowSeconds) - $now),
                        );
                        Cache::store('redis')->put(self::timesKey($providerId), $requestTimes, $ttl);
                        throw new SnappTripRateLimitExceeded($retryAfter);
                    }

                    $requestTimes[] = $now;
                    Cache::store('redis')->put(self::timesKey($providerId), $requestTimes, $ttl);
                }
            );
        } catch (LockTimeoutException) {
            throw new SnappTripRateLimitExceeded(1);
        }
    }

    public static function registerProvider429(Provider $provider, ?int $retryAfterSeconds = null): void
    {
        $windowSeconds = (int) data_get(SnappTripSettings::from($provider), 'rate_limit.window_minutes', 1) * 60;
        $seconds = max(1, $retryAfterSeconds ?? $windowSeconds);

        Cache::store('redis')->put(
            self::cooldownKey((int) $provider->id),
            time() + $seconds,
            $seconds,
        );
    }

    public static function cooldownSeconds(int $providerId): int
    {
        return max(
            0,
            (int) Cache::store('redis')->get(self::cooldownKey($providerId), 0) - time(),
        );
    }

    private static function timesKey(int $providerId): string
    {
        return "snapptrip-v2-api-request-times:{$providerId}";
    }

    private static function cooldownKey(int $providerId): string
    {
        return "snapptrip-v2-api-cooldown:{$providerId}";
    }

    private static function lockKey(int $providerId): string
    {
        return "snapptrip-v2-api-quota-lock:{$providerId}";
    }
}
