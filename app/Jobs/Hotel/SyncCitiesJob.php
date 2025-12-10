<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $providerCode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $providerCode)
    {
        $this->providerCode = $providerCode;
    }

    /**
     * Execute the job.
     * @throws BindingResolutionException
     */
    public function handle(CityRepository $cityRepository): void
    {
        $provider = Provider::query()->where('code', $this->providerCode)->firstOrFail();
        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);
        $cities = $adapter->fetchCities();

        foreach ($cities as $c) {
            if (empty($c['province_name']) || empty($c['name'])) {
                continue;
            }

            $cityRepository->upsertFromProvider($c, $provider);
        }

        \Log::info("Sync cities completed for {$provider->code}", [
            'count' => $cities->count(),
        ]);
    }

}
