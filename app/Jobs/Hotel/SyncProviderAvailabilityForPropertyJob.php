<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Models\JobErrorLog;
use App\Models\Provider;
use App\Support\Http\HttpExceptionContext;
use App\Support\Logging\SystemLogger;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;

class SyncProviderAvailabilityForPropertyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const RATE_LIMITER_PREFIX = 'provider-availability';
    private const RATE_LIMITER_DECAY_SECONDS = 60;
    private const LOG_CONTENT_LIMIT = 2048;
    private const MAX_TRIES = 2;

    /**
     * The number of minutes the job should be unique.
     * This ensures that no duplicate jobs run while an instance is still in progress or recently completed.
     */
    public int $uniqueFor = 60; // Keep unique for 1 hour

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;
    public int $maxExceptions = 1; // Only allow one exception

    /**
     * Get the unique ID for the job.
     * This ID is used to prevent duplicate jobs from running simultaneously.
     */
    public function uniqueId(): string
    {
        return $this->providerId . '_' . $this->providerPropertyId . '_' . $this->fromDate . '_' . $this->toDate;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'sync',
            'provider-availability',
            'provider:' . $this->providerId,
            'property:' . $this->providerPropertyId,
        ];
    }

    public function __construct(
        public int    $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int    $maxAttempts,
        public int    $throttleMs,
        public int    $requestsPerMinute,
    )
    {
        $this->maxAttempts = $this->resolveMaxAttempts($maxAttempts);
        $this->throttleMs = max(0, $throttleMs);
        $this->requestsPerMinute = max(0, $requestsPerMinute);
        $this->tries = $this->maxAttempts;
    }

    /**
     * Handle the job execution.
     * This job will never fail, instead it logs errors and completes.
     *
     */
    public function handle(HotelSyncService $service, SystemLogger $logger): void
    {
        $jobId = $this->job?->getJobId() ?? 'unknown';
        try {
            $provider = Provider::find($this->providerId);
            if (!$provider) {
                return;
            }
            $propertyKey = trim($this->providerPropertyId);
            if ('' === $propertyKey) {
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
                $this->maxAttempts,
                $this->throttleMs,
                $this->requestsPerMinute,
                $logger
            );
        } catch (\Throwable $e) {
            return;
        }
    }

    private function syncPropertyWithRetry(
        HotelSyncService         $service,
        Provider                 $provider,
        ProviderAdapterInterface $adapter,
        string                   $propertyKey,
        CarbonImmutable          $from,
        CarbonImmutable          $to,
        int                      $maxAttempts,
        int                      $throttleMs,
        int                      $requestsPerMinute,
        SystemLogger             $logger,
    ): void
    {
        $attempt = 0;
        $lastRequestAt = null;
        $minimumIntervalMs = $this->calculateMinimumIntervalMs($requestsPerMinute);
        $limiterKey = $this->resolveRateLimiterKey($propertyKey);
        while ($attempt < $maxAttempts) {
            $nextAttempt = $attempt + 1;
            if (
                $requestsPerMinute > 0
                && $this->shouldDelayForRateLimit(
                    $limiterKey,
                    $requestsPerMinute,
                    $minimumIntervalMs,
                    $provider,
                    $propertyKey,
                    $from,
                    $to,
                    $maxAttempts,
                    $throttleMs,
                    $nextAttempt,
                    $logger
                )
            ) {
                return;
            }

            $attempt = $nextAttempt;

            $this->enforceThrottle($lastRequestAt, $throttleMs);

            if ($requestsPerMinute > 0) {
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
            } catch (\Throwable $exception) {
                return;
            }
        }
    }

    private function enforceThrottle(?float &$lastRequestAt, int $throttleMs): void
    {
        if ($throttleMs <= 0) {
            $lastRequestAt = microtime(true);

            return;
        }

        if (null !== $lastRequestAt) {
            $elapsedMs = (microtime(true) - $lastRequestAt) * 1000;
            $remaining = (int)max(0, ($throttleMs - $elapsedMs) * 1000);
            if ($remaining > 0) {
                usleep($remaining);
            }
        }

        $lastRequestAt = microtime(true);
    }

    private function shouldDelayForRateLimit(
        string          $limiterKey,
        int             $requestsPerMinute,
        int             $minimumIntervalMs,
        Provider        $provider,
        string          $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int             $maxAttempts,
        int             $throttleMs,
        int             $attempt,
        SystemLogger    $logger,
    ): bool
    {
        if (!RateLimiter::tooManyAttempts($limiterKey, $requestsPerMinute)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn($limiterKey);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds, $minimumIntervalMs);
        $this->release($delaySeconds);

        return true;
    }

    private function buildAttemptContext(
        Provider        $provider,
        string          $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int             $maxAttempts,
        int             $throttleMs,
        int             $requestsPerMinute,
        int             $attempt,
    ): array
    {
        return array_merge(
            $this->buildBaseContext(
                $provider,
                $propertyKey,
                $from,
                $to,
                $maxAttempts,
                $throttleMs,
                $requestsPerMinute
            ),
            [
                'attempt' => $attempt,
                'remaining_attempts' => max(0, $maxAttempts - $attempt),
            ]
        );
    }

    private function buildBaseContext(
        Provider        $provider,
        string          $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int             $maxAttempts,
        int             $throttleMs,
        int             $requestsPerMinute,
    ): array
    {
        return [
            'provider_id' => $provider->id,
            'provider_property_id' => $propertyKey,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'max_attempts' => $maxAttempts,
            'throttle_ms' => $throttleMs,
            'requests_per_minute' => $requestsPerMinute,
        ];
    }

    private function buildJobPayloadContext(): array
    {
        return [
            'provider_id' => $this->providerId,
            'provider_property_id' => $this->providerPropertyId,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'max_attempts' => $this->maxAttempts,
            'throttle_ms' => $this->throttleMs,
            'requests_per_minute' => $this->requestsPerMinute,
        ];
    }

    private function extractHttpContext(\Throwable $exception): array
    {
        if (!$exception instanceof RequestException) {
            return [];
        }

        return HttpExceptionContext::extract($exception);
    }

    /**
     * @return array{0: string|null, 1: bool}
     */
    private function limitString(?string $value): array
    {
        if (null === $value) {
            return [null, false];
        }

        if (strlen($value) <= self::LOG_CONTENT_LIMIT) {
            return [$value, false];
        }

        return [substr($value, 0, self::LOG_CONTENT_LIMIT), true];
    }

    private function resolveRateLimiterKey(string $propertyKey): string
    {
        $segments = [
            self::RATE_LIMITER_PREFIX,
            'provider-' . $this->providerId,
            'property-' . $this->normalizeRateLimiterSegment($propertyKey),
        ];

        return implode(':', $segments);
    }

    private function normalizeRateLimiterSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-]/', '-', $value);

        if (null === $normalized || '' === $normalized) {
            return substr(hash('sha256', $value), 0, 16);
        }

        return $normalized;
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

        if (null === $availableInSeconds) {
            return $baseDelaySeconds;
        }

        $availableInSeconds = (int)max(0, $availableInSeconds);

        if (0 === $availableInSeconds) {
            return $baseDelaySeconds;
        }

        return max($baseDelaySeconds, $availableInSeconds);
    }

    private function resolveMaxAttempts(int $maxAttempts): int
    {
        $value = max(1, $maxAttempts);

        return min($value, self::MAX_TRIES);
    }
}
