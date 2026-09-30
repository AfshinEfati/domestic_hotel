<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SyncProviderAvailabilityForPropertyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RATE_LIMITER_PREFIX = 'provider-availability';
    private const RATE_LIMITER_DECAY_SECONDS = 60;
    private const MAX_TRIES = 2;

    public int $uniqueFor = 60;
    public int $tries = 1;
    public int $maxExceptions = 1;

    public function __construct(
        public int $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int $maxAttempts,
        public int $throttleMs,
        public int $requestsPerMinute,
    ) {
        $this->maxAttempts = $this->resolveMaxAttempts($maxAttempts);
        $this->throttleMs = max(0, $throttleMs);
        $this->requestsPerMinute = max(0, $requestsPerMinute);
    }

    public function uniqueId(): string
    {
        return $this->providerId.'_'.$this->providerPropertyId.'_'.$this->fromDate.'_'.$this->toDate;
    }

    /** @return array<string> */
    public function tags(): array
    {
        return [
            'sync',
            'provider-availability',
            'provider:'.$this->providerId,
            'property:'.$this->providerPropertyId,
        ];
    }

    public function handle(HotelSyncService $service): void
    {
        $provider = Provider::find($this->providerId);
        if (!$provider || !$provider->is_active || !$provider->is_online) {
            return;
        }

        $propertyKey = trim($this->providerPropertyId);
        if ($propertyKey === '') {
            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->makeWith(ProviderAdapterInterface::class, [
            'provider' => $provider,
        ]);

        $this->syncPropertyWithRetry(
            $service,
            $provider,
            $adapter,
            $propertyKey,
            CarbonImmutable::parse($this->fromDate),
            CarbonImmutable::parse($this->toDate),
        );
    }

    private function syncPropertyWithRetry(
        HotelSyncService $service,
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): void {
        $attempt = 0;
        $lastRequestAt = null;
        $minimumIntervalMs = $this->calculateMinimumIntervalMs($this->requestsPerMinute);
        $limiterKey = $this->resolveRateLimiterKey($propertyKey);

        while ($attempt < $this->maxAttempts) {
            if (
                $this->requestsPerMinute > 0
                && $this->shouldDelayForRateLimit($limiterKey, $minimumIntervalMs)
            ) {
                return;
            }

            $attempt++;
            $this->enforceThrottle($lastRequestAt, $this->throttleMs);

            if ($this->requestsPerMinute > 0) {
                RateLimiter::hit($limiterKey, self::RATE_LIMITER_DECAY_SECONDS);
            }

            try {
                $service->crawlAvailabilityForProperty(
                    $provider,
                    $adapter,
                    $propertyKey,
                    $from,
                    $to
                );

                return;
            } catch (Throwable $exception) {
                if ($attempt >= $this->maxAttempts) {
                    throw $exception;
                }
            }
        }
    }

    private function shouldDelayForRateLimit(string $limiterKey, int $minimumIntervalMs): bool
    {
        if (!RateLimiter::tooManyAttempts($limiterKey, $this->requestsPerMinute)) {
            return false;
        }

        $this->release(
            $this->determineRateLimitDelaySeconds(
                RateLimiter::availableIn($limiterKey),
                $minimumIntervalMs
            )
        );

        return true;
    }

    private function enforceThrottle(?float &$lastRequestAt, int $throttleMs): void
    {
        if ($throttleMs <= 0) {
            $lastRequestAt = microtime(true);
            return;
        }

        if ($lastRequestAt !== null) {
            $elapsedMs = (microtime(true) - $lastRequestAt) * 1000;
            $remaining = (int) max(0, ($throttleMs - $elapsedMs) * 1000);

            if ($remaining > 0) {
                usleep($remaining);
            }
        }

        $lastRequestAt = microtime(true);
    }

    private function resolveRateLimiterKey(string $propertyKey): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-]/', '-', $propertyKey);
        if ($normalized === null || $normalized === '') {
            $normalized = substr(hash('sha256', $propertyKey), 0, 16);
        }

        return implode(':', [
            self::RATE_LIMITER_PREFIX,
            'provider-'.$this->providerId,
            'property-'.$normalized,
        ]);
    }

    private function calculateMinimumIntervalMs(int $requestsPerMinute): int
    {
        return $requestsPerMinute <= 0
            ? 0
            : (int) ceil(60000 / $requestsPerMinute);
    }

    private function determineRateLimitDelaySeconds(?int $availableInSeconds, int $minimumIntervalMs): int
    {
        $baseDelaySeconds = max(1, (int) ceil(max(0, $minimumIntervalMs) / 1000));

        if ($availableInSeconds === null || $availableInSeconds <= 0) {
            return $baseDelaySeconds;
        }

        return max($baseDelaySeconds, $availableInSeconds);
    }

    private function resolveMaxAttempts(int $maxAttempts): int
    {
        return min(max(1, $maxAttempts), self::MAX_TRIES);
    }
}
