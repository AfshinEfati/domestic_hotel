<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SyncHotelDetails;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncHotelDetailsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $timeout = 90;
    public int $uniqueFor = 1800;

    /** @param string[] $providerPropertyIds @param array<string,?string> $providerUrls */
    public function __construct(public array $providerPropertyIds, public array $providerUrls = [])
    {
        $this->onQueue('snapptrip-static');
    }

    public function uniqueId(): string
    {
        $ids = $this->providerPropertyIds;
        sort($ids, SORT_STRING);

        return 'snapptrip-v2-details:'.sha1(implode(',', $ids));
    }

    public function handle(ProviderOutboundGuard $guard, SyncHotelDetails $sync): void
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            return;
        }

        try {
            $sync->execute($this->providerPropertyIds, $this->providerUrls);
        } catch (SnappTripRateLimitExceeded $exception) {
            $this->release($exception->retryAfterSeconds);
        }
    }
}
