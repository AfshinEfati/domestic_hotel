<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Repositories\HotelProviderRefreshStateRepository;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use RuntimeException;
use Throwable;

/**
 * Hotel-centric shared schedule with provider-specific local execution state.
 *
 * The SSP table decides when a hotel is due. Local provider states remember which
 * mapped providers have completed that due cycle. Each provider then consumes its
 * own quota independently; the shared hotel is advanced only after every mapped,
 * registered provider reaches a terminal done/attempted state.
 */
class ProviderPriceRefreshScheduler
{
    private const SYNC_LIMIT = 10000;

    public function __construct(
        private readonly ProviderRepositoryInterface $providers,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly HotelProviderRefreshStateRepository $states,
        private readonly ProviderRefreshCoordinator $coordinator,
        private readonly HotelProviderRegistry $registry,
    ) {
    }

    public function dispatch(?string $onlyProviderCode = null, ?int $days = null): int
    {
        $this->schedules->assertReady();
        $entries = $this->providerEntries();
        if ($entries === []) {
            return 0;
        }

        $this->syncDueHotels($entries);

        $dispatched = 0;
        foreach ($entries as [$provider, $handler]) {
            if ($onlyProviderCode !== null && (string) $provider->code !== $onlyProviderCode) {
                continue;
            }
            if (!$handler->enabled($provider)) {
                continue;
            }

            $capacity = max(1, $handler->hotelCapacityPerMinute($provider));
            foreach ($this->states->claimForProvider((int) $provider->id, $capacity) as $state) {
                $map = $state->accommodation_provider_map_id !== null
                    ? $this->maps->find((int) $state->accommodation_provider_map_id)
                    : null;

                if (!$map instanceof AccommodationProviderMap
                    || (int) $map->provider_id !== (int) $provider->id
                    || (int) $map->accommodation_id !== (int) $state->accommodation_id
                    || $map->is_disabled
                    || trim((string) $map->provider_property_id) === '') {
                    $this->coordinator->attempted((int) $state->id, ProviderRefreshOutcome::MAP_UNAVAILABLE);
                    continue;
                }

                if (!$this->schedules->isCurrentCycle(
                    (int) $state->shared_schedule_id,
                    (int) $state->accommodation_id,
                    $state->source_due_at?->format('Y-m-d H:i:s'),
                )) {
                    $this->coordinator->attempted((int) $state->id, ProviderRefreshOutcome::CYCLE_SUPERSEDED);
                    continue;
                }

                try {
                    $queued = $handler->dispatch(
                        $provider,
                        $map,
                        (int) $state->id,
                        (int) $state->shared_schedule_id,
                        (int) $state->accommodation_id,
                        $days,
                    );
                } catch (Throwable $exception) {
                    $this->coordinator->retry(
                        (int) $state->id,
                        ProviderRefreshOutcome::INTERNAL_ERROR,
                        60,
                    );
                    report($exception);
                    continue;
                }

                if ($queued) {
                    $dispatched++;
                    continue;
                }

                $this->coordinator->attempted((int) $state->id, ProviderRefreshOutcome::SCHEDULER_DISABLED);
            }
        }

        return $dispatched;
    }

    /** @return array<int,array{0:Provider,1:PriceRefreshSchedulerHandler}> */
    private function providerEntries(): array
    {
        $entries = [];
        foreach ($this->providers->getAll() as $provider) {
            $handlerClass = $this->registry->priceRefreshHandler((string) $provider->code);
            if ($handlerClass === null) {
                continue;
            }

            $handler = app($handlerClass);
            if (!$handler instanceof PriceRefreshSchedulerHandler) {
                throw new RuntimeException("Invalid price refresh scheduler handler for {$provider->code}.");
            }

            $entries[(int) $provider->id] = [$provider, $handler];
        }

        return $entries;
    }

    /** @param array<int,array{0:Provider,1:PriceRefreshSchedulerHandler}> $entries */
    private function syncDueHotels(array $entries): void
    {
        $due = $this->schedules->due(self::SYNC_LIMIT);
        if ($due->isEmpty()) {
            return;
        }

        $mapsByHotel = $this->maps->forAccommodations(
            $due->pluck('gds_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all()
        )->groupBy('accommodation_id');

        foreach ($due as $schedule) {
            $accommodationId = (int) $schedule->gds_id;
            if ($accommodationId <= 0) {
                continue;
            }

            $sourceDueAt = $schedule->next_gds_run_at?->format('Y-m-d H:i:s');
            $cycleKey = $this->states->cycleKey((int) $schedule->id, $accommodationId, $sourceDueAt);
            $tracked = 0;

            foreach ($mapsByHotel->get($accommodationId, collect()) as $map) {
                $entry = $entries[(int) $map->provider_id] ?? null;
                if ($entry === null) {
                    continue;
                }

                [$provider, $handler] = $entry;
                $outcome = null;
                $dispatchable = true;

                if (!$provider->is_active) {
                    $dispatchable = false;
                    $outcome = ProviderRefreshOutcome::PROVIDER_DISABLED;
                } elseif ($map->is_disabled) {
                    $dispatchable = false;
                    $outcome = ProviderRefreshOutcome::MAP_DISABLED;
                } elseif (trim((string) $map->provider_property_id) === '') {
                    $dispatchable = false;
                    $outcome = ProviderRefreshOutcome::MAP_UNAVAILABLE;
                } elseif (!$handler->enabled($provider)) {
                    $dispatchable = false;
                    $outcome = ProviderRefreshOutcome::SCHEDULER_DISABLED;
                }

                $this->states->ensure(
                    (int) $schedule->id,
                    $accommodationId,
                    $provider,
                    $map,
                    $sourceDueAt,
                    $dispatchable,
                    $outcome,
                );
                $tracked++;
            }

            // No registered provider map means there is nothing this service can
            // refresh for the hotel. All-disabled/inactive maps also become terminal
            // states above. In both cases the shared cycle must not remain due forever.
            if ($tracked === 0 || !$this->states->hasOpenState($cycleKey)) {
                $this->coordinator->finalizeCycle(
                    (int) $schedule->id,
                    $accommodationId,
                    $sourceDueAt,
                    $cycleKey,
                );
            }
        }
    }
}
