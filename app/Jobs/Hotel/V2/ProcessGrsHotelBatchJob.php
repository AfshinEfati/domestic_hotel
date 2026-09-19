<?php

namespace App\Jobs\Hotel\V2;

use App\Models\Accommodation;
use App\Models\AccommodationProviderMap;
use App\Models\AccommodationType;
use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\ProviderCityMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Only local database work. This job makes ZERO provider HTTP requests. */
class ProcessGrsHotelBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 70;

    public function __construct(public int $providerId, public array $properties)
    {
    }

    public function handle(): void
    {
        $facilityLookup = $this->loadFacilities();
        $errors = 0;
        $mapped = 0;
        $created = 0;
        $skipped = 0;

        foreach ($this->properties as $property) {
            if (!is_array($property)) {
                $skipped++;
                continue;
            }
            $propertyId = trim((string) ($property['id'] ?? ''));
            if ($propertyId === '') {
                $skipped++;
                continue;
            }

            try {
                $result = DB::transaction(fn () => $this->processProperty($property, $propertyId, $facilityLookup));
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'mapped') {
                    $mapped++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $e) {
                $errors++;
                Log::error('GRS hotel mapping failed', [
                    'property_id' => $propertyId,
                    'provider_id' => $this->providerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('GRS hotel mapping batch completed', compact('mapped', 'created', 'skipped', 'errors'));
        if ($errors > 0) {
            throw new RuntimeException("GRS hotel mapping batch finished with {$errors} errors; see application logs.");
        }
    }

    private function processProperty(array $property, string $propertyId, array $facilityLookup): string
    {
        $faName = trim((string) ($property['name'] ?? ''));
        if ($faName === '') {
            throw new RuntimeException('Property has no Persian name.');
        }
        $enName = $this->nullableString($property['name_en'] ?? null);

        // An existing provider/property mapping is authoritative even when its name changes.
        $map = AccommodationProviderMap::query()
            ->where('provider_id', $this->providerId)
            ->where('provider_property_id', $propertyId)
            ->first();

        $new = false;
        if ($map) {
            $hotel = Accommodation::query()->find($map->accommodation_id);
            if (!$hotel) {
                throw new RuntimeException('Existing GRS map points to a missing accommodation.');
            }
        } else {
            $cityId = ProviderCityMap::query()
                ->where('provider_id', $this->providerId)
                ->where('provider_city_id', (string) ($property['city_id'] ?? ''))
                ->value('city_id');

            if (!$cityId) {
                Log::warning('GRS hotel skipped: provider city is not mapped; run provider:city grs separately', [
                    'property_id' => $propertyId,
                    'provider_city_id' => $property['city_id'] ?? null,
                ]);
                return 'skipped';
            }

            $hotel = $this->findUnambiguousMatch((int) $cityId, $property, $faName, $enName);
            if (!$hotel) {
                $type = $this->resolveType($property);
                $hotel = Accommodation::query()->create([
                    'city_id' => $cityId,
                    'fa_name' => $faName,
                    'en_name' => $enName,
                    'accommodation_type_id' => $type->id,
                    'star' => is_numeric($property['star'] ?? null) ? (int) $property['star'] : null,
                    'grade' => $this->nullableString($property['grade'] ?? null),
                    'address' => $this->nullableString($property['address'] ?? null),
                    'lat' => $this->coordinate($property['latitude'] ?? null, -90, 90),
                    'lng' => $this->coordinate($property['longitude'] ?? null, -180, 180),
                    'is_active' => true,
                ]);
                $new = true;
            }

            // Never silently bind another GRS listing to an already-linked local hotel.
            if (AccommodationProviderMap::query()
                ->where('provider_id', $this->providerId)
                ->where('accommodation_id', $hotel->id)
                ->where('provider_property_id', '!=', $propertyId)
                ->exists()) {
                throw new RuntimeException('Accommodation already maps to another GRS property; manual review required.');
            }

            $map = AccommodationProviderMap::query()->firstOrCreate(
                ['provider_id' => $this->providerId, 'provider_property_id' => $propertyId],
                ['accommodation_id' => $hotel->id, 'fa_name' => $faName, 'en_name' => $enName]
            );
        }

        // Provider metadata belongs to the map; do not overwrite the canonical local hotel.
        $map->update(['fa_name' => $faName, 'en_name' => $enName]);
        $this->attachFacilities($hotel, $property['facilities'] ?? [], $facilityLookup);

        return $new ? 'created' : 'mapped';
    }

    private function findUnambiguousMatch(int $cityId, array $property, string $faName, ?string $enName): ?Accommodation
    {
        $candidates = Accommodation::query()->where('city_id', $cityId)->get();
        $faKey = $this->normalize($faName, true);
        $matches = $candidates->filter(fn (Accommodation $hotel) => $this->normalize($hotel->fa_name, true) === $faKey);
        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() > 1) {
            throw new RuntimeException('Ambiguous Persian hotel name; manual review required.');
        }

        if ($enName !== null) {
            $enKey = $this->normalize($enName, true);
            $matches = $candidates->filter(fn (Accommodation $hotel) =>
                $hotel->en_name !== null && $this->normalize($hotel->en_name, true) === $enKey
            );
            if ($matches->count() === 1) {
                return $matches->first();
            }
            if ($matches->count() > 1) {
                throw new RuntimeException('Ambiguous English hotel name; manual review required.');
            }
        }

        // Different name: match only on the same normalized address AND close valid coordinates.
        $address = $this->normalize($property['address'] ?? null);
        $lat = $this->coordinate($property['latitude'] ?? null, -90, 90);
        $lng = $this->coordinate($property['longitude'] ?? null, -180, 180);
        if ($address === '' || $lat === null || $lng === null) {
            return null;
        }
        $matches = $candidates->filter(fn (Accommodation $hotel) =>
            $hotel->lat !== null && $hotel->lng !== null &&
            abs((float) $hotel->lat - $lat) <= 0.00015 &&
            abs((float) $hotel->lng - $lng) <= 0.00015 &&
            $this->normalize($hotel->address) === $address &&
            (!is_numeric($property['star'] ?? null) || $hotel->star === null ||
                (int) $hotel->star === (int) $property['star'])
        );
        if ($matches->count() > 1) {
            throw new RuntimeException('Ambiguous address/coordinate hotel match; manual review required.');
        }

        return $matches->first();
    }

    private function resolveType(array $property): AccommodationType
    {
        $typeCode = trim((string) ($property['type'] ?? 'hotel')) ?: 'hotel';
        return AccommodationType::query()->where('en_name', $typeCode)->first()
            ?? AccommodationType::query()->firstOrCreate(
                ['fa_name' => $typeCode], ['en_name' => $typeCode]
            );
    }

    private function loadFacilities(): array
    {
        $groupNames = [];
        $facilityNames = [];
        foreach ($this->properties as $property) {
            foreach (is_array($property['facilities'] ?? null) ? $property['facilities'] : [] as $facility) {
                if (!is_array($facility)) {
                    continue;
                }
                $groupNames[] = trim((string) ($facility['group_name'] ?? 'سایر')) ?: 'سایر';
                $facilityNames[] = trim((string) ($facility['name'] ?? ''));
            }
        }
        if ($facilityNames === []) {
            return [];
        }
        $groups = FacilityGroup::query()->whereIn('fa_name', array_unique($groupNames))->get();
        $groupNameById = $groups->pluck('fa_name', 'id')->all();
        $result = [];
        foreach (Facility::query()->whereIn('facility_group_id', $groups->pluck('id'))
            ->whereIn('fa_name', array_unique($facilityNames))->get() as $facility) {
            $groupName = $groupNameById[$facility->facility_group_id] ?? null;
            if ($groupName !== null) {
                $result[$groupName."\0".$facility->fa_name] = $facility->id;
            }
        }
        return $result;
    }

    private function attachFacilities(Accommodation $hotel, mixed $facilities, array $lookup): void
    {
        if (!is_array($facilities)) {
            return;
        }
        $ids = [];
        foreach ($facilities as $facility) {
            if (!is_array($facility)) {
                continue;
            }
            $group = trim((string) ($facility['group_name'] ?? 'سایر')) ?: 'سایر';
            $name = trim((string) ($facility['name'] ?? ''));
            $id = $lookup[$group."\0".$name] ?? null;
            if ($id !== null) {
                $description = $this->nullableString($facility['description'] ?? null);
                $ids[$id] = $description === null ? [] : ['description' => mb_substr($description, 0, 500)];
            }
        }
        if ($ids !== []) {
            // Do not detach facilities from another provider or manually entered data.
            $hotel->facilities()->syncWithoutDetaching($ids);
        }
    }

    private function coordinate(mixed $value, float $minimum, float $maximum): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $coordinate = (float) $value;
        return is_finite($coordinate) && $coordinate >= $minimum && $coordinate <= $maximum
            ? $coordinate
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value === '' ? null : $value;
    }

    private function normalize(mixed $value, bool $stripHotelPrefix = false): string
    {
        $value = mb_strtolower($this->nullableString($value) ?? '', 'UTF-8');
        $value = str_replace(['ي', 'ى', 'ك', '‌', 'ـ'], ['ی', 'ی', 'ک', ' ', ''], $value);
        $value = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value);
        if ($stripHotelPrefix) {
            $value = trim(preg_replace('/^(?:هتل|hotel)\s+/u', '', $value) ?? $value);
        }
        return $value;
    }
}
