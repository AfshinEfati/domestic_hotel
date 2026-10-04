<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SyncCatalog;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncCatalogJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $timeout = 60;
    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->onQueue('snapptrip-static');
    }

    public function uniqueId(): string
    {
        return 'snapptrip-v2-catalog';
    }

    public function handle(ProviderOutboundGuard $guard, SyncCatalog $sync): void
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            return;
        }

        try {
            $sync->execute();
        } catch (SnappTripRateLimitExceeded $exception) {
            $this->release($exception->retryAfterSeconds);
        }
    }
}
