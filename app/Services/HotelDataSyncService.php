<?php

namespace App\Services;

use App\Models\AccommodationProviderMap;
use App\Models\Accommodation;
use App\Models\HotelChildPolicy;
use App\Models\Provider;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Services\HotelChildPolicyTextParser;
use App\Services\Contracts\RatePlanProviderMapServiceInterface;
use App\Services\Contracts\RatePlanServiceInterface;
use App\Services\Contracts\RoomTypeNameServiceInterface;
use App\Services\Contracts\RoomTypeProviderMapServiceInterface;
use App\Services\Contracts\RoomTypeServiceInterface;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use Illuminate\Support\Facades\Log;

class HotelDataSyncService
{
    private const DEFAULT_FACILITY_GROUP_ID = 11;

    public function __construct(
        protected RoomTypeServiceInterface $roomTypeService,
        protected RoomTypeNameServiceInterface $roomTypeNameService,
        protected RoomTypeProviderMapServiceInterface $roomTypeProviderMapService,
        protected RatePlanServiceInterface $ratePlanService,
        protected RatePlanProviderMapServiceInterface $ratePlanProviderMapService,
        protected FacilityRepositoryInterface $facilityRepo,
        protected HotelChildPolicyTextParser $childPolicyParser
    ) {}

    public function syncRoomTypes(AccommodationProviderMap $map, $roomTypesData): void
    {
        $provider = $map->provider;

        $propertyFacilities = null;
        $propertyRules = null;

        foreach ($roomTypesData as $data) {
            if (!is_array($data)) {
                continue;
            }

            if ($propertyFacilities === null && array_key_exists('property_facilities', $data)) {
                $propertyFacilities = is_array($data['property_facilities'] ?? null) ? $data['property_facilities'] : [];
            }

            if ($propertyRules === null && array_key_exists('property_rules', $data)) {
                $propertyRules = is_array($data['property_rules'] ?? null) ? $data['property_rules'] : [];
            }

            if (!isset($data['room_type_id'], $data['fa_name'])) {
                continue;
            }

            $this->processRoomType($map, $provider, $data);
        }

        if (is_array($propertyFacilities) && $propertyFacilities !== []) {
            $this->syncFacilities($map, $propertyFacilities);
        }

        if (is_array($propertyRules) && $propertyRules !== []) {
            $this->syncRules($map, $propertyRules);
        }
    }

    protected function processRoomType(AccommodationProviderMap $map, Provider $provider, array $data): void
    {
        $providerRoomTypeId = $data['room_type_id'];
        $providerName = $data['fa_name'];
        $providerEnName = $data['en_name'] ?? null;

        $normalizedName = trim(preg_replace('/\s+/', ' ', $providerName));
        $normalizedEnName = $providerEnName ? trim(preg_replace('/\s+/', ' ', $providerEnName)) : null;

        $roomTypeName = $this->roomTypeNameService->firstOrCreate(
            ['fa_name' => $normalizedName],
            ['en_name' => $normalizedEnName]
        );

        if (!$roomTypeName->en_name && $normalizedEnName) {
            $this->roomTypeNameService->update($roomTypeName->id, ['en_name' => $normalizedEnName]);
        }

        $mapping = $this->roomTypeProviderMapService->repository()->findDynamic([
            'provider_id' => $provider->id,
            'provider_room_type_id' => $providerRoomTypeId
        ]);

        $roomType = null;

        if ($mapping) {
            $roomType = $this->roomTypeService->show($mapping->room_type_id);
            if ($roomType) {
                if ($roomType->room_type_name_id !== $roomTypeName->id) {
                    $this->roomTypeService->update($roomType->id, ['room_type_name_id' => $roomTypeName->id]);
                }
            }
        } else {
            $roomType = $this->roomTypeService->repository()->findDynamic([
                'accommodation_id' => $map->accommodation_id,
                'fa_name' => $providerName
            ]);

            if (!$roomType) {
                $roomType = $this->roomTypeService->store([
                    'accommodation_id' => $map->accommodation_id,
                    'fa_name' => $providerName,
                    'en_name' => $providerEnName,
                    'capacity' => $data['capacity'] ?? null,
                    'extra_capacity' => $data['extra'] ?? null,
                    'room_type_name_id' => $roomTypeName->id,
                ]);
            } else {
                $this->roomTypeService->update($roomType->id, ['room_type_name_id' => $roomTypeName->id]);
            }

            $this->roomTypeProviderMapService->store([
                'room_type_id' => $roomType->id,
                'provider_id' => $provider->id,
                'provider_room_type_id' => $providerRoomTypeId,
                'fa_name' => $providerName,
                'en_name' => $providerEnName,
            ]);
        }

        $ratePlans = $data['rate_plans'] ?? [];
        foreach ($ratePlans as $rpData) {
            $this->processRatePlan($map, $provider, $rpData);
        }
    }

