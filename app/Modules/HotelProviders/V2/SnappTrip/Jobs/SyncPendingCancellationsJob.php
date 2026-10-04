<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripCancellationService;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncPendingCancellationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $timeout = 60;
    public int $uniqueFor = 120;

    public function __construct(public int $limit = 100)
    {
        $this->onQueue('snapptrip-operations');
    }

    public function uniqueId(): string
    {
        return 'snapptrip-v2-pending-cancellations';
    }

    public function handle(ProviderOutboundGuard $guard, SnappTripCancellationService $cancellations): void
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            return;
        }

        try {
            $cancellations->syncPending($this->limit);
        } catch (SnappTripRateLimitExceeded $exception) {
            $this->release($exception->retryAfterSeconds);
        }
    }
}
