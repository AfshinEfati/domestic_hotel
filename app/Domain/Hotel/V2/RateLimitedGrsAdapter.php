<?php

namespace App\Domain\Hotel\V2;

use App\Domain\Hotel\Providers\GRSAdapter;
use DateTimeInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * GRS-only adapter used by V2 price refresh. Every network call is metered,
 * including the additional property detail call needed for missing room maps.
 */
class RateLimitedGrsAdapter extends GRSAdapter
{
    private const LIMITER = 'grs-availability'; // Share accounting with the legacy GRS flow.
    private const COOLDOWN = 'grs-v2-api-cooldown';

    public ?Collection $lastAvailability = null;
    public ?\Throwable $supplementalError = null;

    public function fetchAvailability(string $providerPropertyId, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $this->acquireQuota();
        try {
            return $this->lastAvailability = parent::fetchAvailability($providerPropertyId, $from, $to);
        } catch (RequestException $e) {
            $this->handleHttpError($e);
            throw $e;
        }
    }

    public function fetchRoomTypes(string $providerPropertyId): Collection
    {
        try {
            $this->acquireQuota();
            $rooms = parent::fetchRoomTypes($providerPropertyId);
            // Hotel catalog owns property metadata; this path needs maps only.
            return $rooms->map(static function (array $room): array {
                unset($room['property_facilities'], $room['property_rules']);
                return $room;
            });
        } catch (\Throwable $e) {
            // HotelSyncService catches supplemental exceptions. Remember them
            // so a partial refresh cannot be marked successful.
            $this->supplementalError = $e;
            if ($e instanceof RequestException) {
                $this->handleHttpError($e);
            }
            throw $e;
        }
    }

    public static function cooldownSeconds(): int
    {
        return max(0, (int) Cache::get(self::COOLDOWN, 0) - time());
    }

    private function acquireQuota(): void
    {
        $max = min(10, max(1, (int) data_get($this->provider->config, 'availability_rate_limit.max_requests', 10)));
        $seconds = max(60, (int) data_get($this->provider->config, 'availability_rate_limit.window_minutes', 1) * 60);

        // Serialize quota accounting across workers using the configured shared cache.
        Cache::lock('grs-v2-api-quota-lock', 10)->block(5, function () use ($max, $seconds): void {
            $cooldown = self::cooldownSeconds();
            if ($cooldown > 0) {
                throw new GrsApiQuotaExceeded($cooldown);
            }
            if (RateLimiter::tooManyAttempts(self::LIMITER, $max)) {
                throw new GrsApiQuotaExceeded(max(1, RateLimiter::availableIn(self::LIMITER)));
            }
            RateLimiter::hit(self::LIMITER, $seconds);
        });
    }

    private function handleHttpError(RequestException $e): void
    {
        if ($e->response?->status() !== 429) {
            return;
        }
        $retryAfter = $e->response->header('Retry-After');
        $configuredSeconds = GrsRefreshSettings::from($this->provider)['api_cooldown_minutes'] * 60;
        $seconds = is_numeric($retryAfter) ? max($configuredSeconds, (int) $retryAfter) : $configuredSeconds;
        Cache::put(self::COOLDOWN, time() + $seconds, $seconds);
    }
}
