<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Hotel\SyncPartoCitiesJob;

class HotelSyncPartoCitiesCommand extends Command
{
    protected $signature = 'hotel:sync-parto-cities';
    protected $description = 'Sync cities from Parto JSON file';

    public function handle(): void
    {
        SyncPartoCitiesJob::dispatchSync();
        $this->info("Parto cities sync dispatched.");
    }
}
