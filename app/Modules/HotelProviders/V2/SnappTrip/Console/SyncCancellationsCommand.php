<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncPendingCancellationsJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncCancellationsCommand extends Command
{
    protected $signature = 'snapptrip:sync-cancellations {--limit=100 : Maximum pending cancellations to poll}';
    protected $description = 'Poll pending SnappTrip cancellation requests and persist their latest state.';

    public function handle(ProviderOutboundGuard $guard): int
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');
            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        SyncPendingCancellationsJob::dispatch($limit)->onQueue('snapptrip-operations');
        $this->info('SnappTrip pending cancellation polling was queued.');

        return self::SUCCESS;
    }
}
