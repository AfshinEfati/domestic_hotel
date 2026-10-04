<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncDuePricesJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncPricesCommand extends Command
{
    protected $signature = 'snapptrip:sync-prices {--days= : Calendar horizon in days}';
    protected $description = 'Queue due SnappTrip rate and inventory refresh work.';

    public function handle(ProviderOutboundGuard $guard): int
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');
            return self::FAILURE;
        }

        $days = $this->option('days');
        $days = $days === null || $days === '' ? null : (int) $days;
        if ($days !== null && ($days < 1 || $days > 3650)) {
            $this->error('The --days value must be between 1 and 3650.');
            return self::INVALID;
        }

        SyncDuePricesJob::dispatch($days)->onQueue('snapptrip-prices');
        $this->info('SnappTrip due price synchronization was queued.');

        return self::SUCCESS;
    }
}
