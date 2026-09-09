<?php

namespace App\Jobs\Hotel;

use App\Models\Accommodation;
use App\Models\Facility;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use App\Repositories\Contracts\ProviderCityMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessGrsHotelPropertyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DEFAULT_FACILITY_GROUP_ID = 11;

    /**
     * @param array<string, mixed> $property
     */
    public function __construct(
        public string $providerCode,
        public array $property
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        ProviderRepositoryInterface $providerRepo,
        ProviderCityMapRepositoryInterface $providerCityMapRepo,
        CityRepositoryInterface $cityRepo,
        AccommodationTypeRepositoryInterface $accTypeRepo,
        AccommodationRepositoryInterface $accRepo,
        AccommodationProviderMapRepositoryInterface $mapRepo,
        FacilityRepositoryInterface $facilityRepo
    ): void
    {
        $providerPropertyId = $this->normalizeString($this->property['id'] ?? null);
        $providerCityId = $this->normalizeString($this->property['city_id'] ?? null);

        try {
            $provider = $providerRepo->findDynamic(where: ['code' => $this->providerCode]);
            if (!$provider) {
                return;
            }

            if ($providerPropertyId === null) {
                return;
            }

            if ($providerCityId === null) {
                return;
            }

            $cityMap = $providerCityMapRepo->findDynamic(where: [
                'provider_id' => $provider->id,
                'provider_city_id' => $providerCityId,
            ]);
            if (!$cityMap) {
                return;
            }

            $city = $cityRepo->find($cityMap->city_id);
            if (!$city) {
                return;
            }

            $faName = $this->normalizeString($this->property['name'] ?? null);
            if ($faName === null) {
                return;
            }

            $typeName = $this->normalizeString($this->property['type'] ?? null) ?? 'hotel';
            $typeEnName = $this->normalizeString($this->property['type_en'] ?? null);

            $type = $accTypeRepo->findDynamic(where: ['fa_name' => $typeName]);
            if (!$type) {
                $type = $accTypeRepo->store([
                    'fa_name' => $typeName,
                    'en_name' => $typeEnName,
                ]);
            } elseif ($typeEnName !== null && $type->en_name === null) {
                $accTypeRepo->update($type->id, ['en_name' => $typeEnName]);
                $type->refresh();
            }

            $enName = $this->resolveEnglishName($this->property);

            /** @var Accommodation $acc */
            $acc = $accRepo->updateOrCreate(
                [
                    'city_id' => $city->id,
                    'fa_name' => $faName,
                ],
                [
                    'en_name' => $enName,
                    'accommodation_type_id' => $type->id,
                    'star' => $this->resolveStar($this->property['star'] ?? null),
                    'grade' => $this->normalizeString($this->property['grade'] ?? null),
                    'address' => $this->normalizeString($this->property['address'] ?? null),
                    'lat' => $this->normalizeCoordinate($this->property['latitude'] ?? null, 90),
                    'lng' => $this->normalizeCoordinate($this->property['longitude'] ?? null, 180),
                    'is_active' => true,
                ]
            );

            $mapRepo->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'provider_property_id' => $providerPropertyId,
                ],
                [
                    'accommodation_id' => $acc->id,
                    'fa_name' => $faName,
                    'en_name' => $enName,
                ]
            );

            $this->syncFacilities(
                $acc,
                $this->property['facilities'] ?? null,
                $facilityRepo
            );
        } catch (Throwable $e) {
            Log::error('GRS property processing failed', [
                'provider_code' => $this->providerCode,
                'property_id' => $providerPropertyId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>>|null $facilities
     */
    private function syncFacilities(
        Accommodation $acc,
        ?array $facilities,
        FacilityRepositoryInterface $facilityRepo
    ): void {
        if (empty($facilities)) {
            return;
        }

        $facilityIds = [];

        foreach ($facilities as $facility) {
            if (!is_array($facility)) {
                continue;
            }

            $facilityName = $this->normalizeString($facility['name'] ?? null);
            if ($facilityName === null) {
                continue;
            }

            /** @var Facility|null $facilityModel */
            $facilityModel = $facilityRepo->findDynamic(
                where: ['fa_name' => $facilityName],
                with: ['group']
            );

            if (!$facilityModel) {
                $facilityModel = $facilityRepo->store([
                    'fa_name' => $facilityName,
                    'en_name' => $this->normalizeString($facility['name_en'] ?? null),
                    'facility_group_id' => self::DEFAULT_FACILITY_GROUP_ID,
                ]);

                $facilityModel->loadMissing('group');
            } else {
                $facilityModel->loadMissing('group');
            }

            $facilityIds[$facilityModel->id] = [
                'description' => (string)($facility['description'] ?? ''),
            ];
        }

        if ($facilityIds !== []) {
            $acc->facilities()->sync($facilityIds);
        } else {
            Log::warning('GRS facilities skipped: no valid facilities', [
                'accommodation_id' => $acc->id,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $property
     */
    private function resolveEnglishName(array $property): ?string
    {
        $enName = $this->normalizeString($property['name_en'] ?? null);
        if ($enName !== null) {
            return $enName;
        }

        $images = $property['images'] ?? null;
        if (!is_array($images)) {
            return null;
        }

        return $this->extractNameFromImages($images);
    }

    /**
     * @param array<int, array<string, mixed>> $images
     */
    private function extractNameFromImages(array $images): ?string
    {
        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }

            $rawName = $this->normalizeString($image['name'] ?? null)
                ?? $this->extractFilename($image['url'] ?? null);

            $normalized = $this->normalizeImageBaseName($rawName);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeImageBaseName(?string $value): ?string
    {
        $value = $this->normalizeString($value);
        if ($value === null) {
            return null;
        }

        $baseName = pathinfo($value, PATHINFO_FILENAME);
        $baseName = preg_replace('/[-_]?\\d+$/', '', $baseName) ?? $baseName;
        $baseName = str_replace(['-', '_'], ' ', $baseName);
        $baseName = preg_replace('/\\s+/', ' ', $baseName) ?? $baseName;
        $baseName = trim($baseName);

        return $baseName !== '' ? $baseName : null;
    }

    private function extractFilename(?string $value): ?string
    {
        $value = $this->normalizeString($value);
        if ($value === null) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        $candidate = is_string($path) && $path !== '' ? $path : $value;

        return $this->normalizeString(basename($candidate));
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $stringValue = trim((string)$value);

        return $stringValue !== '' ? $stringValue : null;
    }

    private function resolveStar(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        return 0;
    }

    private function normalizeCoordinate(mixed $value, int $maxAbs): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $floatValue = (float)$value;
        if (abs($floatValue) > $maxAbs) {
            return null;
        }

        return $floatValue;
    }
}
