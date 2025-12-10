<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Hotel\SyncFacilitiesJob;

class HotelSyncFacilitiesCommand extends Command
{
    protected $signature = 'hotel:sync-facilities {provider}';
    protected $description = 'Sync facilities from given provider';

    public function handle(): void
    {
        $provider = $this->argument('provider');
        SyncFacilitiesJob::dispatchSync($provider);

        $this->info("Facilities sync executed for {$provider}");
    }
}
