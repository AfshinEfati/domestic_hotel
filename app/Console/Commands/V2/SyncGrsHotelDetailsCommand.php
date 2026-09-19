<?php

namespace App\Console\Commands\V2;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository;
use App\Jobs\Hotel\V2\SyncGrsHotelDetailsJob;
use Illuminate\Console\Command;

class SyncGrsHotelDetailsCommand extends Command
{
    protected $signature = 'grs:sync-details';

    protected $description = 'Queue a weekly GRS property-details refresh for mapped hotels (10 per minute; no availability or prices)';

    public function handle(GrsHotelDetailsRepository $repository): int
    {
        if (config('queue.default') !== 'redis' || !in_array(config('cache.default'), ['redis', 'database'], true)) {
            $this->error('GRS details require QUEUE_CONNECTION=redis and shared CACHE_STORE=redis or database.');
            return self::FAILURE;
        }

        $provider = $repository->grsProvider();
        if ($provider === null || !$provider->is_active || !$provider->is_online) {
            $this->error('GRS provider is missing, inactive or offline.');
            return self::FAILURE;
        }

        $mappings = $repository->mappedHotels((int) $provider->id);
        if ($mappings->isEmpty()) {
            $this->warn('No mapped GRS hotels found. Run grs:sync-hotels first.');
            return self::SUCCESS;
        }

        $start = now();
        foreach ($mappings as $index => $mapping) {
            // One scheduled HTTP request every six seconds, hence 10/minute.
            // The worker has a separate guard against backlog bursts.
            SyncGrsHotelDetailsJob::dispatch((int) $provider->id, (int) $mapping->id)
                ->delay($start->copy()->addSeconds($index * 6));
        }

        $this->info($mappings->count().' mapped GRS hotel details scheduled on grs-details, ten per minute.');
        $this->info('This command does not dispatch catalog, pricing or availability jobs.');
        return self::SUCCESS;
    }
}
