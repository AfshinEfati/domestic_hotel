<?php

namespace App\Domain\Hotel\Services;

use App\Models\Accommodation;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Models\ProviderCityMap;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GrsAccommodationMapRepairService
{
    /**
     * @param array<int, int> $accommodationIds
     * @return array<int, int>
     */
    public function missingAccommodationIds(int $providerId, array $accommodationIds): array
    {
        $ids = collect($accommodationIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $mapped = AccommodationProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereIn('accommodation_id', $ids->all())
            ->whereNotNull('provider_property_id')
            ->where('provider_property_id', '!=', '')
            ->pluck('accommodation_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $ids->diff($mapped)->values()->all();
    }

    /**
     * @param array<int, int> $accommodationIds
     * @param array<int, mixed> $catalog
     * @return array{
     *     repaired: array<int, string>,
     *     unresolved: array<int, array{hotel:string, reason:string}>
     * }
     */
    public function repair(
        Provider $provider,
        array $accommodationIds,
        array $catalog,
    ): array {
        $ids = collect($accommodationIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return ['repaired' => [], 'unresolved' => []];
        }

        $hotels = Accommodation::query()
            ->whereIn('id', $ids->all())
            ->get([
                'id',
                'city_id',
                'fa_name',
                'en_name',
                'address',
                'lat',
                'lng',
                'star',
            ])
            ->keyBy('id');

        $providerCityIds = ProviderCityMap::query()
            ->where('provider_id', (int) $provider->id)
            ->whereIn('city_id', $hotels->pluck('city_id')->filter()->unique()->all())
            ->pluck('provider_city_id', 'city_id');

        $catalogByCity = collect($catalog)
            ->filter(fn ($property) =>
                is_array($property)
                && trim((string) ($property['id'] ?? '')) !== ''
                && trim((string) ($property['city_id'] ?? '')) !== ''
                && (
                    !array_key_exists('country_id', $property)
                    || (string) ($property['country_id'] ?? '') === '222'
                )
            )
            ->groupBy(fn (array $property) => (string) ($property['city_id'] ?? ''));

        $repaired = [];
        $unresolved = [];

        foreach ($ids as $accommodationId) {
            /** @var Accommodation|null $hotel */
            $hotel = $hotels->get($accommodationId);

            if ($hotel === null) {
                $unresolved[$accommodationId] = [
                    'hotel' => 'هتل #'.$accommodationId,
                    'reason' => 'هتل داخلی پیدا نشد.',
                ];
                continue;
            }

            $hotelName = trim((string) $hotel->fa_name) ?: 'هتل #'.$accommodationId;
            $providerCityId = trim((string) ($providerCityIds[(int) $hotel->city_id] ?? ''));

            if ($providerCityId === '') {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'مپ شهر داخلی به شهر GRS وجود ندارد.',
                ];
                continue;
            }

            /** @var Collection<int, array<string, mixed>> $candidates */
            $candidates = collect($catalogByCity->get($providerCityId, collect()))->values();

            if ($candidates->isEmpty()) {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'در catalog GRS برای شهر این هتل property پیدا نشد.',
                ];
                continue;
            }

            [$property, $reason] = $this->matchProperty($hotel, $candidates);

            if ($property === null) {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => $reason,
                ];
                continue;
            }

            $propertyId = trim((string) ($property['id'] ?? ''));
            if ($propertyId === '') {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'property انتخاب‌شده شناسه معتبر ندارد.',
                ];
                continue;
            }

            $conflictingPropertyMap = AccommodationProviderMap::query()
                ->where('provider_id', (int) $provider->id)
                ->where('provider_property_id', $propertyId)
                ->where('accommodation_id', '!=', $accommodationId)
                ->first();

            if ($conflictingPropertyMap !== null) {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'این property GRS قبلاً به هتل داخلی دیگری مپ شده است.',
                ];
                continue;
            }

            $localMaps = AccommodationProviderMap::query()
                ->where('provider_id', (int) $provider->id)
                ->where('accommodation_id', $accommodationId)
                ->orderBy('id')
                ->get();

            $otherPropertyMap = $localMaps->first(fn (AccommodationProviderMap $map) =>
                trim((string) $map->provider_property_id) !== ''
                && (string) $map->provider_property_id !== $propertyId
            );

            if ($otherPropertyMap !== null) {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'هتل داخلی همزمان به property دیگری از GRS مپ شده است.',
                ];
                continue;
            }

            if ($localMaps->count() > 1) {
                $unresolved[$accommodationId] = [
                    'hotel' => $hotelName,
                    'reason' => 'برای این هتل چند رکورد مپ GRS وجود دارد و ترمیم خودکار امن نیست.',
                ];
                continue;
            }

            try {
                DB::transaction(function () use (
                    $provider,
                    $accommodationId,
                    $property,
                    $propertyId,
                    $localMaps
                ): void {
                    $attributes = [
                        'accommodation_id' => $accommodationId,
                        'provider_id' => (int) $provider->id,
                        'provider_property_id' => $propertyId,
                        'fa_name' => $this->nullableString($property['name'] ?? null),
                        'en_name' => $this->nullableString($property['name_en'] ?? null),
                        'is_disabled' => (bool) ($property['disabled'] ?? false),
                    ];

                    $existing = $localMaps->first();
                    if ($existing !== null) {
                        $existing->update($attributes);
                        return;
                    }

                    AccommodationProviderMap::query()->create($attributes);
                });
            } catch (QueryException) {
                $resolved = AccommodationProviderMap::query()
                    ->where('provider_id', (int) $provider->id)
                    ->where('accommodation_id', $accommodationId)
                    ->where('provider_property_id', $propertyId)
                    ->first();

                if ($resolved === null) {
                    $unresolved[$accommodationId] = [
                        'hotel' => $hotelName,
                        'reason' => 'ساخت مپ به علت تداخل دیتابیس انجام نشد.',
                    ];
                    continue;
                }
            }

            $repaired[$accommodationId] = $propertyId;
        }

        return [
            'repaired' => $repaired,
            'unresolved' => $unresolved,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $candidates
     * @return array{0:?array,1:string}
     */
    private function matchProperty(Accommodation $hotel, Collection $candidates): array
    {
        $faName = $this->normalize($hotel->fa_name, true);

        if ($faName !== '') {
            $matches = $candidates
                ->filter(fn (array $property) =>
                    $this->normalize($property['name'] ?? null, true) === $faName
                )
                ->values();

            if ($matches->count() === 1) {
                return [$matches->first(), ''];
            }

            if ($matches->count() > 1) {
                $refined = $this->refineMatches($hotel, $matches);
                if ($refined->count() === 1) {
                    return [$refined->first(), ''];
                }

                return [null, 'چند property با نام فارسی یکسان پیدا شد و نتیجه قطعی نیست.'];
            }
        }

        $enName = $this->normalize($hotel->en_name, true);

        if ($enName !== '') {
            $matches = $candidates
                ->filter(fn (array $property) =>
                    $this->normalize($property['name_en'] ?? null, true) === $enName
                )
                ->values();

            if ($matches->count() === 1) {
                return [$matches->first(), ''];
            }

            if ($matches->count() > 1) {
                $refined = $this->refineMatches($hotel, $matches);
                if ($refined->count() === 1) {
                    return [$refined->first(), ''];
                }

                return [null, 'چند property با نام انگلیسی یکسان پیدا شد و نتیجه قطعی نیست.'];
            }
        }

        $strong = $this->addressCoordinateMatches($hotel, $candidates);

        if ($strong->count() === 1) {
            return [$strong->first(), ''];
        }

        if ($strong->count() > 1) {
            return [null, 'چند property با آدرس و مختصات منطبق پیدا شد.'];
        }

        return [null, 'هیچ property با تطبیق قطعی نام یا آدرس/مختصات پیدا نشد.'];
    }

    /**
     * @param Collection<int, array<string, mixed>> $matches
     * @return Collection<int, array<string, mixed>>
     */
    private function refineMatches(Accommodation $hotel, Collection $matches): Collection
    {
        $enName = $this->normalize($hotel->en_name, true);

        if ($enName !== '') {
            $byEnglish = $matches
                ->filter(fn (array $property) =>
                    $this->normalize($property['name_en'] ?? null, true) === $enName
                )
                ->values();

            if ($byEnglish->count() === 1) {
                return $byEnglish;
            }

            if ($byEnglish->count() > 1) {
                $matches = $byEnglish;
            }
        }

        $byLocation = $this->addressCoordinateMatches($hotel, $matches);

        return $byLocation->isEmpty() ? $matches : $byLocation;
    }

    /**
     * @param Collection<int, array<string, mixed>> $candidates
     * @return Collection<int, array<string, mixed>>
     */
    private function addressCoordinateMatches(Accommodation $hotel, Collection $candidates): Collection
    {
        $address = $this->normalize($hotel->address);
        if (
            $address === ''
            || $hotel->lat === null
            || $hotel->lng === null
        ) {
            return collect();
        }

        return $candidates
            ->filter(function (array $property) use ($hotel, $address): bool {
                $lat = $this->coordinate($property['latitude'] ?? null, -90, 90);
                $lng = $this->coordinate($property['longitude'] ?? null, -180, 180);

                if ($lat === null || $lng === null) {
                    return false;
                }

                if (abs((float) $hotel->lat - $lat) > 0.00015) {
                    return false;
                }

                if (abs((float) $hotel->lng - $lng) > 0.00015) {
                    return false;
                }

                if ($this->normalize($property['address'] ?? null) !== $address) {
                    return false;
                }

                return !is_numeric($property['star'] ?? null)
                    || $hotel->star === null
                    || (int) $hotel->star === (int) $property['star'];
            })
            ->values();
    }

    private function coordinate(mixed $value, float $minimum, float $maximum): ?float
    {
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

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
