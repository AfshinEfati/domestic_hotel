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
            return parent::fetchRoomTypes($providerPropertyId);
        } catch (\Throwable $e) {
            // The existing HotelSyncService catches supplemental exceptions.
            // Remember them so the caller cannot report a partial refresh as a success.
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

        // Atomic across workers if CACHE_STORE uses database or Redis; use a
        // single queue worker and a shared cache store in production.
        Cache::lock('grs-v2-api-quota-lock', 10)->block(5, function () use ($max, $seconds): void {
            $cooldown = self::cooldownSeconds();
            if ($cooldown > 0) {
                throw new GrsApiQuotaExceeded($cooldown);
            }
            if (RateLimiter::tooManyAttempts(self::LIMITER, $max)) {
                throw new GrsApiQuotaExceeded(max(1, RateLimiter::availableIn(self::LIMITER)));
            }
            // Charge quota before sending the request, including unsuccessful HTTP requests.
            RateLimiter::hit(self::LIMITER, $seconds);
        });
    }

    private function handleHttpError(RequestException $e): void
    {
        if ($e->response?->status() !== 429) {
            return;
        }
        $retryAfter = $e->response->header('Retry-After');
        $seconds = is_numeric($retryAfter) ? max(900, (int) $retryAfter) : 900;
        Cache::put(self::COOLDOWN, time() + $seconds, $seconds);
    }
}
