<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SyncBalance;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncBalanceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 45;
    public int $uniqueFor = 120;

    public function __construct()
    {
        $this->onQueue('snapptrip-operations');
    }

    public function uniqueId(): string
    {
        return 'snapptrip-v2-balance';
    }

    public function handle(ProviderOutboundGuard $guard, SyncBalance $sync): void
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
