<?php
namespace App\Jobs\Hotel;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class DispatchAccommodationSyncJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $providerCode;

    public function __construct(string $providerCode)
    {
        $this->providerCode = $providerCode;
    }

    public function handle(): void
    {
        $provider = Provider::where('code', $this->providerCode)->firstOrFail();
        foreach ($provider->cityMaps as $cityMap) {
            SyncAccommodationsByCityJob::dispatch($provider->code, $cityMap->provider_city_id, $cityMap->city_id);
        }
    }
}
