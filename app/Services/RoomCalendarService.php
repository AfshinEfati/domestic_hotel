<?php

namespace App\Services;

use App\DTOs\RoomCalendarDTO;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\Contracts\HotelRatePricingServiceInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;
use Illuminate\Support\Collection;

class RoomCalendarService extends BaseService implements RoomCalendarServiceInterface
{
    public function __construct(
        RoomCalendarRepositoryInterface $repository,
        private readonly HotelRatePricingServiceInterface $ratePricingService,
    ) {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RoomCalendar>
     */
    public function index(): iterable
    {
        /** @var iterable<RoomCalendar> */
        return parent::index();
    }

    public function show(int|string $id): ?RoomCalendar
    {
        /** @var RoomCalendar|null */
        return parent::show($id);
    }

    /**
     * @param RoomCalendarDTO|array $payload
     * @return RoomCalendar
     */
    public function store(mixed $payload): RoomCalendar
    {
        if ($payload instanceof RoomCalendarDTO) {
            $payload = $payload->toArray();
        }

        /** @var RoomCalendar */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RoomCalendarDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RoomCalendarDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    protected function relations(): array
    {
        return [
            'accommodation.city',
            'accommodation.type',
            'accommodation.facilities',
            'roomType.accommodation.city',
            'roomType.accommodation.type',
            'roomType.accommodation.facilities',
            'ratePlan.accommodation.city',
            'ratePlan.accommodation.type',
            'ratePlan.accommodation.facilities',
            'provider.cityMaps.city',
        ];
    }

    public function getAvailableRoomsByAccommodationId(int $accommodationId): array
    {
        $calendars = $this->repository
            ->getAvailableByAccommodationId($accommodationId);

        $rooms = $calendars
            ->groupBy('room_type_id')
            ->map(function ($roomCalendars) {
                $firstCalendar = $roomCalendars->first();
                $room = $firstCalendar->roomType;

                $ratePlans = $roomCalendars
                    ->groupBy('rate_plan_id')
                    ->map(function ($ratePlanCalendars) {
                        $first = $ratePlanCalendars->first();
                        $ratePlan = $first->ratePlan;

                        return [
                            'id' => $ratePlan?->id,
                            'fa_name' => $ratePlan?->fa_name,
                            'en_name' => $ratePlan?->en_name,
                            'meal_type' => $ratePlan?->meal_type,
                            'food_board_type' => $ratePlan?->food_board_type,
                            'cancelable' => $ratePlan?->cancelable,

                            'calendar' => $ratePlanCalendars
                                ->map(fn ($calendar) => [
                                    'day' => $calendar->day?->format('Y-m-d'),
                                    'inventory' => $calendar->inventory,
                                    'provider_id' => $calendar->provider_id,

                                    'rack_rate' => $calendar->rack_rate,
                                    'daily_rate' => $calendar->daily_rate,
                                    'grs_rate' => $calendar->grs_rate,
                                    'final_rate' => $this->ratePricingService->calculateFinalRate(
                                        $calendar->grs_rate,
                                        $calendar->provider_id
                                    ),

                                    'baby_cot_rack_rate' => $calendar->baby_cot_rack_rate,
                                    'baby_cot_daily_rate' => $calendar->baby_cot_daily_rate,
                                    'baby_cot_grs_rate' => $calendar->baby_cot_grs_rate,
                                    'baby_cot_final_rate' => $this->ratePricingService->calculateFinalRate(
                                        $calendar->baby_cot_grs_rate,
                                        $calendar->provider_id
                                    ),

                                    'extend_bed_rack_rate' => $calendar->extend_bed_rack_rate,
                                    'extend_bed_daily_rate' => $calendar->extend_bed_daily_rate,
                                    'extend_bed_grs_rate' => $calendar->extend_bed_grs_rate,
                                    'extend_bed_final_rate' => $this->ratePricingService->calculateFinalRate(
                                        $calendar->extend_bed_grs_rate,
                                        $calendar->provider_id
                                    ),

                                    'min_stay' => $calendar->min_stay,
                                    'max_stay' => $calendar->max_stay,
                                    'cta' => $calendar->cta,
                                    'ctd' => $calendar->ctd,
                                ])
                                ->values(),
                        ];
                    })
                    ->values();

                return [
                    'id' => $room->id,
                    'fa_name' => $room->fa_name,
                    'en_name' => $room->en_name,
                    'capacity' => $room->capacity,
                    'extra_capacity' => $room->extra_capacity,
                    'single_bed_count' => $room->single_bed_count,
                    'double_bed_count' => $room->double_bed_count,
                    'sofa_bed_count' => $room->sofa_bed_count,

                    'room_type_name' => $room->roomTypeName
                        ? [
                            'id' => $room->roomTypeName->id,
                            'fa_name' => $room->roomTypeName->fa_name,
                            'en_name' => $room->roomTypeName->en_name,
                        ]
                        : null,

                    'rate_plans' => $ratePlans,
                ];
            })
            ->values();

        return [
            'accommodation_id' => $accommodationId,
            'rooms' => $rooms,
        ];
    }

    public function getByRoomTypeIdsAndDays(array $roomTypeIds, array $days): Collection
    {
        return $this->repository->getByRoomTypeIdsAndDays($roomTypeIds, $days);
    }
}
