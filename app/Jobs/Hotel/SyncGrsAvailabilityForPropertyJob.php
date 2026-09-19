<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Models\JobErrorLog;
use App\Models\Provider;
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

class SyncGrsAvailabilityForPropertyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RATE_LIMITER_KEY = 'grs-availability';
    private const MAX_GRS_REQUESTS_PER_WINDOW = 10;
    private const MAX_TRIES = 2;

    public int $tries;
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
        $this->tries = $this->maxAttempts;
    }

    public function uniqueId(): string
    {
        return $this->providerPropertyId.'_'.$this->fromDate.'_'.$this->toDate;
    }

    /**
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'sync',
            'grs',
            'provider:'.$this->providerId,
            'property:'.$this->providerPropertyId,
        ];
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(HotelSyncService $service, SystemLogger $logger): void
    {
        $jobId = $this->job?->getJobId() ?? 'unknown';

        try {
            $provider = Provider::find($this->providerId);

            if (!$provider) {
                JobErrorLog::createFromException(
                    new \RuntimeException('Provider not found'),
                    [
                        'job_class' => static::class,
                        'job_id' => $jobId,
                        'queue' => $this->job?->getQueue() ?? 'default',
                        'attempts' => $this->attempts(),
                        'max_attempts' => $this->maxAttempts,
                        'job_payload' => $this->buildJobPayloadContext(),
                        'additional_context' => [
                            'provider_id' => $this->providerId,
                            'provider_property_id' => $this->providerPropertyId,
                        ],
                    ]
                );

                $logger->warning(__METHOD__, 'GRS availability property sync skipped because provider was not found', [
                    'provider_id' => $this->providerId,
                    'provider_property_id' => $this->providerPropertyId,
                ]);

                return;
            }

            if ($provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
                $logger->warning(__METHOD__, 'GRS availability property sync skipped because provider is not active GRS', [
                    'provider_id' => $provider->id,
                    'provider_code' => $provider->code,
                    'is_active' => $provider->is_active,
                    'is_online' => $provider->is_online,
                ]);

                return;
            }

            $propertyKey = trim($this->providerPropertyId);

            if ($propertyKey === '') {
                $logger->warning(__METHOD__, 'GRS availability property sync skipped because provider property id is empty', [
                    'provider_id' => $this->providerId,
                ]);

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
                $logger
            );
        } catch (\Throwable $e) {
            JobErrorLog::createFromException($e, [
                'job_class' => static::class,
                'job_id' => $jobId,
                'queue' => $this->job?->getQueue() ?? 'default',
                'attempts' => $this->attempts(),
                'max_attempts' => $this->maxAttempts,
                'job_payload' => $this->buildJobPayloadContext(),
                'additional_context' => [
                    'step' => 'handle',
                    'provider_id' => $this->providerId,
                    'provider_property_id' => $this->providerPropertyId,
                ],
            ]);

            $logger->error(__METHOD__, 'GRS availability property job completed with error', [
                'exception_message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'job_id' => $jobId,
                'attempt_number' => $this->attempts(),
                'max_attempts' => $this->maxAttempts,
            ]);
        }
    }

    private function syncPropertyWithRetry(
        HotelSyncService $service,
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        SystemLogger $logger,
    ): void {
        $attempt = 0;
        $lastRequestAt = null;

        while ($attempt < $this->maxAttempts) {
            $nextAttempt = $attempt + 1;

            if ($this->shouldDelayForRateLimit(
                $provider,
                $propertyKey,
                $from,
                $to,
                $nextAttempt,
                $logger
            )) {
                return;
            }

            $attempt = $nextAttempt;
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
            } catch (\Throwable $exception) {
                $context = array_merge(
                    $this->buildAttemptContext(
                        $provider,
                        $propertyKey,
                        $from,
                        $to,
                        $attempt
                    ),
                    $this->buildExceptionContext($exception)
                );

                $logger->warning(
                    __METHOD__,
                    'Failed to sync GRS availability for property',
                    $context
                );

                if ($attempt >= $this->maxAttempts) {
                    JobErrorLog::createFromException($exception, [
                        'job_class' => static::class,
                        'job_id' => $this->job?->getJobId() ?? 'unknown',
                        'queue' => $this->job?->getQueue() ?? 'default',
                        'attempts' => $this->attempts(),
                        'max_attempts' => $this->maxAttempts,
                        'job_payload' => $this->buildJobPayloadContext(),
                        'additional_context' => array_merge(
                            $this->buildAttemptContext(
                                $provider,
                                $propertyKey,
                                $from,
                                $to,
                                $attempt
                            ),
                            ['step' => 'sync_property']
                        ),
                    ]);

                    $logger->error(
                        __METHOD__,
                        'GRS availability property sync exhausted configured attempts',
                        $context
                    );

                    return;
                }
            }
        }
    }

    private function shouldDelayForRateLimit(
        Provider $provider,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $attempt,
        SystemLogger $logger,
    ): bool {
        if (!RateLimiter::tooManyAttempts(self::RATE_LIMITER_KEY, $this->maxRequests)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn(self::RATE_LIMITER_KEY);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds);

        $logger->info(
            __METHOD__,
            'GRS availability job delayed by provider rate limit',
            array_merge(
                $this->buildAttemptContext(
                    $provider,
                    $propertyKey,
                    $from,
                    $to,
                    $attempt
                ),
                [
                    'rate_limiter_key' => self::RATE_LIMITER_KEY,
                    'rate_limit_delay_seconds' => $delaySeconds,
                    'available_in_seconds' => $availableInSeconds,
                ]
            )
        );

        $this->release($delaySeconds);

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

    private function buildAttemptContext(
        Provider $provider,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $attempt,
    ): array {
        return [
            'provider_id' => $provider->id,
            'provider_property_id' => $propertyKey,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'max_attempts' => $this->maxAttempts,
            'throttle_ms' => $this->throttleMs,
            'rate_limit_max_requests' => $this->maxRequests,
            'rate_limit_window_minutes' => $this->windowMinutes,
            'attempt' => $attempt,
            'remaining_attempts' => max(0, $this->maxAttempts - $attempt),
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
            'rate_limit_max_requests' => $this->maxRequests,
            'rate_limit_window_minutes' => $this->windowMinutes,
        ];
    }

    private function buildExceptionContext(\Throwable $exception): array
    {
        $context = [
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
        ];

        if ($exception instanceof RequestException && $exception->response) {
            $context['http'] = [
                'response_status' => $exception->response->status(),
                'response_body' => mb_substr($exception->response->body(), 0, 2048),
            ];
        }

        return $context;
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
