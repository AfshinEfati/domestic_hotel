<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Repositories\GrsAvailabilityPersistenceRepository;
use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Accommodation;
use App\Models\AccommodationProviderMap;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\Provider;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Provider-specific GRS refresh policy with repository-backed data access. */
class GrsPriceRefreshScheduleService
{
    public function __construct(
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly ProviderRepositoryInterface $providers,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
        private readonly AccommodationRepositoryInterface $accommodations,
        private readonly GrsAvailabilityPersistenceRepository $availability,
    ) {
    }

    public function grsProvider(): ?Provider
    {
        return $this->providers->findByCode('grs');
    }

    public function providerById(int $id): ?Provider
    {
        return $this->providers->find($id);
    }

    public function mapForAccommodation(int $gdsId, int $providerId): ?AccommodationProviderMap
    {
        return $this->maps->findForAccommodationAndProvider($gdsId, $providerId);
    }

    public function disableMapForAccommodation(int $gdsId, int $providerId): bool
    {
        return $this->maps->disableForAccommodationAndProvider($gdsId, $providerId);
    }

    public function accommodationById(int $gdsId): ?Accommodation
    {
        return $this->accommodations->find($gdsId);
    }

    public function schedulerEnabled(): bool
    {
        $provider = $this->grsProvider();
        return $provider !== null && GrsRefreshSettings::from($provider)['scheduler_enabled'];
    }

    public function assertReady(): void
    {
        $this->schedules->assertReady();
    }

    /** @return Collection<int, HotelPriceRefreshSchedule> */
    public function due(Provider $provider): Collection
    {
        $capacity = min(10, max(1, (int) data_get($provider->config, 'availability_rate_limit.max_requests', 10)));
        $selected = collect();
        $offset = 0;

        do {
            $batch = $this->schedules->due($capacity, $offset);

            $existingAccommodationIds = collect(
                $this->accommodations->existingIds(
                    $batch
                        ->pluck('gds_id')
                        ->map(fn ($id): int => (int) $id)
                        ->filter(fn (int $id): bool => $id > 0)
                        ->unique()
                        ->values()
                        ->all()
                )
            )->flip();

            foreach ($batch as $schedule) {
                $gdsId = (int) $schedule->gds_id;

                // Shared SSP may contain stale/orphan hotel IDs. They are not
                // actionable in Domestic Hotel and must be ignored silently.
                if ($gdsId <= 0 || !$existingAccommodationIds->has($gdsId)) {
                    continue;
                }

                $map = $this->mapForAccommodation($gdsId, (int) $provider->id);

                if ($map?->is_disabled === true) {
                    continue;
                }

                $selected->push($schedule);

                if ($selected->count() >= $capacity) {
                    return $selected;
                }
            }

            $offset += $batch->count();
        } while ($batch->count() === $capacity);

        return $selected;
    }

    /** @return Collection<int, HotelPriceRefreshSchedule> */
    public function activeForMappingRepair(): Collection
    {
        $schedules = $this->schedules->activeForMappingRepair();

        if ($schedules->isEmpty()) {
            return $schedules;
        }

        $existingAccommodationIds = collect(
            $this->accommodations->existingIds(
                $schedules
                    ->pluck('gds_id')
                    ->map(fn ($id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values()
                    ->all()
            )
        )->flip();

        return $schedules
            ->filter(
                fn (HotelPriceRefreshSchedule $schedule): bool =>
                    $existingAccommodationIds->has((int) $schedule->gds_id)
            )
            ->values();
    }

    public function active(int $id, int $gdsId): ?HotelPriceRefreshSchedule
    {
        return $this->schedules->active($id, $gdsId);
    }

    public function requestStarted(int $id, int $gdsId): void
    {
        $this->schedules->markRequestStarted($id, $gdsId);
    }

    public function http200(int $id, int $gdsId): void
    {
        $this->schedules->markHttp200($id, $gdsId);
    }

    /** @param Collection<int, array<string, mixed>>|null $response */
    public function verifiedRowCount(
        int $providerId,
        int $accommodationProviderMapId,
        int $gdsId,
        string $providerPropertyId,
        ?Collection $response,
        CarbonInterface $started,
    ): int {
        return $this->availability->verifiedRowCount(
            $providerId,
            $accommodationProviderMapId,
            $gdsId,
            $providerPropertyId,
            $response,
            $started
        );
    }

    public function persisted(int $id, int $gdsId): int
    {
        return $this->schedules->markPersisted($id, $gdsId);
    }

    public function providerAnomalyHandled(int $id, int $gdsId): int
    {
        return $this->schedules->markProviderAnomalyHandled($id, $gdsId);
    }

    public function mappingIssueHandled(int $id, int $gdsId): int
    {
        return $this->schedules->markMappingIssueHandled($id, $gdsId);
    }
}
