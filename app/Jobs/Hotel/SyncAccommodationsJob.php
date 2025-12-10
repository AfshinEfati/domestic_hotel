<?php

namespace App\Jobs\Hotel;

use App\Models\Accommodation;
use App\Models\AccommodationType;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\Provider;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAccommodationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $providerCode;

    public function __construct(string $providerCode)
    {
        $this->providerCode = $providerCode;
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $provider = Provider::where('code', $this->providerCode)->firstOrFail();

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);

        // همه‌ی city map های مربوط به provider
        $providerCities = $provider->cityMaps()->get(); // رابطه توی مدل Provider تعریف شده باشه

        foreach ($providerCities as $pc) {
            $properties = $adapter->fetchPropertiesByCity($pc->provider_city_id);

            foreach ($properties as $p) {
                // city داخلی
                $city = City::find($pc->city_id);
                if (!$city) {
                    continue;
                }

                // type
                $type = AccommodationType::firstOrCreate(
                    ['fa_name' => $p['type']],
                    ['en_name' => $p['type_en'] ?? null]
                );

                // accommodation
                $acc = Accommodation::updateOrCreate(
                    [
                        'city_id' => $city->id,
                        'fa_name' => $p['name'],
                    ],
                    [
                        'en_name' => $p['name_en'] ?? null,
                        'accommodation_type_id' => $type->id,
                        'star' => (int)($p['star'] ?? 0),
                        'grade' => $p['grade'] ?? null,
                        'address' => $p['address'] ?? null,
                        'lat' => ($p['latitude'] && abs($p['latitude']) <= 90) ? $p['latitude'] : null,
                        'lng' => ($p['longitude'] && abs($p['longitude']) <= 180) ? $p['longitude'] : null,
                        'is_active' => true,
                    ]
                );

                // accommodation_provider_maps
                \DB::table('accommodation_provider_maps')->updateOrInsert(
                    [
                        'provider_id'          => $provider->id,
                        'provider_property_id' => (string)$p['id'],
                    ],
                    [
                        'accommodation_id' => $acc->id,
                        'fa_name'          => $p['name'] ?? null,
                        'en_name'          => $p['name_en'] ?? null,
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );


                // facilities
                if (!empty($p['facilities'])) {
                    $facilityIds = [];
                    foreach ($p['facilities'] as $f) {
                        $group = FacilityGroup::firstOrCreate(
                            ['fa_name' => $f['group_name'] ?? 'سایر'],
                            ['en_name' => $f['group_name_en'] ?? null]
                        );

                        $facility = Facility::updateOrCreate(
                            [
                                'fa_name' => $f['name'],
                                'facility_group_id' => $group->id,
                            ],
                            ['en_name' => $f['name_en'] ?? null]
                        );

                        $facilityIds[$facility->id] = ['description' => $f['description'] ?? ''];
                    }

                    $acc->facilities()->sync($facilityIds);
                }
            }
        }

        \Log::info("Accommodations synced from {$provider->code}");
    }

}
