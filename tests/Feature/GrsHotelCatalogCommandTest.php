<?php

namespace Tests\Feature;

use App\Jobs\Hotel\SyncCitiesJob;
use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrsHotelCatalogCommandTest extends TestCase
{
    public function test_dedicated_command_only_queues_the_catalog_job(): void
    {
        Bus::fake();
        Http::fake();

        $this->artisan('grs:sync-hotels')->assertExitCode(0);

        Bus::assertDispatched(SyncGrsHotelCatalogJob::class, 1);
        Bus::assertDispatched(SyncGrsHotelCatalogJob::class,
            fn (SyncGrsHotelCatalogJob $job) => $job->queue === 'grs-hotels');
        Bus::assertNotDispatched(SyncCitiesJob::class);
        Bus::assertNotDispatched(SyncGrsAvailabilityJob::class);
        Http::assertNothingSent();
    }
}
