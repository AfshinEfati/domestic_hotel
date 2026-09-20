<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\AccommodationProviderMap;
use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\HotelChildPolicy;
use App\Models\Provider;
use App\Models\RatePlan;
use App\Models\RatePlanProviderMap;
use App\Models\RoomType;
use App\Models\RoomTypeName;
use App\Models\RoomTypeProviderMap;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Services\HotelChildPolicyTextParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Persistence exclusively for the weekly GRS property-details workflow. */
class GrsHotelDetailsRepository
{
    public function grsProvider(): ?Provider
    {
        return Provider::query()->where('code', 'grs')->first();
    }

    /** @return Collection<int, AccommodationProviderMap> */
    public function mappedHotels(int $providerId): Collection
    {
        return AccommodationProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereHas('accommodation')
            ->orderBy('id')
            ->get(['id', 'provider_id', 'accommodation_id', 'provider_property_id']);
    }

    public function mappedHotel(int $providerId, int $mapId): ?AccommodationProviderMap
    {
        return AccommodationProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereKey($mapId)
            ->with('accommodation')
            ->first();
    }

    public function persist(AccommodationProviderMap $map, array $property, HotelChildPolicyTextParser $parser): void
    {
        $facilityIds = [];
        if ($property['facilities'] !== []) {
            // Facility/group names have no DB uniqueness constraints. Commit
            // dictionary entries before releasing this short shared lock.
            // Room/plan/rule updates remain parallel and independent per hotel.
            $facilityIds = Cache::store('redis')->lock('grs-v2-hotel-details-dictionary', 60)
                ->block(30, fn (): array => DB::transaction(
                    fn (): array => $this->resolveFacilities($property['facilities'])
                ));
        }

        DB::transaction(function () use ($map, $property, $parser, $facilityIds): void {
            $map->refresh();
            $hotel = $map->accommodation;
            if ($hotel === null || (string) $map->provider_property_id !== (string) ($property['id'] ?? '')) {
                throw new RuntimeException('GRS details mapping was deleted or changed during refresh.');
            }
            if ($facilityIds !== []) {
                // Do not remove manually curated or other providers' facilities.
                $hotel->facilities()->syncWithoutDetaching($facilityIds);
            }

            foreach ($property['room_types'] as $roomData) {
                if (!is_array($roomData)) {
                    throw new RuntimeException('GRS property has an invalid room type.');
                }
                $this->syncRoom($map, $roomData);
            }
            // The provider also exposes a property-level plan list. Reuse the
            // same mappings; no duplicate plans and no calendar writes.
            foreach (is_array($property['rate_plans'] ?? null) ? $property['rate_plans'] : [] as $planData) {
                if (is_array($planData)) {
                    $this->syncRatePlan($map, $planData);
                }
            }
            foreach ($property['rules'] as $ruleData) {
                if (!is_array($ruleData)) {
                    throw new RuntimeException('GRS property has an invalid rule.');
                }
                $this->syncRule((int) $map->accommodation_id, $ruleData, $parser);
            }
        });
    }

    /** @return array<int, array{description: ?string}> */
    private function resolveFacilities(array $facilities): array
    {
        $ids = [];
        foreach ($facilities as $data) {
            if (!is_array($data)) {
                throw new RuntimeException('GRS facility must be an object.');
            }
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $groupName = trim((string) ($data['group_name'] ?? '')) ?: 'سایر';
            $group = FacilityGroup::query()->firstOrCreate(['fa_name' => $groupName]);
            $facility = Facility::query()->firstOrCreate(
                ['facility_group_id' => $group->id, 'fa_name' => $name],
                ['en_name' => $this->nullableString($data['name_en'] ?? null)]
            );
            if (!$facility->en_name && $this->nullableString($data['name_en'] ?? null) !== null) {
                $facility->update(['en_name' => $this->nullableString($data['name_en'])]);
            }
            $description = $this->nullableString($data['description'] ?? null);
            $ids[$facility->id] = ['description' => $description === null ? null : mb_substr($description, 0, 500)];
        }
        return $ids;
    }

