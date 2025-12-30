<?php
namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAccommodationsByCityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $providerCode,
        public string $providerCityId,
        public int $cityId
    ) {}

    /**
     * @throws BindingResolutionException
     */
    public function handle(
        ProviderRepositoryInterface $providerRepo,
        CityRepositoryInterface $cityRepo,
        AccommodationTypeRepositoryInterface $accTypeRepo,
        AccommodationRepositoryInterface $accRepo,
        AccommodationProviderMapRepositoryInterface $mapRepo,
        FacilityGroupRepositoryInterface $facilityGroupRepo,
        FacilityRepositoryInterface $facilityRepo
    ): void
    {
        $provider = $providerRepo->findDynamic(where: ['code' => $this->providerCode]);
        if (!$provider) {
            // Or log error
            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);
        $properties = $adapter->fetchPropertiesByCity($this->providerCityId);

        foreach ($properties as $p) {
            // city داخلی
            $city = $cityRepo->find($this->cityId);
            if (!$city) {
                continue;
            }

            // type
            $type = $accTypeRepo->firstOrCreate(
                ['fa_name' => $p['type']],
                ['en_name' => $p['type_en'] ?? null]
            );

            // accommodation
            $acc = $accRepo->updateOrCreate(
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

            // Map
            // Since updateOrInsert is a DB query builder method, we can use updateOrCreate on repository if model supports it,
            // or we might need to check existence first.
            // Assuming AccommodationProviderMap model has these fields fillable.
            $mapRepo->updateOrCreate(
                [
                    'provider_id'          => $provider->id,
                    'provider_property_id' => (string)$p['id'],
                ],
                [
                    'accommodation_id' => $acc->id,
                    'fa_name'          => $p['name'] ?? null,
                    'en_name'          => $p['name_en'] ?? null,
                    // updated_at/created_at handled by Eloquent
                ]
            );


            // facilities
            if (!empty($p['facilities'])) {
                $facilityIds = [];
                foreach ($p['facilities'] as $f) {
                    $group = $facilityGroupRepo->firstOrCreate(
                        ['fa_name' => $f['group_name'] ?? 'سایر'],
                        ['en_name' => $f['group_name_en'] ?? null]
                    );

                    $facility = $facilityRepo->updateOrCreate(
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
}
