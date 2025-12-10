<?php

namespace App\Console\Commands;

use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class TestJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-job-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('TestJobCommand executed successfully!');
        SyncGrsAvailabilityJob::dispatch();
        return CommandAlias::SUCCESS;
    }
}
