<?php

namespace App\Console\Commands\V2;

use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob;
use Illuminate\Console\Command;

class SyncGrsHotelsCommand extends Command
{
    protected $signature = 'grs:sync-hotels';

    protected $description = 'Queue only the GRS (Aghamat24) hotel catalog sync; no city, room or availability requests';

    public function handle(): int
    {
        SyncGrsHotelCatalogJob::dispatch();
        $this->info('GRS hotel catalog sync queued. Start a queue worker to process it.');

        return self::SUCCESS;
    }
}
