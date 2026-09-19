<?php

namespace App\Jobs\Hotel;

use App\Models\Provider;
use App\Services\Contracts\SystemSettingServiceInterface;
use App\Support\Logging\SystemLogger;
use App\Support\System\SystemSettingKey;
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

    private const MAX_GRS_REQUESTS_PER_WINDOW = 10;

    public function __construct(
        public ?int $days = null,
        public ?int $chunkSize = null,
        public ?int $throttleMs = null,
        public ?int $maxAttempts = null,
    ) {
    }

    public function handle(
        SystemLogger $logger,
        SystemSettingServiceInterface $systemSettingService
    ): void {
        $provider = Provider::query()->where('code', 'grs')->first();

        if (!$provider) {
            $logger->warning(__METHOD__, 'GRS availability sync skipped because provider was not found', [
                'code' => 'grs',
            ]);

            return;
        }

        if (!$provider->is_active || !$provider->is_online) {
            $logger->warning(__METHOD__, 'GRS availability sync skipped because provider is not active and online', [
                'provider_id' => $provider->id,
                'is_active' => $provider->is_active,
                'is_online' => $provider->is_online,
            ]);

            return;
        }

        $days = $this->resolvePositiveInt(
            $this->days,
            (int) $systemSettingService->getValue(SystemSettingKey::GRS_AVAILABILITY_DAYS),
            1
        );
        $chunkSize = $this->resolvePositiveInt(
            $this->chunkSize,
            (int) $systemSettingService->getValue(SystemSettingKey::GRS_AVAILABILITY_CHUNK_SIZE),
            1
        );
        $throttleMs = max(
            0,
            (int) ($this->throttleMs
                ?? $systemSettingService->getValue(SystemSettingKey::GRS_AVAILABILITY_THROTTLE_MS))
        );
        $maxAttempts = $this->resolvePositiveInt(
            $this->maxAttempts,
            (int) $systemSettingService->getValue(SystemSettingKey::GRS_AVAILABILITY_MAX_ATTEMPTS),
            1
        );

        $maxRequests = $this->resolveMaxRequests(
            data_get($provider->config, 'availability_rate_limit.max_requests')
        );
        $windowMinutes = $this->resolveWindowMinutes(
            data_get($provider->config, 'availability_rate_limit.window_minutes')
        );

        $from = CarbonImmutable::today();
        $to = $from->addDays($days);

        $baseQuery = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->whereNotNull('provider_property_id');

        $totalProperties = (clone $baseQuery)->count();

        if ($totalProperties === 0) {
            $logger->info(__METHOD__, 'GRS availability sync skipped because no mapped properties were found', [
                'provider_id' => $provider->id,
            ]);

            return;
        }

        $logger->info(__METHOD__, 'GRS availability sync dispatch started', [
            'provider_id' => $provider->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_properties' => $totalProperties,
            'chunk_size' => $chunkSize,
            'max_attempts' => $maxAttempts,
            'rate_limit_max_requests' => $maxRequests,
            'rate_limit_window_minutes' => $windowMinutes,
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
                $maxRequests,
                $windowMinutes,
                &$queued,
                &$skipped,
                $logger
            ) {
                foreach ($rows as $row) {
                    $propertyKey = trim((string) ($row->provider_property_id ?? ''));

                    if ($propertyKey === '') {
                        $logger->warning(__METHOD__, 'GRS availability property skipped because provider property id is empty', [
                            'provider_id' => $provider->id,
                            'map_id' => $row->id,
                        ]);
                        $skipped++;

                        continue;
                    }

                    $windowIndex = intdiv($queued, $maxRequests);
                    $delaySeconds = $windowIndex * $windowMinutes * 60;

                    $pending = SyncGrsAvailabilityForPropertyJob::dispatch(
                        $provider->id,
                        $propertyKey,
                        $from->toDateString(),
                        $to->toDateString(),
                        $maxAttempts,
                        $throttleMs,
                        $maxRequests,
                        $windowMinutes
                    );

                    if ($delaySeconds > 0) {
                        $pending->delay(now()->addSeconds($delaySeconds));
                    }

                    $queued++;
                }
            }, 'id');

        $logger->info(__METHOD__, 'GRS availability sync dispatch finished', [
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

        return max($min, $default);
    }

    private function resolveMaxRequests(mixed $value): int
    {
        $resolved = is_numeric($value) ? (int) $value : self::MAX_GRS_REQUESTS_PER_WINDOW;

        return min(self::MAX_GRS_REQUESTS_PER_WINDOW, max(1, $resolved));
    }

    private function resolveWindowMinutes(mixed $value): int
    {
        $resolved = is_numeric($value) ? (int) $value : 1;

        return max(1, $resolved);
    }
}
