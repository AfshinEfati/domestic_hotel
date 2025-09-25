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

class SyncGrsAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $days = null,
        public ?int $chunkSize = null,
        public ?int $throttleMs = null,
        public ?int $maxAttempts = null,
    ) {
    }

    public function handle(SystemLogger $logger): void
    {
        $provider = Provider::where('code', 'grs')->first();
        if (!$provider) {
            $logger->warning(__METHOD__, 'SyncGrsAvailabilityJob skipped because provider not found', [
                'code' => 'grs',
            ]);

            return;
        }

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
            $logger->info(__METHOD__, 'SyncGrsAvailabilityJob skipped because no mapped properties found', [
                'provider_id' => $provider->id,
            ]);

            return;
        }

        $logger->info(__METHOD__, 'SyncGrsAvailabilityJob started', [
            'provider_id' => $provider->id,
            'date_range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_properties' => $totalProperties,
            'chunk_size' => $chunkSize,
            'throttle_ms' => $throttleMs,
            'max_attempts' => $maxAttempts,
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
                &$queued,
                &$skipped
            ) {
                foreach ($rows as $row) {
                    $propertyKey = trim((string)($row->provider_property_id ?? ''));
                    if ($propertyKey === '') {
                        $logger->warning(__METHOD__, 'Skipping GRS availability sync because provider property id is empty', [
                            'provider_id' => $provider->id,
                            'map_id' => $row->id,
                        ]);
                        $skipped++;

                        continue;
                    }

                    SyncGrsAvailabilityForPropertyJob::dispatch(
                        $provider->id,
                        $propertyKey,
                        $from->toDateString(),
                        $to->toDateString(),
                        $maxAttempts,
                        $throttleMs
                    );

                    $queued++;
                }
            }, 'id');

        $logger->info(__METHOD__, 'SyncGrsAvailabilityJob finished', [
            'provider_id' => $provider->id,
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
