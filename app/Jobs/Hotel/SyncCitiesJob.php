<?php

namespace App\Jobs\Hotel;

use App\Models\City;
use App\Models\Country;
use App\Models\Provider;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\ProviderCityMap;
use App\Models\State;
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
    public function handle(): void
    {
        $provider = Provider::where('code', $this->providerCode)->firstOrFail();
        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);
        $cities = $adapter->fetchCities();
        foreach ($cities as $c) {
            $country = Country::firstOrCreate(
                ['iso2' => $c['country_code_alpha_2'] ?? 'IR'],
                [
                    'fa_name' => $c['country_name'] ?? 'ایران',
                    'en_name' => $c['country_name_en'] ?? 'Iran',
                    'iso3'    => $c['country_code_alpha_3'] ?? 'IRN',
                ]
            );

            $state = State::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'fa_name'    => $c['province_name'],
                ],
                [
                    'en_name' => $c['province_name_en'] ?? null,
                ]
            );
            $city = City::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'fa_name'    => $c['name'], // همون name فارسی
                ],
                [
                    'en_name'    => $c['name_en'] ?? null,
                    'state_id'   => $state->id,
                    'is_active'  => true,
                    'is_popular' => false,
                ]
            );

            // 4) Map با provider
            ProviderCityMap::updateOrCreate(
                [
                    'provider_id'      => $provider->id,
                    'provider_city_id' => (string)($c['id']),
                ],
                [
                    'city_id'  => $city->id,
                    'fa_name'  => $c['name'],
                    'en_name'  => $c['name_en'] ?? null,
                ]
            );
        }

        \Log::info("Sync cities completed for {$provider->code}", [
            'count' => $cities->count(),
        ]);
    }

}
