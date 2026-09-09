<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DispatchRoomTypeFetchJobs;
/*
 * It's for fetching hotel data including room types from active providers
 * it will dispatch jobs for each active provider to fetch room types and related data
 * this command can be scheduled to run periodically to keep hotel data up to date
 */
class FetchHotelDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotel:fetch-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch hotel data including room types from active providers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting hotel data fetch...');

        DispatchRoomTypeFetchJobs::dispatch();

        $this->info('DispatchRoomTypeFetchJobs dispatched successfully.');
    }
}
