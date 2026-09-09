<?php

namespace App\Jobs\Hotel;

use App\Models\Provider;
use App\Services\Contracts\AccommodationProviderMapServiceInterface;
use App\Services\Contracts\ProviderServiceInterface;
use App\Support\Logging\SystemLogger;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAllProvidersAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $days = 30,
        public ?int $chunkSize = null,
        public ?int $throttleMs = null,
        public ?int $maxAttempts = null,
        public ?int $requestsPerMinute = null,
    ) {
    }

    public function handle(
        SystemLogger $logger,
        ProviderServiceInterface $providerService,
        AccommodationProviderMapServiceInterface $mapService
    ): void {
        $providers = collect($providerService->getActiveProviders());

        if ($providers->isEmpty()) {
            $logger->info(__METHOD__, 'SyncAllProvidersAvailabilityJob skipped because no active providers found');
            return;
        }

        foreach ($providers as $provider) {
            $this->syncProvider($provider, $mapService, $logger);
        }
    }

    private function syncProvider(
        Provider $provider,
        AccommodationProviderMapServiceInterface $mapService,
        SystemLogger $logger
    ): void {
        $config =$provider->config ?? [];

        $days = $this->resolvePositiveInt($this->days, (int)($config['days'] ?? 30), 1);
        $chunkSize = $this->resolvePositiveInt($this->chunkSize, (int)($config['chunk_size'] ?? 20), 1);
        $throttleMs = max(0, (int)($this->throttleMs ?? $config['throttle_ms'] ?? 500));
        $maxAttempts = $this->resolvePositiveInt($this->maxAttempts, (int)($config['max_attempts'] ?? 3), 1);
        $requestsPerMinute = $this->resolvePositiveInt(
            $this->requestsPerMinute,
            (int)($config['requests_per_minute'] ?? 20),
            1
        );

        $from = CarbonImmutable::today();
        $to = $from->addDays($days);

        $totalProperties = $mapService->countMappedPropertiesByProvider($provider->id);
        if ($totalProperties === 0) {
            return;
        }
        $queued = 0;
        $skipped = 0;

        $mapService->chunkMappedPropertiesByProvider(
            $provider->id,
            $chunkSize,
            function ($rows) use (
                $provider,
                $from,
                $to,
                $maxAttempts,
                $throttleMs,
                $requestsPerMinute,
                &$queued,
                &$skipped,
                $logger
            ) {
                foreach ($rows as $row) {
                    $propertyKey = trim((string)($row->provider_property_id ?? ''));
                    if ($propertyKey === '') {
                        $skipped++;
                        continue;
                    }
                    SyncProviderAvailabilityForPropertyJob::dispatch(
                        $provider->id,
                        $propertyKey,
                        $from->toDateString(),
                        $to->toDateString(),
                        $maxAttempts,
                        $throttleMs,
                        $requestsPerMinute
                    );
                    $queued++;
                }
            }
        );
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
}
