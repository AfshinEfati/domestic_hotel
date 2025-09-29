<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Support\Logging\SystemLogger;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\RequestException;
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
    private const LOG_CONTENT_LIMIT = 2048;

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
            $nextAttempt = $attempt + 1;

            if (
                $requestsPerMinute > 0 &&
                $this->shouldDelayForRateLimit(
                    self::RATE_LIMITER_KEY,
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
                    [
                        'message' => $exception->getMessage(),
                    ],
                    $this->extractHttpContext($exception),
                    [
                        'exception' => $exception,
                    ]
                );

                $logger->warning(__METHOD__, 'Failed to sync GRS availability for property', $context);

                if ($attempt >= $maxAttempts) {
                    $logger->error(
                        __METHOD__,
                        'Abandoning GRS availability sync after max attempts',
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
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $attempt,
        SystemLogger $logger
    ): bool {
        if (!RateLimiter::tooManyAttempts($limiterKey, $requestsPerMinute)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn($limiterKey);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds, $minimumIntervalMs);

        $logger->info(
            __METHOD__,
            'Delaying GRS availability sync due to provider rate limit',
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

    public function failed(?Throwable $exception): void
    {
        /** @var SystemLogger $logger */
        $logger = app(SystemLogger::class);

        $context = array_merge(
            $this->buildJobPayloadContext(),
            [
                'exception' => $exception,
                'exception_message' => $exception?->getMessage(),
            ],
            $exception ? $this->extractHttpContext($exception) : []
        );

        $logger->error(__METHOD__, 'SyncGrsAvailabilityForPropertyJob failed permanently', $context);
    }

    private function buildAttemptContext(
        Provider $provider,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        int $requestsPerMinute,
        int $attempt
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
        int $requestsPerMinute
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
            'max_attempts' => max(1, $this->maxAttempts),
            'throttle_ms' => max(0, $this->throttleMs),
            'requests_per_minute' => max(0, $this->requestsPerMinute),
        ];
    }

    private function extractHttpContext(Throwable $exception): array
    {
        if (!$exception instanceof RequestException) {
            return [];
        }

        $context = [];

        if ($response = $exception->response) {
            [$body, $truncated] = $this->limitString($response->body());
            $context['response_status'] = $response->status();
            $context['response_headers'] = $response->headers();
            if ($body !== null) {
                $context['response_body'] = $body;
                if ($truncated) {
                    $context['response_body_truncated'] = true;
                }
            }
        }

        if ($request = $exception->request()) {
            $context['request_method'] = $request->getMethod();
            $context['request_url'] = (string)$request->getUri();

            $headers = $request->getHeaders();
            if (!empty($headers)) {
                $context['request_headers'] = $headers;
            }

            try {
                $bodyString = (string)$request->getBody();
            } catch (Throwable $error) {
                $bodyString = null;
            }

            if ($bodyString !== null && $bodyString !== '') {
                [$body, $truncated] = $this->limitString($bodyString);
                $context['request_body'] = $body;
                if ($truncated) {
                    $context['request_body_truncated'] = true;
                }
            }
        }

        return $context;
    }

    /**
     * @return array{0: string|null, 1: bool}
     */
    private function limitString(?string $value): array
    {
        if ($value === null) {
            return [null, false];
        }

        if (strlen($value) <= self::LOG_CONTENT_LIMIT) {
            return [$value, false];
        }

        return [substr($value, 0, self::LOG_CONTENT_LIMIT), true];
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
