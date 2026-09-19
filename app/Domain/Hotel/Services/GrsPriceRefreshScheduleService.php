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

    /** @return Collection<int, HotelPriceRefreshSchedule> */
    public function due(Provider $provider): Collection
    {
        // One hotel = one planned availability request. Supplemental calls, when
        // necessary, are charged separately by the HTTP adapter's rate limiter.
        $capacity = min(10, max(1, (int) data_get($provider->config, 'availability_rate_limit.max_requests', 10)));
        return $this->schedules->due($capacity);
    }

    public function active(int $id, string $grsId): ?HotelPriceRefreshSchedule
    {
        return $this->schedules->active($id, $grsId);
    }

    public function requestStarted(int $id, string $grsId): void
    {
        $this->schedules->markRequestStarted($id, $grsId);
    }

    public function http200(int $id, string $grsId): void
    {
        $this->schedules->markHttp200($id, $grsId);
    }

    public function persisted(int $id, string $grsId): int
    {
        return $this->schedules->markPersisted($id, $grsId);
    }
}
