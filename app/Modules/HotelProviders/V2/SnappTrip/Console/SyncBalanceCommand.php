<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncBalanceJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncBalanceCommand extends Command
{
    protected $signature = 'snapptrip:sync-balance';
    protected $description = 'Synchronize the current SnappTrip wallet balance.';

    public function handle(ProviderOutboundGuard $guard): int
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');
            return self::FAILURE;
        }

        SyncBalanceJob::dispatch()->onQueue('snapptrip-operations');
        $this->info('SnappTrip balance synchronization was queued.');

        return self::SUCCESS;
    }
}
