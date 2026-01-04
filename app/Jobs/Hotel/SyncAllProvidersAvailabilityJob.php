<?php

namespace App\Jobs\Hotel;

use App\Models\Provider;
use App\Support\Logging\SystemLogger;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncAllProvidersAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $days = null,
        public ?int $chunkSize = null,
        public ?int $throttleMs = null,
        public ?int $maxAttempts = null,
        public ?int $requestsPerMinute = null,
    ) {
    }

    public function handle(SystemLogger $logger): void
    {
        $providers = Provider::where('is_active', true)->get();

        if ($providers->isEmpty()) {
            $logger->info(__METHOD__, 'SyncAllProvidersAvailabilityJob skipped because no active providers found');

            return;
        }

        foreach ($providers as $provider) {
            $this->syncProvider($provider, $logger);
        }
    }

    private function syncProvider(Provider $provider, SystemLogger $logger): void
    {
        $config = config("hotel.providers.{$provider->code}.availability", []);

        $days = $this->resolvePositiveInt($this->days, (int)($config['days'] ?? 60), 1);
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

        $baseQuery = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->whereNotNull('provider_property_id');

        $totalProperties = (clone $baseQuery)->count();
        if ($totalProperties === 0) {
            $logger->info(__METHOD__, 'SyncProviderAvailabilityJob skipped because no mapped properties found', [
                'provider_id' => $provider->id,
                'provider_code' => $provider->code,
            ]);

            return;
        }

        $logger->info(__METHOD__, 'SyncProviderAvailabilityJob started', [
            'provider_id' => $provider->id,
            'provider_code' => $provider->code,
            'date_range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_properties' => $totalProperties,
            'chunk_size' => $chunkSize,
            'throttle_ms' => $throttleMs,
            'max_attempts' => $maxAttempts,
            'requests_per_minute' => $requestsPerMinute,
            'dispatch_mode' => 'per_property',
        ]);

        $queued = 0;
        $skipped = 0;

        $baseQuery
            ->select('id', 'provider_property_id')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
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
                        $logger->warning(__METHOD__, 'Skipping provider availability sync because provider property id is empty', [
                            'provider_id' => $provider->id,
                            'provider_code' => $provider->code,
                            'map_id' => $row->id,
                        ]);
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
            }, 'id');

        $logger->info(__METHOD__, 'SyncProviderAvailabilityJob finished', [
            'provider_id' => $provider->id,
            'provider_code' => $provider->code,
            'queued_properties' => $queued,
            'skipped_properties' => $skipped,
        ]);
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
