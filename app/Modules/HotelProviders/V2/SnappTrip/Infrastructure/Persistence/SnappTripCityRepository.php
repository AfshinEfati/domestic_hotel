<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\City;
use App\Models\Country;
use App\Models\Provider;
use App\Models\ProviderCityMap;
use Illuminate\Support\Collection;

final class SnappTripCityRepository
{
    /** @param Collection<int,array<string,mixed>> $cities */
    public function persistMappedDomesticCities(Provider $provider, Collection $cities): int
    {
        $iranId = Country::query()->where('iso2', 'IR')->value('id');
        if ($iranId === null) {
            return 0;
        }

        $domesticCities = City::query()
            ->with('state')
            ->where('country_id', (int) $iranId)
            ->get();

        $count = 0;
        foreach ($cities as $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $providerCityId = trim((string) ($payload['id'] ?? ''));
            $city = $this->resolveExistingDomesticCity($domesticCities, $payload);
            if ($providerCityId === '' || $city === null) {
                continue;
            }

            ProviderCityMap::query()->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'provider_city_id' => $providerCityId,
                ],
                [
                    'city_id' => $city->id,
                    'fa_name' => $payload['name'] ?? $city->fa_name,
                    'en_name' => $payload['name_en'] ?? null,
                ],
            );
            $count++;
        }

        return $count;
    }

    /**
     * @param Collection<int,array<string,mixed>> $cities
     * @return string[]
     */
    public function mappedProviderCityIds(Provider $provider, Collection $cities): array
    {
        $ids = $cities
            ->map(static fn (array $city): string => trim((string) ($city['id'] ?? '')))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return ProviderCityMap::query()
            ->where('provider_id', $provider->id)
            ->whereIn('provider_city_id', $ids)
            ->pluck('provider_city_id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    public function isMapped(Provider $provider, string $providerCityId): bool
    {
        $providerCityId = trim($providerCityId);
        if ($providerCityId === '') {
            return false;
        }

        return ProviderCityMap::query()
            ->where('provider_id', $provider->id)
            ->where('provider_city_id', $providerCityId)
            ->exists();
    }

    /**
     * SnappTrip /cities does not expose country information. For the domestic hotel
     * service we therefore only map provider cities onto already existing Iranian
     * canonical cities. Unknown cities are ignored instead of being created.
     *
     * @param Collection<int,City> $domesticCities
     * @param array<string,mixed> $payload
     */
    private function resolveExistingDomesticCity(Collection $domesticCities, array $payload): ?City
    {
        $cityKey = $this->normalize($payload['name'] ?? null);
        if ($cityKey === '') {
            return null;
        }

        $matches = $domesticCities->filter(
            fn (City $city): bool => $this->normalize($city->fa_name) === $cityKey,
        );

        $provinceKey = $this->normalize($payload['province_name'] ?? null);
        if ($provinceKey !== '') {
            $matches = $matches->filter(
                fn (City $city): bool => $city->state !== null
                    && $this->normalize($city->state->fa_name) === $provinceKey,
            );
        }

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalize(mixed $value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة', '‌', 'ـ'], ['ی', 'ی', 'ک', 'ه', 'ه', ' ', ''], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