    private function syncRoom(AccommodationProviderMap $map, array $data): void
    {
        $providerRoomId = trim((string) ($data['id'] ?? ''));
        $name = $this->nullableString($data['name'] ?? null);
        if ($providerRoomId === '' || $name === null) {
            throw new RuntimeException('GRS room requires both ID and name.');
        }

        $nameRecord = RoomTypeName::query()->firstOrCreate(
            ['fa_name' => $name],
            ['en_name' => $this->nullableString($data['name_en'] ?? null)]
        );
        if (!$nameRecord->en_name && $this->nullableString($data['name_en'] ?? null) !== null) {
            $nameRecord->update(['en_name' => $this->nullableString($data['name_en'])]);
        }

        $roomMap = RoomTypeProviderMap::query()
            ->where('provider_id', $map->provider_id)
            ->where('provider_room_type_id', $providerRoomId)
            ->first();
        if ($roomMap !== null) {
            $room = RoomType::query()->find($roomMap->room_type_id);
            if ($room === null || (int) $room->accommodation_id !== (int) $map->accommodation_id) {
                throw new RuntimeException('GRS room mapping belongs to a missing or different hotel: '.$providerRoomId);
            }
        } else {
            $room = RoomType::query()->firstOrCreate(
                ['accommodation_id' => $map->accommodation_id, 'fa_name' => $name],
                ['room_type_name_id' => $nameRecord->id]
            );
            RoomTypeProviderMap::query()->create([
                'room_type_id' => $room->id,
                'provider_id' => $map->provider_id,
                'provider_room_type_id' => $providerRoomId,
                'fa_name' => $name,
                'en_name' => $this->nullableString($data['name_en'] ?? null),
            ]);
        }

        $attributes = ['room_type_name_id' => $nameRecord->id, 'fa_name' => $name];
        if (array_key_exists('name_en', $data)) {
            $attributes['en_name'] = $this->nullableString($data['name_en']);
        }
        foreach (['capacity' => 'capacity', 'extra_capacity' => 'extra_capacity',
            'single_bed_count' => 'single_bed_count', 'double_bed_count' => 'double_bed_count',
            'sofa_bed_count' => 'sofa_bed_count'] as $source => $column) {
            if (array_key_exists($source, $data)) {
                $attributes[$column] = $this->nullableUnsignedSmallInt($data[$source]);
            }
        }
        if (array_key_exists('out_of_service', $data)) {
            $attributes['out_of_service'] = (bool) $data['out_of_service'];
        }
        $room->update($attributes);
        if ($roomMap !== null) {
            $roomMap->update(['fa_name' => $name, 'en_name' => $this->nullableString($data['name_en'] ?? null)]);
        }

        foreach (is_array($data['rate_plans'] ?? null) ? $data['rate_plans'] : [] as $planData) {
            if (!is_array($planData)) {
                throw new RuntimeException('GRS room has an invalid rate plan.');
            }
            $this->syncRatePlan($map, $planData);
        }
    }

    private function syncRatePlan(AccommodationProviderMap $map, array $data): void
    {
        $providerPlanId = trim((string) ($data['id'] ?? ''));
        $name = $this->nullableString($data['name'] ?? null);
        if ($providerPlanId === '' || $name === null) {
            throw new RuntimeException('GRS rate plan requires both ID and name.');
        }

        $planMap = RatePlanProviderMap::query()
            ->where('provider_id', $map->provider_id)
            ->where('provider_rate_plan_id', $providerPlanId)
            ->first();
        if ($planMap !== null) {
            $plan = RatePlan::query()->find($planMap->rate_plan_id);
            if ($plan === null || (int) $plan->accommodation_id !== (int) $map->accommodation_id) {
                throw new RuntimeException('GRS rate plan mapping belongs to a missing or different hotel: '.$providerPlanId);
            }
        } else {
            $plan = RatePlan::query()->firstOrCreate(
                ['accommodation_id' => $map->accommodation_id, 'fa_name' => $name]
            );
            RatePlanProviderMap::query()->create([
                'rate_plan_id' => $plan->id,
                'provider_id' => $map->provider_id,
                'provider_rate_plan_id' => $providerPlanId,
                'fa_name' => $name,
                'en_name' => $this->nullableString($data['name_en'] ?? null),
            ]);
        }

        $attributes = ['fa_name' => $name];
        if (array_key_exists('name_en', $data)) {
            $attributes['en_name'] = $this->nullableString($data['name_en']);
        }
        if (array_key_exists('meal_type_included', $data)) {
            $meal = $data['meal_type_included'];
            $attributes['meal_type'] = in_array($meal, ['breakfast', 'half_board', 'full_board'], true) ? $meal : null;
        }
        if (array_key_exists('food_board_type', $data)) {
            $board = $data['food_board_type'];
            $attributes['food_board_type'] = in_array($board, ['limit_options', 'full_options'], true) ? $board : null;
        }
        if (array_key_exists('cancelable', $data)) {
            $attributes['cancelable'] = (bool) $data['cancelable'];
        }
        foreach (['sleeps', 'min_stay', 'max_stay'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $this->nullableUnsignedSmallInt($data[$field]);
            }
        }
        if (array_key_exists('facilities', $data) && is_array($data['facilities'])) {
            $attributes['facilities'] = $data['facilities'];
        }
        $plan->update($attributes);
        if ($planMap !== null) {
            $planMap->update(['fa_name' => $name, 'en_name' => $this->nullableString($data['name_en'] ?? null)]);
        }
    }

