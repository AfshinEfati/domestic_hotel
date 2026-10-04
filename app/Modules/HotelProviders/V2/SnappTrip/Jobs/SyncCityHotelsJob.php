<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SyncCityHotels;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncCityHotelsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $timeout = 60;
    public int $uniqueFor = 1800;

    public function __construct(public string $providerCityId)
    {
        $this->onQueue('snapptrip-static');
    }

    public function uniqueId(): string
    {
        return 'snapptrip-v2-city-hotels:'.$this->providerCityId;
    }

    public function handle(ProviderOutboundGuard $guard, SyncCityHotels $sync): void
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            return;
        }

        try {
            $sync->execute($this->providerCityId);
        } catch (SnappTripRateLimitExceeded $exception) {
            $this->release($exception->retryAfterSeconds);
        }
    }
}
