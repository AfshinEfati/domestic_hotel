<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\Provider;
use Illuminate\Support\Collection;

/** Scheduling policy uses only SSP due time and GRS's existing API request quota. */
class GrsPriceRefreshScheduleService
{
    public function __construct(private readonly HotelPriceRefreshScheduleRepository $schedules)
    {
    }

    public function assertReady(): void
    {
        $this->schedules->assertReady();
    }

    /** @return Collection<int, HotelPriceRefreshSchedule> */
    public function due(Provider $provider): Collection
    {
        // Ten due hotels mean ten planned availability calls. The adapter
        // still meters occasional supplemental requests against the same quota.
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
