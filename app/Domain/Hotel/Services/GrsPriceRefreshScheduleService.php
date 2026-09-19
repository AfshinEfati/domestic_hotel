<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Support\Collection;

/** GRS scheduling policy; the scheduler never queries the database itself. */
class GrsPriceRefreshScheduleService
{
    public function __construct(
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly ProviderRepositoryInterface $providers,
    ) {
    }

    public function grsProvider(): ?Provider
    {
        return $this->providers->findByCode('grs');
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
        return $this->schedules->due($capacity);
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

    public function persisted(int $id, int $gdsId): int
    {
        return $this->schedules->markPersisted($id, $gdsId);
    }
}
