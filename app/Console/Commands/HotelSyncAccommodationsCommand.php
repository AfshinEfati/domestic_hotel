<?php

namespace App\Console\Commands;

use App\Jobs\Hotel\DispatchAccommodationSyncJobs;
use Illuminate\Console\Command;

class HotelSyncAccommodationsCommand extends Command
{
    protected $signature = 'hotel:sync-accommodations {provider}';
    protected $description = 'Sync accommodations from provider';

    public function handle(): void
    {
        $provider = $this->argument('provider');
        DispatchAccommodationSyncJobs::dispatch($provider);
        $this->info("Accommodations synced for {$provider}");
    }
}
