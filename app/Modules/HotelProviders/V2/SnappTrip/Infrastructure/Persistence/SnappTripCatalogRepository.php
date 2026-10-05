<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Services\AccommodationTypeResolver;
use App\Models\Accommodation;
use App\Models\AccommodationProviderDetail;
use App\Models\AccommodationProviderMap;
use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\HotelChildPolicy;
use App\Models\Provider;
use App\Models\ProviderCityMap;
use App\Models\RatePlan;
use App\Models\RatePlanProviderMap;
use App\Models\RoomType;
use App\Models\RoomTypeName;
use App\Models\RoomTypeProviderMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SnappTripCatalogRepository
{
    public function __construct(
        private readonly CityRepository $cities,
        private readonly AccommodationTypeResolver $types,
    ) {
    }

    /** @param Collection<int,array<string,mixed>> $cities */
    public function persistCities(Provider $provider, Collection $cities): int
    {
        $count = 0;
        foreach ($cities as $payload) {
            $this->cities->upsertFromProvider($payload, $provider);
            $count++;
        }

        return $count;
    }

    /** @return Collection<int,AccommodationProviderMap> */
    public function mappedHotels(Provider $provider): Collection
    {
        return AccommodationProviderMap::query()
            ->where('provider_id', $provider->id)
            ->whereNotNull('provider_property_id')
            ->orderBy('id')
            ->get();
    }

    /** @param array<string,mixed> $hotel */
    public function persistHotelBundle(
        Provider $provider,
        array $hotel,
        array $facilities = [],
        array $rooms = [],
        ?string $providerUrl = null,
    ): AccommodationProviderMap {
        return DB::transaction(function () use (
            $provider,
            $hotel,
            $facilities,
            $rooms,
            $providerUrl,
        ): AccommodationProviderMap {
            $map = $this->resolveAccommodationMap($provider, $hotel);
            $map->update([
                'fa_name' => $hotel['fa_name'],
                'en_name' => $hotel['en_name'] ?? null,
                'is_disabled' => ($hotel['enabled'] ?? true) !== true,
            ]);

            $this->persistProviderDetails($map, $hotel, $providerUrl);
            $this->persistChildPolicy((int) $map->accommodation_id, $hotel['policies'] ?? []);
            $this->persistFacilities($map, $facilities !== [] ? $facilities : ($hotel['facilities'] ?? []));
            $this->persistRooms($provider, $map, $rooms);

            return $map->fresh(['accommodation']) ?? $map;
        });
    }

    /** @return array{room_map:RoomTypeProviderMap,rate_plan_map:RatePlanProviderMap} */
    public function ensureOfferMaps(
        Provider $provider,
        AccommodationProviderMap $map,
        string $providerRoomId,
        bool $foreigner,
    ): array {
        $roomMap = RoomTypeProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_provider_map_id', $map->id)
            ->where('provider_room_type_id', $providerRoomId)
            ->first();

        if ($roomMap === null) {
            throw new RuntimeException("SnappTrip room mapping {$providerRoomId} is missing for hotel {$map->provider_property_id}.");
        }

        $domesticMap = RatePlanProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_provider_map_id', $map->id)
            ->where('provider_rate_plan_id', $this->providerRatePlanId($providerRoomId, false))
            ->first();

        if ($domesticMap === null) {
            throw new RuntimeException("SnappTrip domestic rate-plan mapping is missing for room {$providerRoomId}.");
        }

        if (!$foreigner) {
            return ['room_map' => $roomMap, 'rate_plan_map' => $domesticMap];
        }

        $domesticPlan = RatePlan::query()->find($domesticMap->rate_plan_id);
        if ($domesticPlan === null || (int) $domesticPlan->accommodation_id !== (int) $map->accommodation_id) {
            throw new RuntimeException("SnappTrip domestic rate-plan mapping for room {$providerRoomId} is invalid.");
        }

        $ratePlanMap = $this->ensureRatePlan(
            $provider,
            $map,
            $providerRoomId,
            $this->boardTypeFromRatePlan($domesticPlan),
            true,
        );

        return ['room_map' => $roomMap, 'rate_plan_map' => $ratePlanMap];
    }

    private function resolveAccommodationMap(Provider $provider, array $hotel): AccommodationProviderMap
    {
        $providerPropertyId = trim((string) ($hotel['provider_property_id'] ?? ''));
        $faName = trim((string) ($hotel['fa_name'] ?? ''));

        if ($providerPropertyId === '' || $faName === '') {
            throw new RuntimeException('SnappTrip hotel requires provider_property_id and fa_name.');
        }

        $existing = AccommodationProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('provider_property_id', $providerPropertyId)
            ->first();

        if ($existing !== null) {
            if (!Accommodation::query()->whereKey($existing->accommodation_id)->exists()) {
                throw new RuntimeException('Existing SnappTrip mapping points to a missing accommodation.');
            }

            return $existing;
        }

        $providerCityId = trim((string) ($hotel['provider_city_id'] ?? ''));
        $cityId = $providerCityId === '' ? null : ProviderCityMap::query()
            ->where('provider_id', $provider->id)
            ->where('provider_city_id', $providerCityId)
            ->value('city_id');

        if ($cityId === null) {
            throw new RuntimeException("SnappTrip city mapping is missing for hotel {$providerPropertyId}.");
        }

        $accommodation = $this->findUnambiguousAccommodation((int) $cityId, $hotel);
        if ($accommodation === null) {
            $accommodation = Accommodation::query()->create([
                'city_id' => (int) $cityId,
                'fa_name' => $faName,
                'en_name' => $hotel['en_name'] ?? null,
                'accommodation_type_id' => $this->types->resolveId($hotel['accommodation_type'] ?? null),
                'star' => is_numeric($hotel['star'] ?? null) ? (int) $hotel['star'] : null,
                'grade' => null,
                'address' => $hotel['address'] ?? null,
                'lat' => $this->coordinate($hotel['lat'] ?? null, -90, 90),
                'lng' => $this->coordinate($hotel['lng'] ?? null, -180, 180),
                'is_active' => true,
            ]);
        }

        if (AccommodationProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_id', $accommodation->id)
            ->where('provider_property_id', '!=', $providerPropertyId)
            ->exists()) {
            throw new RuntimeException('Accommodation already maps to another SnappTrip hotel; manual review required.');
        }

        return AccommodationProviderMap::query()->create([
            'accommodation_id' => $accommodation->id,
            'provider_id' => $provider->id,
            'provider_property_id' => $providerPropertyId,
            'is_disabled' => ($hotel['enabled'] ?? true) !== true,
            'fa_name' => $faName,
            'en_name' => $hotel['en_name'] ?? null,
        ]);
    }

    private function findUnambiguousAccommodation(int $cityId, array $hotel): ?Accommodation
    {
        $candidates = Accommodation::query()->where('city_id', $cityId)->get();
        $faKey = $this->normalize($hotel['fa_name'] ?? null, true);
        $matches = $candidates->filter(fn (Accommodation $item): bool => $this->normalize($item->fa_name, true) === $faKey);

        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() > 1) {
            throw new RuntimeException('Ambiguous Persian hotel name while mapping SnappTrip hotel.');
        }

        $enName = $this->nullableString($hotel['en_name'] ?? null);
        if ($enName !== null) {
            $enKey = $this->normalize($enName, true);
            $matches = $candidates->filter(
                fn (Accommodation $item): bool => $item->en_name !== null
                    && $this->normalize($item->en_name, true) === $enKey,
            );
            if ($matches->count() === 1) {
                return $matches->first();
            }
            if ($matches->count() > 1) {
                throw new RuntimeException('Ambiguous English hotel name while mapping SnappTrip hotel.');
            }
        }

        $address = $this->normalize($hotel['address'] ?? null);
        $lat = $this->coordinate($hotel['lat'] ?? null, -90, 90);
        $lng = $this->coordinate($hotel['lng'] ?? null, -180, 180);
        if ($address === '' || $lat === null || $lng === null) {
            return null;
        }

        $matches = $candidates->filter(fn (Accommodation $item): bool =>
            $item->lat !== null
            && $item->lng !== null
            && abs((float) $item->lat - $lat) <= 0.00015
            && abs((float) $item->lng - $lng) <= 0.00015
            && $this->normalize($item->address) === $address
            && (!is_numeric($hotel['star'] ?? null) || $item->star === null || (int) $item->star === (int) $hotel['star'])
        );

        if ($matches->count() > 1) {
            throw new RuntimeException('Ambiguous address/coordinate hotel match while mapping SnappTrip hotel.');
        }

        return $matches->first();
    }

    private function persistProviderDetails(AccommodationProviderMap $map, array $hotel, ?string $providerUrl): void
    {
        $policies = is_array($hotel['policies'] ?? null) ? $hotel['policies'] : [];

        AccommodationProviderDetail::query()->updateOrCreate(
            ['accommodation_provider_map_id' => $map->id],
            [
                'accommodation_title' => $hotel['accommodation_title'] ?? null,
                'description' => $hotel['description'] ?? null,
                'provider_url' => $providerUrl,
                'is_marketplace' => $hotel['is_marketplace'] ?? null,
                'check_in_time' => $policies['check_in_time'] ?? null,
                'check_out_time' => $policies['check_out_time'] ?? null,
                'cancellation_policy' => $policies['cancellation_policy'] ?? null,
                'foreigners_fee' => $policies['foreigners_fee'] ?? null,
                'free_transfer_policy' => $policies['free_transfer_policy'] ?? null,
                'free_transfers' => $policies['free_transfers'] ?? null,
            ],
        );
    }

    private function persistChildPolicy(int $accommodationId, mixed $policies): void
    {
        if (!is_array($policies)) {
            return;
        }

        $infant = $this->nullableUnsignedTinyInt($policies['max_infant_age'] ?? null);
        $child = $this->nullableUnsignedTinyInt($policies['max_child_age'] ?? null);
        if ($infant === null && $child === null) {
            return;
        }

        $payload = ['status' => true];
        if ($infant !== null) {
            $payload['max_infant_age'] = $infant;
        }
        if ($child !== null) {
            $payload['max_child_age'] = $child;
        }

        HotelChildPolicy::query()->updateOrCreate(['accommodation_id' => $accommodationId], $payload);
    }

    private function persistFacilities(AccommodationProviderMap $map, array $facilities): void
    {
        if ($facilities === []) {
            return;
        }

        $group = FacilityGroup::query()->firstOrCreate(['fa_name' => 'سایر']);
        $ids = [];

        foreach ($facilities as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = $this->nullableString($row['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $facility = Facility::query()->firstOrCreate(
                ['facility_group_id' => $group->id, 'fa_name' => $name],
                ['en_name' => null],
            );
            $ids[$facility->id] = [];
        }

        if ($ids !== []) {
            $map->accommodation?->facilities()->syncWithoutDetaching($ids);
        }
    }

    private function persistRooms(Provider $provider, AccommodationProviderMap $map, array $rooms): void
    {
        foreach ($rooms as $row) {
            if (!is_array($row)) {
                continue;
            }
            $providerRoomId = trim((string) ($row['provider_room_type_id'] ?? ''));
            $name = $this->nullableString($row['fa_name'] ?? null);
            if ($providerRoomId === '' || $name === null) {
                continue;
            }

            $nameRecord = RoomTypeName::query()->firstOrCreate(['fa_name' => $name]);
            $roomMap = RoomTypeProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('accommodation_provider_map_id', $map->id)
                ->where('provider_room_type_id', $providerRoomId)
                ->first();

            if ($roomMap !== null) {
                $room = RoomType::query()->find($roomMap->room_type_id);
                if ($room === null || (int) $room->accommodation_id !== (int) $map->accommodation_id) {
                    throw new RuntimeException("SnappTrip room mapping {$providerRoomId} belongs to another accommodation.");
                }
            } else {
                $room = RoomType::query()->firstOrCreate(
                    ['accommodation_id' => $map->accommodation_id, 'fa_name' => $name],
                    ['room_type_name_id' => $nameRecord->id],
                );
                $roomMap = RoomTypeProviderMap::query()->create([
                    'room_type_id' => $room->id,
                    'provider_id' => $provider->id,
                    'accommodation_provider_map_id' => $map->id,
                    'provider_room_type_id' => $providerRoomId,
                    'fa_name' => $name,
                    'en_name' => null,
                ]);
            }

            $adultCapacity = $this->nullableUnsignedSmallInt($row['adult_capacity'] ?? null);
            $extraCapacity = $this->nullableUnsignedSmallInt($row['extra_capacity'] ?? null);
            $room->update(array_filter([
                'room_type_name_id' => $nameRecord->id,
                'fa_name' => $name,
                'capacity' => $adultCapacity,
                'extra_capacity' => $extraCapacity,
                'out_of_service' => false,
            ], static fn ($value): bool => $value !== null));

            $roomMap->update(['fa_name' => $name]);

            $this->ensureRatePlan(
                $provider,
                $map,
                $providerRoomId,
                $this->normalizeBoardType($row['board_type'] ?? null),
                false,
            );
        }
    }

    private function ensureRatePlan(
        Provider $provider,
        AccommodationProviderMap $map,
        string $providerRoomId,
        string $boardType,
        bool $foreigner,
    ): RatePlanProviderMap {
        [$faName, $enName, $mealType] = $this->ratePlanAttributes($boardType, $foreigner);
        $plan = RatePlan::query()->firstOrCreate(
            ['accommodation_id' => $map->accommodation_id, 'fa_name' => $faName],
            [
                'en_name' => $enName,
                'meal_type' => $mealType,
                'cancelable' => true,
                'is_foreign_guest' => $foreigner,
            ],
        );
        $plan->update([
            'en_name' => $plan->en_name ?: $enName,
            'meal_type' => $mealType,
            'is_foreign_guest' => $foreigner,
        ]);

        $providerPlanId = $this->providerRatePlanId($providerRoomId, $foreigner);
        $existing = RatePlanProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_provider_map_id', $map->id)
            ->where('provider_rate_plan_id', $providerPlanId)
            ->first();

        if ($existing !== null) {
            $existingPlan = RatePlan::query()->find($existing->rate_plan_id);
            if ($existingPlan === null || (int) $existingPlan->accommodation_id !== (int) $map->accommodation_id) {
                throw new RuntimeException("SnappTrip rate-plan mapping {$providerPlanId} belongs to another accommodation.");
            }

            $existing->update([
                'rate_plan_id' => $plan->id,
                'fa_name' => $faName,
                'en_name' => $enName,
            ]);

            return $existing;
        }

        return RatePlanProviderMap::query()->create([
            'rate_plan_id' => $plan->id,
            'provider_id' => $provider->id,
            'accommodation_provider_map_id' => $map->id,
            'provider_rate_plan_id' => $providerPlanId,
            'fa_name' => $faName,
            'en_name' => $enName,
        ]);
    }

    private function providerRatePlanId(string $providerRoomId, bool $foreigner): string
    {
        return sprintf('room:%s:%s', $providerRoomId, $foreigner ? 'foreign' : 'domestic');
    }

    private function boardTypeFromRatePlan(RatePlan $plan): string
    {
        return match ((string) $plan->meal_type) {
            'breakfast' => 'bed_breakfast',
            'half_board' => 'half_board',
            'full_board' => 'full_board',
            default => 'room_only',
        };
    }

    /** @return array{0:string,1:string,2:?string} */
    private function ratePlanAttributes(string $boardType, bool $foreigner): array
    {
        [$fa, $en, $meal] = match ($boardType) {
            'bed_breakfast' => ['اقامت با صبحانه', 'breakfast', 'breakfast'],
            'half_board' => ['هاف برد', 'half_board', 'half_board'],
            'full_board' => ['فولبرد', 'full_board', 'full_board'],
            default => ['اقامت بدون صبحانه', 'room_only', null],
        };

        if ($foreigner) {
            $fa = 'مهمان خارجی - '.$fa;
            $en = 'foreign_'.$en;
        }

        return [$fa, $en, $meal];
    }

    private function normalizeBoardType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['room_only', 'bed_breakfast', 'half_board', 'full_board'], true)
            ? $value
            : 'room_only';
    }

    private function normalize(mixed $value, bool $stripHotelPrefix = false): string
    {
        $value = mb_strtolower($this->nullableString($value) ?? '', 'UTF-8');
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة', '‌', 'ـ'], ['ی', 'ی', 'ک', 'ه', 'ه', ' ', ''], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
        $value = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value);
        if ($stripHotelPrefix) {
            $value = trim(preg_replace('/^(?:هتل|hotel)\s+/u', '', $value) ?? $value);
        }

        return $value;
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $coordinate = (float) $value;

        return is_finite($coordinate) && $coordinate >= $min && $coordinate <= $max ? $coordinate : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    private function nullableUnsignedTinyInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $number = (int) $value;

        return $number >= 0 && $number <= 255 ? $number : null;
    }

    private function nullableUnsignedSmallInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $number = (int) $value;

        return $number >= 0 && $number <= 65535 ? $number : null;
    }
}
