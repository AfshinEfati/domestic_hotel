<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Carbon\CarbonImmutable;
use RuntimeException;

final class RefreshScheduledAvailability
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
        private readonly RefreshAvailability $availability,
    ) {
    }

    public function execute(int $scheduleId, int $accommodationId, int $providerId, int $days): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        if ((int) $provider->id !== $providerId) {
            return 0;
        }

        // The shared row remains authoritative for whether this hotel cycle still exists,
        // but provider workers no longer advance next_gds_run_at themselves.
        $schedule = $this->schedules->active($scheduleId, $accommodationId);
        if ($schedule === null) {
            return 0;
        }

        $map = $this->maps->findForAccommodationAndProvider($accommodationId, $providerId);
        if (
            $map === null
            || $map->is_disabled
            || trim((string) $map->provider_property_id) === ''
        ) {
            return 0;
        }

        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('SnappTrip availability days must be between 1 and 3650.');
        }

        $from = CarbonImmutable::now('Asia/Tehran')->toDateString();
        $to = CarbonImmutable::now('Asia/Tehran')->addDays($days)->toDateString();
        $rows = $this->availability->execute($map, $from, $to);

        return $rows->count();
    }
}
