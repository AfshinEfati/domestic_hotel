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

class SyncGrsAvailabilityForPropertyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RATE_LIMITER_KEY = 'grs-availability';
    private const MAX_GRS_REQUESTS_PER_WINDOW = 10;
    private const MAX_TRIES = 2;

    public int $tries = 1;
    public int $maxExceptions = 1;

    public function __construct(
        public int $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int $maxAttempts,
        public int $throttleMs,
        public int $maxRequests,
        public int $windowMinutes = 1,
    ) {
        $this->maxAttempts = $this->resolveMaxAttempts($maxAttempts);
        $this->throttleMs = max(0, $throttleMs);
        $this->maxRequests = min(self::MAX_GRS_REQUESTS_PER_WINDOW, max(1, $maxRequests));
        $this->windowMinutes = max(1, $windowMinutes);
    }

    public function uniqueId(): string
    {
        return $this->providerPropertyId.'_'.$this->fromDate.'_'.$this->toDate;
    }

    /** @return array<string> */
    public function tags(): array
    {
        return [
            'sync',
            'grs',
            'provider:'.$this->providerId,
            'property:'.$this->providerPropertyId,
        ];
    }

    public function handle(HotelSyncService $service): void
    {
        $provider = Provider::find($this->providerId);
        if (!$provider || $provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
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

        while ($attempt < $this->maxAttempts) {
            if ($this->shouldDelayForRateLimit()) {
                return;
            }

            $attempt++;
            $this->enforceThrottle($lastRequestAt, $this->throttleMs);
            RateLimiter::hit(self::RATE_LIMITER_KEY, $this->windowMinutes * 60);

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

    private function shouldDelayForRateLimit(): bool
    {
        if (!RateLimiter::tooManyAttempts(self::RATE_LIMITER_KEY, $this->maxRequests)) {
            return false;
        }

        $this->release(
            $this->determineRateLimitDelaySeconds(
                RateLimiter::availableIn(self::RATE_LIMITER_KEY)
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
            $remainingMicroseconds = (int) max(0, ($throttleMs - $elapsedMs) * 1000);

            if ($remainingMicroseconds > 0) {
                usleep($remainingMicroseconds);
            }
        }

        $lastRequestAt = microtime(true);
    }

    private function determineRateLimitDelaySeconds(?int $availableInSeconds): int
    {
        $windowSeconds = max(60, $this->windowMinutes * 60);

        if ($availableInSeconds === null || $availableInSeconds <= 0) {
            return $windowSeconds;
        }

        return max(1, $availableInSeconds);
    }

    private function resolveMaxAttempts(int $maxAttempts): int
    {
        return min(max(1, $maxAttempts), self::MAX_TRIES);
    }
}