    protected function processRatePlan(AccommodationProviderMap $map, Provider $provider, array $rpData): void
    {
        $providerRpId = (string) ($rpData['id'] ?? '');
        if (!$providerRpId) {
            return;
        }

        $rpName = $rpData['name'] ?? null;
        $rpEnName = $rpData['name_en'] ?? null;

        $rpMap = $this->ratePlanProviderMapService->repository()->findDynamic([
            'provider_id' => $provider->id,
            'provider_rate_plan_id' => $providerRpId
        ]);

        if (!$rpMap) {
            $ratePlan = $this->ratePlanService->repository()->findDynamic([
                'accommodation_id' => $map->accommodation_id,
                'fa_name' => $rpName
            ]);

            if (!$ratePlan) {
                $ratePlan = $this->ratePlanService->store([
                    'accommodation_id' => $map->accommodation_id,
                    'fa_name' => $rpName,
                    'en_name' => $rpEnName,
                    'meal_type' => $rpData['meal_type_included'] ?? null,
                    'food_board_type' => null,
                    'cancelable' => (bool) ($rpData['cancelable'] ?? true),
                    'sleeps' => $rpData['sleeps'] ?? null,
                    'min_stay' => $rpData['min_stay'] ?? null,
                    'max_stay' => $rpData['max_stay'] ?? null,
                ]);
            }

            $this->ratePlanProviderMapService->store([
                'rate_plan_id' => $ratePlan->id,
                'provider_id' => $provider->id,
                'provider_rate_plan_id' => $providerRpId,
                'fa_name' => $rpName,
                'en_name' => $rpEnName,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $facilities
     */
    protected function syncFacilities(AccommodationProviderMap $map, array $facilities): void
    {
        $acc = $map->accommodation;
        if (!$acc) {
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

            $facilityModel = $this->facilityRepo->findDynamic(
                where: ['fa_name' => $facilityName],
                with: ['group']
            );

            if (!$facilityModel) {
                $facilityModel = $this->facilityRepo->store([
                    'fa_name' => $facilityName,
                    'en_name' => $this->normalizeString($facility['name_en'] ?? null),
                    'facility_group_id' => self::DEFAULT_FACILITY_GROUP_ID,
                ]);
            }

            $facilityIds[$facilityModel->id] = [
                'description' => (string) ($facility['description'] ?? ''),
            ];
        }

        if ($facilityIds !== []) {
            $acc->facilities()->sync($facilityIds);
        } else {
            Log::warning('Facilities sync skipped: no valid facilities', [
                'accommodation_id' => $acc->id,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     */
    protected function syncRules(AccommodationProviderMap $map, array $rules): void
    {
        $accommodationId = (int) $map->accommodation_id;
        if ($accommodationId <= 0) {
            return;
        }

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $providerRuleId = $this->normalizeString($rule['id'] ?? null);
            if ($providerRuleId === null) {
                continue;
            }

            $categoryCode = $this->normalizeString($rule['category'] ?? null);
            $categoryId = null;

            if ($categoryCode !== null) {
                $category = RuleCategory::query()->firstOrCreate(
                    ['code' => $categoryCode],
                    [
                        'name' => $categoryCode,
                        'name_en' => null,
                        'name_ar' => null,
                    ]
                );
                $categoryId = $category->id;
            }

            $conditions = $rule['conditions'] ?? null;
            if (!is_array($conditions)) {
                $conditions = null;
            }

            Rule::query()->updateOrCreate(
                [
                    'hotel_id' => $accommodationId,
                    'provider_rule_id' => $providerRuleId,
                ],
                [
                    'rule_category_id' => $categoryId,
                    'rule_id' => is_numeric($rule['rule_id'] ?? null) ? (int) $rule['rule_id'] : null,
                    'type' => $this->normalizeString($rule['type'] ?? null),
                    'name' => $this->normalizeString($rule['name'] ?? null),
                    'name_ar' => $this->normalizeString($rule['name_ar'] ?? null),
                    'name_en' => $this->normalizeString($rule['name_en'] ?? null),
                    'conditions' => $conditions,
                    'room_type_id' => $this->normalizeString($rule['room_type_id'] ?? null),
                    'rate_plan_id' => $this->normalizeString($rule['rate_plan_id'] ?? null),
                    'description' => $this->normalizeText($rule['description'] ?? null),
                    'description_ar' => $this->normalizeText($rule['description_ar'] ?? null),
                    'description_en' => $this->normalizeText($rule['description_en'] ?? null),
                ]
            );

            if ($categoryCode === 'children') {
                $this->syncChildPolicy($accommodationId, $conditions, $rule);
            }
        }
    }

    /**
     * @param array<string, mixed>|null $conditions
     * @param array<string, mixed> $rule
     */
    protected function syncChildPolicy(int $accommodationId, ?array $conditions, array $rule): void
    {
        if ($accommodationId <= 0) {
            return;
        }

        $conditions = is_array($conditions) ? $conditions : [];
        $maxInfantAge = $this->toUnsignedTinyInt($conditions['max_infant_age'] ?? null);
        $maxChildAge = $this->toUnsignedTinyInt($conditions['max_child_age'] ?? null);
        $description = $this->normalizeText($rule['description'] ?? null)
            ?? $this->normalizeText($rule['description_ar'] ?? null)
            ?? $this->normalizeText($rule['description_en'] ?? null);

        $parsed = [];
        if ($description !== null) {
            $parsed = $this->childPolicyParser->parse($description, $conditions);
        }

        $payload = [
            'max_infant_age' => $maxInfantAge,
            'max_child_age' => $maxChildAge,
            'description' => $description,
            'status' => true,
        ];

        foreach ($parsed as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        // When the provider specifies either coverage cap, synchronize BOTH.
        // Otherwise an old infant cap can linger after the source changes to
        // a shared limit (or vice versa). If the source mentions no limit,
        // retain manually curated Admin values rather than clearing them.
        if (
            ($parsed['max_children_covered'] ?? null) !== null
            || ($parsed['max_infants_covered'] ?? null) !== null
        ) {
            $payload['max_children_covered'] = $parsed['max_children_covered'];
            $payload['max_infants_covered'] = $parsed['max_infants_covered'];
        }

        HotelChildPolicy::query()->updateOrCreate(
            ['accommodation_id' => $accommodationId],
            $payload
        );
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }

    private function toUnsignedTinyInt(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        $intValue = (int) $value;

        if ($intValue < 0) {
            return 0;
        }

        if ($intValue > 255) {
            return 255;
        }

        return $intValue;
    }
}
