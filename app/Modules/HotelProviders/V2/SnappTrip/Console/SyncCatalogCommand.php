<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncCatalogJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncCatalogCommand extends Command
{
    protected $signature = 'snapptrip:sync-catalog';
    protected $description = 'Synchronize SnappTrip cities and schedule static hotel catalog refresh jobs.';

    public function handle(ProviderOutboundGuard $guard): int
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');
            return self::FAILURE;
        }

        SyncCatalogJob::dispatch()->onQueue('snapptrip-static');
        $this->info('SnappTrip catalog synchronization was queued.');

        return self::SUCCESS;
    }
}
