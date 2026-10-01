<?php

namespace App\Domain\Hotel\V2;

use App\Domain\Hotel\Providers\GRSAdapter;
use Closure;
use DateTimeInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/** Both availability and the necessary supplemental room request use one API quota. */
class RateLimitedGrsAdapter extends GRSAdapter
{
    private const LIMITER = 'grs-availability';
    private const COOLDOWN = 'grs-v2-api-cooldown';
    private const MAX_REQUESTS_PER_MINUTE = 10;
    private const RATE_WINDOW_SECONDS = 60;
    private const PROVIDER_429_COOLDOWN_SECONDS = 120;

    public ?Collection $lastAvailability = null;
    public ?\Throwable $supplementalError = null;

    private ?Closure $availabilityStarting = null;
    private ?Closure $availabilitySucceeded = null;

    public function trackAvailability(Closure $starting, Closure $succeeded): void
    {
        $this->availabilityStarting = $starting;
        $this->availabilitySucceeded = $succeeded;
    }

    public function fetchAvailability(string $providerPropertyId, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $this->acquireQuota();
        try {
            // Quota is acquired before recording the actual HTTP attempt.
            if ($this->availabilityStarting !== null) {
                ($this->availabilityStarting)();
            }
            $availability = parent::fetchAvailability($providerPropertyId, $from, $to);
            // The parent uses ->throw(): reaching this line means its HTTP GET
            // returned successfully, regardless of later calendar persistence.
            if ($this->availabilitySucceeded !== null) {
                ($this->availabilitySucceeded)();
            }
            return $this->lastAvailability = $availability;
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
            // Catalog metadata belongs to the separate hotel catalog flow.
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
        Cache::lock('grs-v2-api-quota-lock', 10)->block(5, function (): void {
            $cooldown = self::cooldownSeconds();
            if ($cooldown > 0) {
                throw new GrsApiQuotaExceeded($cooldown);
            }

            if (RateLimiter::tooManyAttempts(self::LIMITER, self::MAX_REQUESTS_PER_MINUTE)) {
                throw new GrsApiQuotaExceeded(
                    max(1, RateLimiter::availableIn(self::LIMITER))
                );
            }

            RateLimiter::hit(self::LIMITER, self::RATE_WINDOW_SECONDS);
        });
    }

    private function handleHttpError(RequestException $e): void
    {
        if ($e->response?->status() !== 429) {
            return;
        }
        // This provider does not return a usable Retry-After value. A real
        // provider-side 429 pauses all GRS availability traffic for exactly
        // two minutes. During the cooldown acquireQuota() rejects locally,
        // therefore no HTTP request is sent to the provider.
        $seconds = self::PROVIDER_429_COOLDOWN_SECONDS;

        Cache::put(self::COOLDOWN, time() + $seconds, $seconds);
    }
}
