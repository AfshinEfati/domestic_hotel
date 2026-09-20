<?php

namespace Tests\Feature;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Accommodation;
use App\Models\Provider;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\ReservationCreateValidator;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ReservationCreateValidationTest extends TestCase
{
    private function requestData(int $price = 25000000): array
    {
        return [
            'check_in' => '2027-03-04',
            'check_out' => '2027-03-05',
            'hotel' => [
                'accommodation_id' => 123,
                'rooms' => [[
                    'room_calendar_id' => 5480,
                    'price' => $price,
                    'guests' => [['type' => 1]],
                ]],
            ],
        ];
    }

    private function validator(array $availability, bool $online = true): ReservationCreateValidator
    {
        $hotel = new Accommodation(['id' => 123]);
        $hotel->setRelation('childPolicy', null);
        $room = new RoomType([
            'id' => 456,
            'accommodation_id' => 123,
            'capacity' => 2,
            'extra_capacity' => 0,
            'out_of_service' => false,
        ]);
        $calendar = new RoomCalendar([
            'id' => 5480,
            'accommodation_id' => 123,
            'room_type_id' => 456,
            'rate_plan_id' => 789,
            'provider_id' => 3,
            'provider_property_id' => 'property',
            'provider_room_type_id' => 'room',
            'provider_rate_plan_id' => 'plan',
            'day' => '2027-03-04',
        ]);
        $calendar->setRelation('roomType', $room);
        $calendar->setRelation('accommodation', $hotel);

        $calendars = Mockery::mock(RoomCalendarRepositoryInterface::class);
        $calendars->shouldReceive('find')->once()->with(5480)->andReturn($calendar);
        $providers = Mockery::mock(ProviderRepositoryInterface::class);
        $providers->shouldReceive('find')->once()->with(3)->andReturn(new Provider([
            'id' => 3, 'code' => 'grs', 'is_active' => true, 'is_online' => $online,
        ]));

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        if ($online) {
            $adapter->shouldReceive('fetchAvailability')->once()->andReturn(new Collection($availability));
        } else {
            $adapter->shouldNotReceive('fetchAvailability');
        }
        $this->app->bind(ProviderAdapterInterface::class, fn () => $adapter);

        return new ReservationCreateValidator($calendars, $providers);
    }

    private function nightly(int $price, int $inventory = 1): array
    {
        return [
            'day' => '2027-03-04',
            'room_type_id' => 'room',
            'rate_plan_id' => 'plan',
            'daily_rate' => $price,
            'grs_rate' => $price,
            'inventory' => $inventory,
            'closed' => false,
        ];
    }

    public function test_price_increase_validates_exact_selected_provider(): void
    {
        $result = $this->validator([$this->nightly(27000000)])->validate($this->requestData());
        $this->assertSame(ReservationStatus::READY_FOR_PAYMENT, $result['status']);
        $this->assertSame(27000000, $result['total']);
        $this->assertSame(['price' => 27000000, 'provider_id' => 3], $result['rooms'][0]);
    }

    public function test_price_decrease_is_returned_for_profit_calculation_without_changing_customer_quote(): void
    {
        $result = $this->validator([$this->nightly(22000000)])->validate($this->requestData());
        $this->assertSame(ReservationStatus::READY_FOR_PAYMENT, $result['status']);
        $this->assertSame(22000000, $result['total']);
    }

    public function test_no_inventory_returns_closed_status(): void
    {
        $result = $this->validator([$this->nightly(25000000, 0)])->validate($this->requestData());
        $this->assertSame(ReservationStatus::NO_AVAILABILITY, $result['status']);
        $this->assertNull($result['total']);
    }

    public function test_offline_provider_never_receives_a_validation_call(): void
    {
        $result = $this->validator([], false)->validate($this->requestData());
        $this->assertSame(ReservationStatus::CHECKED, $result['status']);
        $this->assertNull($result['total']);
    }
}
