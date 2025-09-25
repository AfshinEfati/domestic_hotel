<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGrsAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $days = null,
        public ?int $chunkSize = null,
        public ?int $throttleMs = null,
        public ?int $maxAttempts = null,
    ) {}

    public function handle(HotelSyncService $service): void
    {
        $provider = Provider::where('code', 'grs')->first();
        if (!$provider) {
            Log::warning('SyncGrsAvailabilityJob skipped because provider not found', [
                'code' => 'grs',
            ]);
            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->makeWith(ProviderAdapterInterface::class, [
            'provider' => $provider,
        ]);

        $config = config('hotel.providers.grs.availability', []);

        $days = $this->resolvePositiveInt($this->days, (int)($config['days'] ?? 60), 1);
        $chunkSize = $this->resolvePositiveInt($this->chunkSize, (int)($config['chunk_size'] ?? 20), 1);
        $throttleMs = max(0, (int)($this->throttleMs ?? $config['throttle_ms'] ?? 500));
        $maxAttempts = $this->resolvePositiveInt($this->maxAttempts, (int)($config['max_attempts'] ?? 3), 1);

        $from = CarbonImmutable::today();
        $to = $from->addDays($days);

        $baseQuery = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->whereNotNull('provider_property_id');

        $totalProperties = (clone $baseQuery)->count();
        if ($totalProperties === 0) {
            Log::info('SyncGrsAvailabilityJob skipped because no mapped properties found', [
                'provider_id' => $provider->id,
            ]);
            return;
        }

        Log::info('SyncGrsAvailabilityJob started', [
            'provider_id' => $provider->id,
            'date_range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_properties' => $totalProperties,
            'chunk_size' => $chunkSize,
            'throttle_ms' => $throttleMs,
            'max_attempts' => $maxAttempts,
        ]);

        $lastRequestAt = null;
        $processed = 0;

        $baseQuery
            ->select('id', 'provider_property_id')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
                $service,
                $provider,
                $adapter,
                $from,
                $to,
                $maxAttempts,
                $throttleMs,
                &$lastRequestAt,
                &$processed
            ) {
                foreach ($rows as $row) {
                    $propertyKey = trim((string)($row->provider_property_id ?? ''));
                    if ($propertyKey === '') {
                        Log::warning('Skipping GRS availability sync because provider property id is empty', [
                            'provider_id' => $provider->id,
                            'map_id' => $row->id,
                        ]);
                        continue;
                    }

                    $this->syncPropertyWithRetry(
                        $service,
                        $provider,
                        $adapter,
                        $propertyKey,
                        $from,
                        $to,
                        $maxAttempts,
                        $throttleMs,
                        $lastRequestAt
                    );

                    $processed++;
                }
            }, 'id');

        Log::info('SyncGrsAvailabilityJob finished', [
            'provider_id' => $provider->id,
            'processed_properties' => $processed,
        ]);
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
        ?float &$lastRequestAt
    ): void {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $this->enforceThrottle($lastRequestAt, $throttleMs);

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
                $retryDelayMs = $this->resolveRetryDelay($exception, $attempt, $throttleMs);

                Log::warning('Failed to sync GRS availability for property', [
                    'provider_id' => $provider->id,
                    'provider_property_id' => $propertyKey,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                    'retry_delay_ms' => $retryDelayMs,
                ]);

                if ($attempt >= $maxAttempts) {
                    Log::error('Abandoning GRS availability sync after max attempts', [
                        'provider_id' => $provider->id,
                        'provider_property_id' => $propertyKey,
                        'attempts' => $attempt,
                        'exception' => $exception,
                    ]);

                    return;
                }

                if ($retryDelayMs > 0) {
                    usleep($retryDelayMs * 1000);
                    $lastRequestAt = microtime(true);
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

    private function resolvePositiveInt(?int $value, int $default, int $min): int
    {
        if (is_int($value) && $value >= $min) {
            return $value;
        }

        if ($default < $min) {
            return $min;
        }

        return $default;
    }

    private function resolveRetryDelay(Throwable $exception, int $attempt, int $throttleMs): int
    {
        $defaultDelay = (int)min(
            60000,
            pow(2, max(0, $attempt - 1)) * max($throttleMs, 500)
        );

        if (!$exception instanceof RequestException) {
            return $defaultDelay;
        }

        $response = $exception->response;
        if (!$response || $response->status() !== 429) {
            return $defaultDelay;
        }

        $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After'));
        if ($retryAfter !== null) {
            return max($retryAfter, $defaultDelay);
        }

        return (int)max($defaultDelay, 2000);
    }

    private function parseRetryAfterHeader(?string $header): ?int
    {
        if ($header === null) {
            return null;
        }

        $header = trim($header);
        if ($header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return (int)max(0, ((float)$header) * 1000);
        }

        try {
            $retryTime = CarbonImmutable::parse($header);
        } catch (Throwable) {
            return null;
        }

        $now = CarbonImmutable::now();
        if ($retryTime->lessThanOrEqualTo($now)) {
            return 0;
        }

        return (int)$now->diffInRealMilliseconds($retryTime);
    }
}
