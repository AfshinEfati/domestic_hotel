<?php

namespace App\Console\Commands\V2;

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use Illuminate\Console\Command;

class SyncGrsPricesCommand extends Command
{
    protected $signature = 'grs:sync-prices {--days= : Override the provider-configured day range}';

    protected $description = 'Manual GRS-only alias for the coordinated hotel provider refresh scheduler';

    public function handle(ProviderPriceRefreshScheduler $scheduler): int
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
        $count = $scheduler->dispatch('grs', $days);
        $label = $days === null
            ? 'provider-configured (fallback '.GrsRefreshSettings::defaults()['default_days'].')'
            : (string) $days;

        $this->info("Queued {$count} coordinated GRS hotel refresh job(s); days: {$label}.");
        return self::SUCCESS;
    }
}
