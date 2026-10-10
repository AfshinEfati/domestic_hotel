<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncPricesCommand extends Command
{
    protected $signature = 'snapptrip:sync-prices {--days= : Calendar horizon in days}';
    protected $description = 'Manual SnappTrip-only alias for the coordinated hotel provider refresh scheduler.';

    public function handle(
        ProviderOutboundGuard $guard,
        ProviderPriceRefreshScheduler $scheduler,
    ): int {
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

        if (config('queue.default') === 'sync') {
            $this->error('SnappTrip prices require an asynchronous queue; QUEUE_CONNECTION=sync is unsafe.');
            return self::FAILURE;
        }

        $count = $scheduler->dispatch(SnappTripSettings::PROVIDER_CODE, $days);
        $this->info("Queued {$count} coordinated SnappTrip hotel refresh job(s).");

        return self::SUCCESS;
    }
}
