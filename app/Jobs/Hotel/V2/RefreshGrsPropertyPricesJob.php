<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Models\RatePlan;
use App\Models\RatePlanProviderMap;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Models\RoomTypeProviderMap;
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

/** Dedicated GRS-only price/stock worker. It never syncs cities or hotels. */
class RefreshGrsPropertyPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100; // Quota releases count as attempts; HTTP errors do not retry here.
    public int $maxExceptions = 1;
    public int $timeout = 80;
    public int $uniqueFor = 14400;

    public function __construct(
        public int $scheduleId,
        public string $grsId,
        public int $providerId,
        public int $days,
    ) {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        return 'grs-property-price:'.$this->grsId;
    }

    public function handle(HotelSyncService $service, GrsPriceRefreshScheduleService $schedules): void
    {
        try {
            $provider = Provider::query()->findOrFail($this->providerId);
            if ($provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
                throw new RuntimeException('GRS provider inactive, offline, or mismatched.');
            }

            $map = AccommodationProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('provider_property_id', $this->grsId)
                ->firstOrFail();
            if ($schedules->active($this->scheduleId, $this->grsId) === null) {
                Log::warning('GRS price job skipped: shared schedule disabled/remapped', [
                    'schedule_id' => $this->scheduleId, 'grs_id' => $this->grsId,
                ]);
                return;
            }

            $from = CarbonImmutable::today('Asia/Tehran');
            $to = $from->addDays($this->days);
            $started = now()->subSeconds(2); // Include DB timestamps rounded to the second.
            $adapter = new RateLimitedGrsAdapter($provider);
            $adapter->trackAvailability(
                fn (): mixed => $schedules->requestStarted($this->scheduleId, $this->grsId),
                fn (): mixed => $schedules->http200($this->scheduleId, $this->grsId)
            );

            // The existing service performs the required supplemental room/rate
            // mapping request when necessary. Both calls consume GRS API quota.
            $service->crawlAvailabilityForProperty($provider, $adapter, $this->grsId, $from, $to);
            if ($adapter->supplementalError !== null) {
                throw $adapter->supplementalError;
            }

            $count = $this->verifyPersistedAvailability(
                $provider->id, (int) $map->accommodation_id, $adapter, $started, $from, $to
            );
            // SSP's current interval, not a stale value captured during dispatch.
            $minutes = $schedules->persisted($this->scheduleId, $this->grsId);
            Log::info('GRS scheduled availability successfully persisted', [
                'schedule_id' => $this->scheduleId, 'grs_id' => $this->grsId,
                'days' => $this->days, 'calendar_rows' => $count, 'next_in_minutes' => $minutes,
            ]);
        } catch (GrsApiQuotaExceeded $e) {
            // No request was made and the shared due time must not change.
            $this->release(max(1, $e->retryAfterSeconds + 1));
        } catch (RequestException $e) {
            if ($e->response?->status() === 429) {
                // Adapter enables shared provider-configured cooldown. Do not
                // change this hotel's due time or create a failure backoff.
                Log::error('GRS returned HTTP 429; API cooldown enabled; due time unchanged', [
                    'schedule_id' => $this->scheduleId, 'grs_id' => $this->grsId,
                    'cooldown_seconds' => RateLimitedGrsAdapter::cooldownSeconds(),
                ]);
            }
            $this->recordFailure($e);
        } catch (Throwable $e) {
            $this->recordFailure($e);
        }
    }

    private function verifyPersistedAvailability(
        int $providerId,
        int $accommodationId,
        RateLimitedGrsAdapter $adapter,
        \Carbon\CarbonInterface $started,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): int {
        $response = $adapter->lastAvailability;
        if ($response === null || $response->isEmpty()) {
            throw new RuntimeException('GRS availability was empty; no price/stock data was saved.');
        }

        $roomProviderIds = [];
        $rateProviderIds = [];
        $rows = [];
        foreach ($response as $row) {
            $roomId = trim((string) ($row['room_type_id'] ?? ''));
            $rateId = trim((string) ($row['rate_plan_id'] ?? ''));
            $day = $row['day'] ?? null;
            if ($roomId === '' || $rateId === '' || !is_string($day) || $day === '') {
                throw new RuntimeException('GRS availability contains rows without room, rate-plan or date.');
            }
            try {
                $normalizedDay = CarbonImmutable::parse($day)->toDateString();
            } catch (Throwable $e) {
                throw new RuntimeException('GRS availability contains an invalid date.', 0, $e);
            }
            if ($normalizedDay < $from->toDateString() || $normalizedDay >= $to->toDateString()) {
                throw new RuntimeException('GRS availability returned a date outside the requested range.');
            }
            $roomProviderIds[$roomId] = true;
            $rateProviderIds[$rateId] = true;
            $rows[] = [$roomId, $rateId, $normalizedDay];
        }

        $roomIds = RoomTypeProviderMap::query()->where('provider_id', $providerId)
            ->whereIn('provider_room_type_id', array_keys($roomProviderIds))
            ->pluck('room_type_id', 'provider_room_type_id')->all();
        $rateIds = RatePlanProviderMap::query()->where('provider_id', $providerId)
            ->whereIn('provider_rate_plan_id', array_keys($rateProviderIds))
            ->pluck('rate_plan_id', 'provider_rate_plan_id')->all();
        if (count($roomIds) !== count($roomProviderIds) || count($rateIds) !== count($rateProviderIds)) {
            throw new RuntimeException('GRS room/rate-plan maps remain incomplete; refresh not marked successful.');
        }

        if (RoomType::query()->where('accommodation_id', $accommodationId)
                ->whereIn('id', array_values($roomIds))->count() !== count($roomIds) ||
            RatePlan::query()->where('accommodation_id', $accommodationId)
                ->whereIn('id', array_values($rateIds))->count() !== count($rateIds)) {
            throw new RuntimeException('GRS room/rate-plan mappings belong to a different accommodation.');
        }

        $expected = [];
        foreach ($rows as [$roomProviderId, $rateProviderId, $day]) {
            $expected[$roomIds[$roomProviderId].'|'.$rateIds[$rateProviderId].'|'.$day] = true;
        }
        $persisted = RoomCalendar::query()->where('provider_id', $providerId)
            ->where('accommodation_id', $accommodationId)
            ->where('provider_property_id', $this->grsId)
            ->where('updated_at', '>=', $started)
            ->where('day', '>=', $from->toDateString())
            ->where('day', '<', $to->toDateString())
            ->get(['room_type_id', 'rate_plan_id', 'day']);
        foreach ($persisted as $calendar) {
            $key = $calendar->room_type_id.'|'.$calendar->rate_plan_id.'|'.substr((string) $calendar->day, 0, 10);
            unset($expected[$key]);
        }
        if ($expected !== []) {
            throw new RuntimeException('GRS price/stock rows not fully saved: '.count($expected).' calendar dimensions missing.');
        }
        return count($rows);
    }

    private function recordFailure(Throwable $e): void
    {
        Log::error('GRS scheduled price refresh failed; shared due time unchanged', [
            'schedule_id' => $this->scheduleId, 'grs_id' => $this->grsId,
            'error' => $e->getMessage(),
        ]);
        $this->fail($e);
    }
}
