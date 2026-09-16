<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\RatePlan;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\AvailabilityFilterService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AvailabilityMixedRoomAllocationTest extends TestCase
{
    private function select(
        array $requestedRooms,
        array $roomDefinitions,
        array $days = ['2027-03-04']
    ): Collection {
        $hotel = new Accommodation();
        $hotel->setAttribute('id', 35);
        $hotel->setRelation('childPolicy', null);

        $rooms = [];
        $calendars = [];
        $calendarId = 1;

        foreach ($roomDefinitions as $definition) {
            $room = new RoomType([
                'id' => $definition['id'],
                'capacity' => $definition['capacity'],
                'extra_capacity' => 0,
                'out_of_service' => false,
            ]);
            $rooms[] = $room;

            foreach ($days as $dayIndex => $day) {
                $inventory = $definition['inventory'];
                if (is_array($inventory)) {
                    $inventory = $inventory[$dayIndex];
                }

                $calendar = new RoomCalendar([
                    'id' => $calendarId++,
                    'accommodation_id' => 35,
                    'room_type_id' => $definition['id'],
                    'rate_plan_id' => 740,
                    'provider_id' => 1,
                    'day' => $day,
                    'inventory' => $inventory,
                    'closed' => false,
                    'daily_rate' => $definition['price'],
                    'rack_rate' => $definition['price'],
                    'grs_rate' => $definition['price'],
                ]);
                $calendar->setRelation('ratePlan', new RatePlan());
                $calendars[] = $calendar;
            }
        }

        $hotel->setRelation('rooms', collect($rooms));

        $service = new AvailabilityFilterService(
            $this->createMock(RoomCalendarRepositoryInterface::class)
        );

        return (new ReflectionMethod($service, 'findCheapestAvailableCombination'))
            ->invoke($service, $hotel, $requestedRooms, $days, collect($calendars));
    }

    private function request(int $adults = 1): array
    {
        return [
            'passengers' => array_fill(0, $adults, ['type' => 'adl', 'age' => 20]),
        ];
    }

    public function test_five_identical_requests_use_four_singles_plus_one_double(): void
    {
        $result = $this->select(
            array_fill(0, 5, $this->request()),
            [
                ['id' => 3402, 'capacity' => 1, 'inventory' => 4, 'price' => 49000000],
                ['id' => 3403, 'capacity' => 2, 'inventory' => 2, 'price' => 70000000],
                ['id' => 3404, 'capacity' => 2, 'inventory' => 4, 'price' => 70000000],
                ['id' => 3405, 'capacity' => 2, 'inventory' => 3, 'price' => 80000000],
                ['id' => 3406, 'capacity' => 2, 'inventory' => 3, 'price' => 86000000],
            ]
        );

        $this->assertCount(5, $result);
        $this->assertSame([3402 => 4, 3403 => 1], $result->countBy('id')->all());
        $this->assertSame(266000000, $result->sum('total_price'));
        $this->assertSame([0, 1, 2, 3, 4], $result->pluck('requested_room_index')->all());
        $this->assertSame(4, $result->firstWhere('id', 3402)->required_inventory);
        $this->assertSame(1, $result->firstWhere('id', 3403)->required_inventory);
    }

    public function test_multinight_allocation_uses_lowest_inventory_across_all_nights(): void
    {
        $result = $this->select(
            array_fill(0, 3, $this->request()),
            [
                ['id' => 3402, 'capacity' => 1, 'inventory' => [4, 1], 'price' => 49000000],
                ['id' => 3403, 'capacity' => 2, 'inventory' => [4, 4], 'price' => 70000000],
            ],
            ['2027-03-04', '2027-03-05']
        );

        $this->assertCount(3, $result);
        $this->assertSame([3402 => 1, 3403 => 2], $result->countBy('id')->all());
        $this->assertSame(378000000, $result->sum('total_price'));
    }

    public function test_constrained_request_gets_shared_cheap_room_when_it_minimizes_total(): void
    {
        $result = $this->select(
            [$this->request(1), $this->request(2)],
            [
                ['id' => 10, 'capacity' => 2, 'inventory' => 1, 'price' => 10000000],
                ['id' => 11, 'capacity' => 1, 'inventory' => 1, 'price' => 11000000],
                ['id' => 12, 'capacity' => 2, 'inventory' => 1, 'price' => 50000000],
            ]
        );

        $this->assertCount(2, $result);
        $this->assertSame([11, 10], $result->pluck('id')->all());
        $this->assertSame([0, 1], $result->pluck('requested_room_index')->all());
        $this->assertSame(21000000, $result->sum('total_price'));
    }

    public function test_hotel_is_rejected_if_the_full_request_cannot_be_fulfilled(): void
    {
        $result = $this->select(
            [$this->request(), $this->request()],
            [['id' => 3402, 'capacity' => 1, 'inventory' => 1, 'price' => 49000000]]
        );

        $this->assertCount(0, $result);
    }
}
