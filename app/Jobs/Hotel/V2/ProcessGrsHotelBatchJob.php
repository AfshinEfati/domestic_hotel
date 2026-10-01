<?php

namespace App\Jobs\Hotel\V2;

use App\Models\Accommodation;
use App\Models\AccommodationProviderMap;
use App\Models\ProviderCityMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Mapping-only GRS catalog processor.
 *
 * Safety rule: this job must NEVER create or update Accommodation records.
 * It may only preserve/update provider-map metadata or create a missing,
 * unambiguous AccommodationProviderMap for an already existing hotel.
 */
class ProcessGrsHotelBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 70;

    public function __construct(
        public int $providerId,
        public array $properties,
    ) {
    }

    public function handle(): void
    {
        $errors = 0;
        $firstError = null;

        foreach ($this->properties as $property) {
            if (!is_array($property)) {
                continue;
            }

            $propertyId = trim((string) ($property['id'] ?? ''));
            if ($propertyId === '') {
                continue;
            }

            try {
                DB::transaction(
                    fn (): string => $this->processProperty($property, $propertyId),
                    3
                );
            } catch (Throwable $e) {
                $errors++;
                $firstError ??= [
                    'property_id' => $propertyId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($errors > 0) {
            $propertyId = (string) ($firstError['property_id'] ?? 'unknown');
            $message = trim((string) ($firstError['message'] ?? 'Unknown mapping error.'));

            throw new RuntimeException(
                "GRS mapping-only batch finished with {$errors} error(s). "
                ."First failure for property {$propertyId}: {$message}"
            );
        }
    }

    private function processProperty(array $property, string $propertyId): string
    {
        $faName = trim((string) ($property['name'] ?? ''));
        if ($faName === '') {
            return 'skipped';
        }

        $enName = $this->nullableString($property['name_en'] ?? null);

        // Existing mappings are read-only in catalog recovery. This workflow
        // only fills missing maps and never rewrites already curated data.
        $existingPropertyMap = AccommodationProviderMap::query()
            ->where('provider_id', $this->providerId)
            ->where('provider_property_id', $propertyId)
            ->first();

        if ($existingPropertyMap !== null) {
            if (!Accommodation::query()->whereKey($existingPropertyMap->accommodation_id)->exists()) {
                throw new RuntimeException(
                    'Existing GRS map points to a missing accommodation.'
                );
            }

            return 'mapped';
        }

        $cityId = ProviderCityMap::query()
            ->where('provider_id', $this->providerId)
            ->where('provider_city_id', (string) ($property['city_id'] ?? ''))
            ->value('city_id');

        if (!$cityId) {
            return 'skipped';
        }

        $matched = $this->findUnambiguousMatch(
            (int) $cityId,
            $property,
            $faName,
            $enName
        );

        // Catalog sync is update/map-only. A provider hotel that cannot be
        // matched safely to an existing local hotel is intentionally ignored.
        if ($matched === null) {
            return 'skipped';
        }

        // Serialize competing mappings for the same local hotel. This prevents
        // parallel catalog batches from binding two GRS properties to one
        // Accommodation even though the database has no such unique key.
        $hotel = Accommodation::query()
            ->whereKey((int) $matched->id)
            ->lockForUpdate()
            ->first();

        if ($hotel === null) {
            return 'skipped';
        }

        // Another batch may have created the exact property map while this job
        // was waiting for the accommodation lock.
        $propertyMap = AccommodationProviderMap::query()
            ->where('provider_id', $this->providerId)
            ->where('provider_property_id', $propertyId)
            ->first();

        if ($propertyMap !== null) {
            if ((int) $propertyMap->accommodation_id !== (int) $hotel->id) {
                throw new RuntimeException(
                    'GRS property was concurrently mapped to a different accommodation.'
                );
            }

            return 'mapped';
        }

        $conflictingHotelMap = AccommodationProviderMap::query()
            ->where('provider_id', $this->providerId)
            ->where('accommodation_id', (int) $hotel->id)
            ->where('provider_property_id', '!=', $propertyId)
            ->first();

        if ($conflictingHotelMap !== null) {
            throw new RuntimeException(
                'Accommodation already maps to another GRS property; no automatic change was made.'
            );
        }

        AccommodationProviderMap::query()->create([
            'provider_id' => $this->providerId,
            'provider_property_id' => $propertyId,
            'accommodation_id' => (int) $hotel->id,
            'fa_name' => $faName,
            'en_name' => $enName,
        ]);

        return 'mapped';
    }

    private function findUnambiguousMatch(
        int $cityId,
        array $property,
        string $faName,
        ?string $enName,
    ): ?Accommodation {
        $candidates = Accommodation::query()
            ->where('city_id', $cityId)
            ->get([
                'id',
                'city_id',
                'fa_name',
                'en_name',
                'address',
                'lat',
                'lng',
                'star',
            ]);

        $faKey = $this->normalize($faName, true);
        if ($faKey !== '') {
            $matches = $candidates
                ->filter(
                    fn (Accommodation $hotel): bool =>
                        $this->normalize($hotel->fa_name, true) === $faKey
                )
                ->values();

            if ($matches->count() === 1) {
                return $matches->first();
            }

            if ($matches->count() > 1) {
                return $this->refineDuplicateNameMatches(
                    $matches,
                    $property,
                    $enName
                );
            }
        }

        if ($enName !== null) {
            $enKey = $this->normalize($enName, true);

            if ($enKey !== '') {
                $matches = $candidates
                    ->filter(
                        fn (Accommodation $hotel): bool =>
                            $hotel->en_name !== null
                            && $this->normalize($hotel->en_name, true) === $enKey
                    )
                    ->values();

                if ($matches->count() === 1) {
                    return $matches->first();
                }

                if ($matches->count() > 1) {
                    return $this->refineByLocation($matches, $property);
                }
            }
        }

        return $this->refineByLocation($candidates, $property);
    }

    private function refineDuplicateNameMatches(
        $matches,
        array $property,
        ?string $providerEnName,
    ): ?Accommodation {
        if ($providerEnName !== null) {
            $enKey = $this->normalize($providerEnName, true);

            if ($enKey !== '') {
                $englishMatches = $matches
                    ->filter(
                        fn (Accommodation $hotel): bool =>
                            $hotel->en_name !== null
                            && $this->normalize($hotel->en_name, true) === $enKey
                    )
                    ->values();

                if ($englishMatches->count() === 1) {
                    return $englishMatches->first();
                }

                if ($englishMatches->count() > 1) {
                    $matches = $englishMatches;
                }
            }
        }

        return $this->refineByLocation($matches, $property);
    }

    private function refineByLocation($candidates, array $property): ?Accommodation
    {
        $address = $this->normalize($property['address'] ?? null);
        $lat = $this->coordinate($property['latitude'] ?? null, -90, 90);
        $lng = $this->coordinate($property['longitude'] ?? null, -180, 180);

        if ($address === '' || $lat === null || $lng === null) {
            return null;
        }

        $matches = $candidates
            ->filter(function (Accommodation $hotel) use (
                $property,
                $address,
                $lat,
                $lng
            ): bool {
                if ($hotel->lat === null || $hotel->lng === null) {
                    return false;
                }

                if (abs((float) $hotel->lat - $lat) > 0.00015) {
                    return false;
                }

                if (abs((float) $hotel->lng - $lng) > 0.00015) {
                    return false;
                }

                if ($this->normalize($hotel->address) !== $address) {
                    return false;
                }

                return !is_numeric($property['star'] ?? null)
                    || $hotel->star === null
                    || (int) $hotel->star === (int) $property['star'];
            })
            ->values();

        return $matches->count() === 1
            ? $matches->first()
            : null;
    }

    private function coordinate(
        mixed $value,
        float $minimum,
        float $maximum,
    ): ?float {
        if (!is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        return is_finite($coordinate)
            && $coordinate >= $minimum
            && $coordinate <= $maximum
                ? $coordinate
                : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value)
            ? trim((string) $value)
            : '';

        return $value === ''
            ? null
            : $value;
    }

    private function normalize(
        mixed $value,
        bool $stripHotelPrefix = false,
    ): string {
        $value = mb_strtolower(
            $this->nullableString($value) ?? '',
            'UTF-8'
        );

        $value = str_replace(
            ['ي', 'ى', 'ك', '‌', 'ـ'],
            ['ی', 'ی', 'ک', ' ', ''],
            $value
        );

        $value = trim(
            preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value
        );

        if ($stripHotelPrefix) {
            $value = trim(
                preg_replace('/^(?:هتل|hotel)\s+/u', '', $value)
                    ?? $value
            );
        }

        return $value;
    }
}
