<?php

namespace App\Console\Commands\V2;

use App\Jobs\Hotel\V2\SyncGrsDuePricesJob;
use Illuminate\Console\Command;

class SyncGrsPricesCommand extends Command
{
    protected $signature = 'grs:sync-prices {--days= : Number of days to refresh (default: 90)}';

    protected $description = 'Dispatch due GRS-only prices and inventory using shared SSP schedules';

    public function handle(): int
    {
        $value = $this->option('days');
        if ($value !== null && $value !== '') {
            if (!ctype_digit((string) $value) || (int) $value < 1 || (int) $value > 3650) {
                $this->error('--days must be an integer between 1 and 3650.');
                return self::FAILURE;
            }
        }

        if (config('queue.default') === 'sync') {
            $this->error('GRS prices require an asynchronous queue (database or redis); QUEUE_CONNECTION=sync is unsafe.');
            return self::FAILURE;
        }
        if (!in_array(config('cache.default'), ['database', 'redis'], true)) {
            $this->error('GRS prices require a shared CACHE_STORE=database or redis for the global API limiter.');
            return self::FAILURE;
        }

        $days = $value === null || $value === '' ? null : (int) $value;
        SyncGrsDuePricesJob::dispatch($days);
        $this->info('GRS due price refresh queued; days: '.($days ?? 90).'. Only shared SSP due hotels will be selected.');
        return self::SUCCESS;
    }
}
