<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** GRS-specific price/inventory worker; IDs and persistence go through services/repositories. */
class RefreshGrsPropertyPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100; // Quota releases count as attempts; HTTP errors are not retried here.
    public int $maxExceptions = 1;
    public int $timeout = 55; // Below Horizon's 60s worker timeout and Redis retry_after=90.
    public int $uniqueFor = 14400;

    public function __construct(
        public int $scheduleId,
        public int $gdsId,
        public int $providerId,
        public int $days,
    ) {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        return 'grs-property-price:'.$this->gdsId;
    }

    public function handle(HotelSyncService $service, GrsPriceRefreshScheduleService $schedules): void
    {
        try {
            $provider = $schedules->providerById($this->providerId);
            if ($provider === null || $provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
                throw new RuntimeException('GRS provider inactive, offline, missing, or mismatched.');
            }

            // The shared schedule and this job carry our local accommodations.id.
            if ($schedules->active($this->scheduleId, $this->gdsId) === null) {
                Log::warning('GRS price job skipped: shared schedule disabled/remapped', [
                    'schedule_id' => $this->scheduleId, 'gds_id' => $this->gdsId,
                ]);
                return;
            }

            $map = $schedules->mapForAccommodation($this->gdsId, (int) $provider->id);
            $grsId = trim((string) ($map?->provider_property_id ?? ''));
            if ($grsId === '') {
                throw new RuntimeException('GRS provider property ID missing from local accommodation map.');
            }

            $from = CarbonImmutable::today('Asia/Tehran');
            $to = $from->addDays($this->days);
            $started = now()->subSeconds(2); // Account for second-granularity DB timestamps.
            $adapter = new RateLimitedGrsAdapter($provider);
            $adapter->trackAvailability(
                fn (): mixed => $schedules->requestStarted($this->scheduleId, $this->gdsId),
                fn (): mixed => $schedules->http200($this->scheduleId, $this->gdsId)
            );

            // Each actual HTTP call consumes the provider's existing quota.
            // Persist provider-returned dates even when outside the requested window.
            $service->crawlAvailabilityForProperty($provider, $adapter, $grsId, $from, $to);
            if ($adapter->supplementalError !== null) {
                throw $adapter->supplementalError;
            }

            // Missing calendar days, and even an empty successful response, are valid.
            // Only dimensions that were actually returned must have been persisted.
            $count = $schedules->verifiedRowCount(
                (int) $provider->id,
                $this->gdsId,
                $grsId,
                $adapter->lastAvailability,
                $started
            );
            $minutes = $schedules->persisted($this->scheduleId, $this->gdsId);
            Log::info('GRS scheduled availability refresh completed', [
                'schedule_id' => $this->scheduleId,
                'gds_id' => $this->gdsId,
                'grs_id' => $grsId,
                'days' => $this->days,
                'calendar_rows' => $count,
                'next_in_minutes' => $minutes,
            ]);
        } catch (GrsApiQuotaExceeded $e) {
            // Quota is already exhausted; do not advance the SSP due time.
            $this->release(max(1, $e->retryAfterSeconds + 1));
        } catch (RequestException $e) {
            if ($e->response?->status() === 429) {
                Log::error('GRS returned HTTP 429; API cooldown enabled; due time unchanged', [
                    'schedule_id' => $this->scheduleId,
                    'gds_id' => $this->gdsId,
                    'cooldown_seconds' => RateLimitedGrsAdapter::cooldownSeconds(),
                ]);
            }
            $this->recordFailure($e);
        } catch (Throwable $e) {
            $this->recordFailure($e);
        }
    }

    private function recordFailure(Throwable $e): void
    {
        Log::error('GRS scheduled price refresh failed; shared due time unchanged', [
            'schedule_id' => $this->scheduleId,
            'gds_id' => $this->gdsId,
            'error' => $e->getMessage(),
        ]);
        $this->fail($e);
    }
}