    private function syncRule(int $hotelId, array $data, HotelChildPolicyTextParser $parser): void
    {
        $ruleId = trim((string) ($data['id'] ?? ''));
        if ($ruleId === '') {
            throw new RuntimeException('GRS property contains a rule without an ID.');
        }
        $categoryCode = $this->nullableString($data['category'] ?? null);
        $categoryId = $categoryCode === null ? null : RuleCategory::query()->firstOrCreate(
            ['code' => $categoryCode], ['name' => $categoryCode]
        )->id;
        $conditions = is_array($data['conditions'] ?? null) ? $data['conditions'] : null;
        Rule::query()->updateOrCreate(
            ['hotel_id' => $hotelId, 'provider_rule_id' => $ruleId],
            [
                'rule_category_id' => $categoryId,
                'rule_id' => is_numeric($data['rule_id'] ?? null) ? (int) $data['rule_id'] : null,
                'type' => $this->nullableString($data['type'] ?? null),
                'name' => $this->nullableString($data['name'] ?? null),
                'name_ar' => $this->nullableString($data['name_ar'] ?? null),
                'name_en' => $this->nullableString($data['name_en'] ?? null),
                'conditions' => $conditions,
                'room_type_id' => $this->nullableString($data['room_type_id'] ?? null),
                'rate_plan_id' => $this->nullableString($data['rate_plan_id'] ?? null),
                'description' => $this->nullableString($data['description'] ?? null),
                'description_ar' => $this->nullableString($data['description_ar'] ?? null),
                'description_en' => $this->nullableString($data['description_en'] ?? null),
                'status' => true,
            ]
        );
        if ($categoryCode !== 'children') {
            return;
        }

        $conditions ??= [];
        $description = $this->nullableString($data['description'] ?? null)
            ?? $this->nullableString($data['description_ar'] ?? null)
            ?? $this->nullableString($data['description_en'] ?? null);
        $parsed = $description === null ? [] : $parser->parse($description, $conditions);
        $payload = [
            'max_infant_age' => $this->nullableUnsignedTinyInt($conditions['max_infant_age'] ?? null) ?? 0,
            'max_child_age' => $this->nullableUnsignedTinyInt($conditions['max_child_age'] ?? null) ?? 0,
            'description' => $description,
            'status' => true,
        ];
        foreach ($parsed as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }
        if (($parsed['max_children_covered'] ?? null) !== null || ($parsed['max_infants_covered'] ?? null) !== null) {
            $payload['max_children_covered'] = $parsed['max_children_covered'];
            $payload['max_infants_covered'] = $parsed['max_infants_covered'];
        }
        HotelChildPolicy::query()->updateOrCreate(['accommodation_id' => $hotelId], $payload);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value === '' ? null : $value;
    }

    private function nullableUnsignedSmallInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value) || (int) $value < 0 || (int) $value > 65535) {
            throw new RuntimeException('GRS details contain an invalid unsigned small integer.');
        }
        return (int) $value;
    }

    private function nullableUnsignedTinyInt(mixed $value): ?int
    {
        $number = $this->nullableUnsignedSmallInt($value);
        if ($number !== null && $number > 255) {
            throw new RuntimeException('GRS child age exceeds the database range.');
        }
        return $number;
    }
}
