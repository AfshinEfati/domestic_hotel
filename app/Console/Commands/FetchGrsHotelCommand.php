<?php

namespace App\Console\Commands;

use App\Jobs\Hotel\FetchGrsHotelJob;
use Illuminate\Console\Command;

class FetchGrsHotelCommand extends Command
{
    protected $signature = 'fetch:grs-hotel';

    protected $description = 'Command description';

    public function handle(): void
    {
        FetchGrsHotelJob::dispatch('grs');
    }
}
