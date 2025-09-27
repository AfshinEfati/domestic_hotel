<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Support\Logging\SystemLogger;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SyncGrsAvailabilityForPropertyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RATE_LIMITER_KEY = 'grs-availability';
    private const RATE_LIMITER_DECAY_SECONDS = 60;

    public function __construct(
        public int $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int $maxAttempts,
        public int $throttleMs,
        public int $requestsPerMinute
    ) {
    }

    public function handle(HotelSyncService $service, SystemLogger $logger): void
    {
        $provider = Provider::find($this->providerId);
        if (!$provider) {
            $logger->warning(__METHOD__, 'SyncGrsAvailabilityForPropertyJob skipped because provider not found', [
                'provider_id' => $this->providerId,
                'provider_property_id' => $this->providerPropertyId,
            ]);

            return;
        }

        $propertyKey = trim($this->providerPropertyId);
        if ($propertyKey === '') {
            $logger->warning(__METHOD__, 'SyncGrsAvailabilityForPropertyJob skipped because provider property id is empty', [
                'provider_id' => $this->providerId,
            ]);

            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->makeWith(ProviderAdapterInterface::class, [
            'provider' => $provider,
        ]);

        $from = CarbonImmutable::parse($this->fromDate);
        $to = CarbonImmutable::parse($this->toDate);

        $this->syncPropertyWithRetry(
            $service,
            $provider,
            $adapter,
            $propertyKey,
            $from,
            $to,
            max(1, $this->maxAttempts),
            max(0, $this->throttleMs),
            max(0, $this->requestsPerMinute),
            $logger
        );
    }

    private function syncPropertyWithRetry(
        HotelSyncService $service,
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $requestsPerMinute,
        SystemLogger $logger
    ): void {
        $attempt = 0;
        $lastRequestAt = null;
        $minimumIntervalMs = $this->calculateMinimumIntervalMs($requestsPerMinute);

        while ($attempt < $maxAttempts) {
            $attempt++;

            if (
                $requestsPerMinute > 0 &&
                $this->shouldDelayForRateLimit(
                    self::RATE_LIMITER_KEY,
                    $requestsPerMinute,
                    $minimumIntervalMs,
                    $provider,
                    $propertyKey,
                    $attempt,
                    $logger
                )
            ) {
                return;
            }

            $this->enforceThrottle($lastRequestAt, $throttleMs);

            if ($requestsPerMinute > 0) {
                RateLimiter::hit(self::RATE_LIMITER_KEY, self::RATE_LIMITER_DECAY_SECONDS);
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
                $logger->warning(__METHOD__, 'Failed to sync GRS availability for property', [
                    'provider_id' => $provider->id,
                    'provider_property_id' => $propertyKey,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);

                if ($attempt >= $maxAttempts) {
                    $logger->error(__METHOD__, 'Abandoning GRS availability sync after max attempts', [
                        'provider_id' => $provider->id,
                        'provider_property_id' => $propertyKey,
                        'attempts' => $attempt,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    private function enforceThrottle(?float &$lastRequestAt, int $throttleMs): void
    {
        if ($throttleMs <= 0) {
            $lastRequestAt = microtime(true);

            return;
        }

        if ($lastRequestAt !== null) {
            $elapsedMs = (microtime(true) - $lastRequestAt) * 1000;
            $remaining = (int)max(0, ($throttleMs - $elapsedMs) * 1000);
            if ($remaining > 0) {
                usleep($remaining);
            }
        }

        $lastRequestAt = microtime(true);
    }

    private function shouldDelayForRateLimit(
        string $limiterKey,
        int $requestsPerMinute,
        int $minimumIntervalMs,
        Provider $provider,
        string $propertyKey,
        int $attempt,
        SystemLogger $logger
    ): bool {
        if (!RateLimiter::tooManyAttempts($limiterKey, $requestsPerMinute)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn($limiterKey);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds, $minimumIntervalMs);

        $logger->info(__METHOD__, 'Delaying GRS availability sync due to provider rate limit', [
            'provider_id' => $provider->id,
            'provider_property_id' => $propertyKey,
            'attempt' => $attempt,
            'requests_per_minute' => $requestsPerMinute,
            'rate_limiter_key' => $limiterKey,
            'rate_limit_delay_seconds' => $delaySeconds,
            'available_in_seconds' => $availableInSeconds,
        ]);

        $this->release($delaySeconds);

        return true;
    }

    private function calculateMinimumIntervalMs(int $requestsPerMinute): int
    {
        if ($requestsPerMinute <= 0) {
            return 0;
        }

        return (int)ceil(60000 / $requestsPerMinute);
    }

    private function determineRateLimitDelaySeconds(?int $availableInSeconds, int $minimumIntervalMs): int
    {
        $baseDelaySeconds = max(1, (int)ceil(max(0, $minimumIntervalMs) / 1000));

        if ($availableInSeconds === null) {
            return $baseDelaySeconds;
        }

        $availableInSeconds = (int)max(0, $availableInSeconds);

        if ($availableInSeconds === 0) {
            return $baseDelaySeconds;
        }

        return max($baseDelaySeconds, $availableInSeconds);
    }
}
