<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripPriceRefreshRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Carbon\CarbonImmutable;
use RuntimeException;
use Throwable;

final class RefreshScheduledAvailability
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripPriceRefreshRepository $refresh,
        private readonly RefreshAvailability $availability,
    ) {
    }

    public function execute(int $stateId, int $days): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $state = $this->refresh->findForProvider($provider, $stateId);
        if ($state === null) {
            return 0;
        }

        $map = $this->refresh->mapForState($provider, $state);
        if ($map === null) {
            $this->refresh->markSkipped($state);
            return 0;
        }

        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('SnappTrip availability days must be between 1 and 3650.');
        }

        $from = CarbonImmutable::now('Asia/Tehran')->toDateString();
        $to = CarbonImmutable::now('Asia/Tehran')->addDays($days)->toDateString();
        $this->refresh->markRequestStarted($state);

        try {
            $rows = $this->availability->execute($map, $from, $to);
            $map->refresh();
            if ($map->is_disabled) {
                $this->refresh->markSkipped($state);
                return 0;
            }
            $this->refresh->markHttpSuccess($state);
            $this->refresh->markPersisted($state);

            return $rows->count();
        } catch (Throwable $exception) {
            $this->refresh->markFailure($state, $exception->getMessage());
            throw $exception;
        }
    }
}
