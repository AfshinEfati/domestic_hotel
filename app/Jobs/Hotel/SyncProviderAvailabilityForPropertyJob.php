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
        return $this->providerId.'_'.$this->providerPropertyId.'_'.$this->fromDate.'_'.$this->toDate;
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
            'provider:'.$this->providerId,
            'property:'.$this->providerPropertyId,
        ];
    }

    public function __construct(
        public int $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int $maxAttempts,
        public int $throttleMs,
        public int $requestsPerMinute,
    ) {
        $this->maxAttempts = max(1, $maxAttempts);
        $this->throttleMs = max(0, $throttleMs);
        $this->requestsPerMinute = max(0, $requestsPerMinute);
        $this->tries = $this->maxAttempts;
    }

    /**
     * Handle the job execution.
     * This job will never fail, instead it logs errors and completes.
     *
     * @throws BindingResolutionException
     */
    public function handle(HotelSyncService $service, SystemLogger $logger): void
    {
        $logger->info(__METHOD__, 'START - Job execution started', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'provider_id' => $this->providerId,
            'property_id' => $this->providerPropertyId,
            'attempts' => $this->attempts(),
            'max_attempts' => $this->maxAttempts,
        ]);

        $jobId = $this->job?->getJobId() ?? 'unknown';

        $logger->info(__METHOD__, 'Starting job execution', [
            'job_id' => $jobId,
            'provider_id' => $this->providerId,
            'property_id' => $this->providerPropertyId,
        ]);

        try {
            $provider = Provider::find($this->providerId);
            if (!$provider) {
                // Log error but don't fail the job
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

                $logger->warning(__METHOD__, 'SyncProviderAvailabilityForPropertyJob completed with warning: provider not found', [
                    'provider_id' => $this->providerId,
                    'provider_property_id' => $this->providerPropertyId,
                ]);

                return;
            }

            $propertyKey = trim($this->providerPropertyId);
            if ('' === $propertyKey) {
                $logger->warning(__METHOD__, 'SyncProviderAvailabilityForPropertyJob skipped because provider property id is empty', [
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
                $this->maxAttempts,
                $this->throttleMs,
                $this->requestsPerMinute,
                $logger
            );
        } catch (\Throwable $e) {
            // Log the error in database
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

            $logger->error(__METHOD__, 'Job completed with error', [
                'exception_message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString(),
                'job_id' => $jobId,
                'attempt_number' => $this->attempts(),
                'max_attempts' => $this->maxAttempts,
            ]);

            // Don't throw the error, just return
            return;
        }
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
        SystemLogger $logger,
    ): void {
        $attempt = 0;
        $lastRequestAt = null;
        $minimumIntervalMs = $this->calculateMinimumIntervalMs($requestsPerMinute);
        $limiterKey = $this->resolveRateLimiterKey($propertyKey);

        $logger->info(
            __METHOD__,
            'Starting Provider availability sync',
            [
                'provider' => $provider->id,
                'property' => $propertyKey,
                'max_attempts' => $maxAttempts,
                'throttle_ms' => $throttleMs,
                'requests_per_minute' => $requestsPerMinute,
            ]
        );

        while ($attempt < $maxAttempts) {
            $nextAttempt = $attempt + 1;

            $logger->info(
                __METHOD__,
                'Attempting Provider availability sync',
                ['attempt' => $nextAttempt, 'max_attempts' => $maxAttempts]
            );

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
                $logger->info(
                    __METHOD__,
                    'Job delayed due to rate limiting',
                    ['attempt' => $nextAttempt]
                );

                return;
            }

            $attempt = $nextAttempt;

            $this->enforceThrottle($lastRequestAt, $throttleMs);

            if ($requestsPerMinute > 0) {
                RateLimiter::hit($limiterKey, self::RATE_LIMITER_DECAY_SECONDS);
            }

            try {
                $logger->info(
                    __METHOD__,
                    'Making request to Provider API',
                    [
                        'attempt' => $attempt,
                        'job_id' => $this->job->getJobId(),
                        'provider' => $provider->name,
                        'property' => $propertyKey,
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                    ]
                );

                $service->crawlAvailabilityForProperty(
                    $provider,
                    $adapter,
                    $propertyKey,
                    $from,
                    $to
                );

                $logger->info(
                    __METHOD__,
                    'Successfully synced Provider availability',
                    ['attempt' => $attempt, 'job_id' => $this->job->getJobId()]
                );

                return;
            } catch (\Throwable $exception) {
                $errorDetails = [
                    'job_id' => $this->job?->getJobId() ?? 'unknown',
                    'attempt' => $attempt,
                    'exception' => [
                        'class' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'code' => $exception->getCode(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ],
                ];

                if ($exception instanceof RequestException && $exception->response) {
                    $errorDetails['http'] = [
                        'response_status' => $exception->response->status(),
                        'response_body' => $exception->response->body(),
                        'request_url' => $exception->response->effectiveUri(),
                        'request_method' => $exception->response->effectiveMethod(),
                    ];
                }

                $context = array_merge(
                    $this->buildAttemptContext(
                        $provider,
                        $propertyKey,
                        $from,
                        $to,
                        $maxAttempts,
                        $throttleMs,
                        $requestsPerMinute,
                        $attempt
                    ),
                    $errorDetails
                );

                $logger->warning(
                    __METHOD__,
                    sprintf(
                        'Failed to sync Provider availability for property. Error: %s at %s:%d',
                        $exception->getMessage(),
                        $exception->getFile(),
                        $exception->getLine()
                    ),
                    $context
                );

                if ($attempt >= $maxAttempts) {
                    // Log to database but don't throw
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
                                $maxAttempts,
                                $throttleMs,
                                $requestsPerMinute,
                                $attempt
                            ),
                            ['step' => 'sync_property']
                        ),
                    ]);

                    $logger->error(
                        __METHOD__,
                        'Completed sync with errors after max attempts',
                        array_merge(
                            $this->buildAttemptContext(
                                $provider,
                                $propertyKey,
                                $from,
                                $to,
                                $maxAttempts,
                                $throttleMs,
                                $requestsPerMinute,
                                $attempt
                            ),
                            [
                                'exception' => $exception,
                            ]
                        )
                    );

                    return;
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

        if (null !== $lastRequestAt) {
            $elapsedMs = (microtime(true) - $lastRequestAt) * 1000;
            $remaining = (int) max(0, ($throttleMs - $elapsedMs) * 1000);
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
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $attempt,
        SystemLogger $logger,
    ): bool {
        if (!RateLimiter::tooManyAttempts($limiterKey, $requestsPerMinute)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn($limiterKey);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds, $minimumIntervalMs);

        $logger->info(
            __METHOD__,
            'Delaying Provider availability sync due to provider rate limit',
            array_merge(
                $this->buildAttemptContext(
                    $provider,
                    $propertyKey,
                    $from,
                    $to,
                    $maxAttempts,
                    $throttleMs,
                    $requestsPerMinute,
                    $attempt
                ),
                [
                    'rate_limiter_key' => $limiterKey,
                    'rate_limit_delay_seconds' => $delaySeconds,
                    'available_in_seconds' => $availableInSeconds,
                ]
            )
        );

        $this->release($delaySeconds);

        return true;
    }

    private function buildAttemptContext(
        Provider $provider,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $requestsPerMinute,
        int $attempt,
    ): array {
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
        Provider $provider,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $requestsPerMinute,
    ): array {
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
            'provider-'.$this->providerId,
            'property-'.$this->normalizeRateLimiterSegment($propertyKey),
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

        return (int) ceil(60000 / $requestsPerMinute);
    }

    private function determineRateLimitDelaySeconds(?int $availableInSeconds, int $minimumIntervalMs): int
    {
        $baseDelaySeconds = max(1, (int) ceil(max(0, $minimumIntervalMs) / 1000));

        if (null === $availableInSeconds) {
            return $baseDelaySeconds;
        }

        $availableInSeconds = (int) max(0, $availableInSeconds);

        if (0 === $availableInSeconds) {
            return $baseDelaySeconds;
        }

        return max($baseDelaySeconds, $availableInSeconds);
    }
}
