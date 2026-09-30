<?php

namespace App\Jobs\Hotel;

use App\Models\Provider;
use App\Services\Contracts\SystemSettingServiceInterface;
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
        SystemSettingServiceInterface $systemSettingService
    ): void {
        $provider = Provider::query()->where('code', 'grs')->first();

        if (!$provider) {
            return;
        }

        if (!$provider->is_active || !$provider->is_online) {
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
            return;
        }

        $queued = 0;

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
                &$queued
            ) {
                foreach ($rows as $row) {
                    $propertyKey = trim((string) ($row->provider_property_id ?? ''));

                    if ($propertyKey === '') {
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
