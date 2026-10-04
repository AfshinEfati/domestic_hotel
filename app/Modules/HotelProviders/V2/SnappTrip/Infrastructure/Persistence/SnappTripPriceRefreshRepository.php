<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Models\ProviderPriceRefreshState;
use Illuminate\Support\Collection;

final class SnappTripPriceRefreshRepository
{
    public function __construct(private readonly HotelPriceRefreshScheduleRepository $sharedSchedules)
    {
    }

    public function assertReady(): void
    {
        $this->sharedSchedules->assertReady();
    }

    public function synchronizeStates(Provider $provider): int
    {
        $schedules = $this->sharedSchedules->activeForMappingRepair();
        if ($schedules->isEmpty()) {
            return 0;
        }

        $mapped = AccommodationProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('is_disabled', false)
            ->whereNotNull('provider_property_id')
            ->whereIn('accommodation_id', $schedules->pluck('gds_id')->map(fn ($id): int => (int) $id))
            ->get(['accommodation_id'])
            ->pluck('accommodation_id')
            ->map(fn ($id): int => (int) $id)
            ->flip();

        $count = 0;
        foreach ($schedules as $schedule) {
            $accommodationId = (int) $schedule->gds_id;
            if ($accommodationId <= 0 || !$mapped->has($accommodationId)) {
                continue;
            }

            ProviderPriceRefreshState::query()->updateOrCreate(
                ['provider_id' => $provider->id, 'accommodation_id' => $accommodationId],
                ['schedule_id' => (int) $schedule->id],
            );
            $count++;
        }

        return $count;
    }

    /** @return Collection<int,ProviderPriceRefreshState> */
    public function due(Provider $provider, int $limit): Collection
    {
        return ProviderPriceRefreshState::query()
            ->where('provider_id', $provider->id)
            ->where(function ($query): void {
                $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->whereHas('accommodation', fn ($query) => $query->where('is_active', true))
            ->orderBy('next_run_at')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();
    }

    public function findForProvider(Provider $provider, int $stateId): ?ProviderPriceRefreshState
    {
        return ProviderPriceRefreshState::query()
            ->whereKey($stateId)
            ->where('provider_id', $provider->id)
            ->first();
    }

    public function mapForState(Provider $provider, ProviderPriceRefreshState $state): ?AccommodationProviderMap
    {
        return AccommodationProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_id', $state->accommodation_id)
            ->where('is_disabled', false)
            ->whereNotNull('provider_property_id')
            ->first();
    }

    public function markRequestStarted(ProviderPriceRefreshState $state): void
    {
        $state->update(['last_request_at' => now(), 'last_error' => null]);
    }

    public function markHttpSuccess(ProviderPriceRefreshState $state): void
    {
        $state->update(['last_success_at' => now(), 'last_error' => null]);
    }

    public function markPersisted(ProviderPriceRefreshState $state): void
    {
        $minutes = $this->refreshIntervalMinutes($state);
        $state->update([
            'last_persisted_at' => now(),
            'next_run_at' => now()->addMinutes($minutes),
            'last_error' => null,
        ]);
    }

    public function markSkipped(ProviderPriceRefreshState $state): void
    {
        $state->update(['next_run_at' => now()->addMinutes($this->refreshIntervalMinutes($state))]);
    }

    public function markFailure(ProviderPriceRefreshState $state, string $message, int $retryMinutes = 5): void
    {
        $state->update([
            'last_error' => mb_substr(trim($message), 0, 1000),
            'next_run_at' => now()->addMinutes(max(1, $retryMinutes)),
        ]);
    }

    private function refreshIntervalMinutes(ProviderPriceRefreshState $state): int
    {
        if ($state->schedule_id === null) {
            return 60;
        }

        $schedule = $this->sharedSchedules->active((int) $state->schedule_id, (int) $state->accommodation_id);

        return max(1, (int) ($schedule?->refresh_interval_minutes ?? 60));
    }
}
