<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\ReservationHotel;
use App\Models\ReservationRoom;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;
use App\Services\ReservationCreateValidator;
use App\Services\ReservationService;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ReservationCreateTicketTest extends TestCase
{
    public function test_missing_calendar_keeps_ticket_room_and_guests_before_closing(): void
    {
        DB::shouldReceive('transaction')->twice()->andReturnUsing(static fn ($callback) => $callback());

        $ticket = new Reservation();
        $ticket->id = 100;
        $reservations = Mockery::mock(ReservationRepositoryInterface::class);
        $reservations->shouldReceive('store')->once()->with(Mockery::on(static fn (array $row): bool =>
            $row['status'] === ReservationStatus::REQUESTED && $row['sale_amount'] === 25000000
        ))->andReturn($ticket);
        $reservations->shouldReceive('update')->once()->with(100, ['reservation_number' => '100'])->andReturn(true);
        $reservations->shouldReceive('update')->once()->with(100, Mockery::on(static fn (array $row): bool =>
            $row['status'] === ReservationStatus::NO_AVAILABILITY
            && $row['sale_amount'] === 25000000
            && $row['validated_sale_amount'] === null
        ))->andReturn(true);
        $reservations->shouldReceive('findByReservationNumber')->once()->with('100')->andReturn($ticket);

        $hotels = Mockery::mock(ReservationHotelRepositoryInterface::class);
        $hotel = new ReservationHotel();
        $hotel->id = 200;
        $hotels->shouldReceive('store')->once()->with(Mockery::on(static fn (array $row): bool =>
            $row['reservation_id'] === 100 && $row['accommodation_id'] === null
        ))->andReturn($hotel);

        $rooms = Mockery::mock(ReservationRoomRepositoryInterface::class);
        $room = new ReservationRoom();
        $room->id = 300;
        $rooms->shouldReceive('store')->once()->with(Mockery::on(static fn (array $row): bool =>
            $row['reservation_hotel_id'] === 200
            && $row['room_calendar_id'] === 5480
            && $row['room_type_id'] === null
            && $row['rate_plan_id'] === null
            && $row['initial_price'] === 25000000
        ))->andReturn($room);

        $guests = Mockery::mock(ReservationGuestRepositoryInterface::class);
        $guests->shouldReceive('store')->once()->with(Mockery::on(static fn (array $row): bool =>
            $row['reservation_room_id'] === 300
            && $row['country_id'] === 990001
            && $row['national_id'] === '0012345678'
        ))->andReturn(new ReservationGuest());

        $calendarService = Mockery::mock(RoomCalendarServiceInterface::class);
        $calendarService->shouldReceive('show')->once()->with(5480)->andReturn(null);
        $validator = Mockery::mock(ReservationCreateValidator::class);
        $validator->shouldReceive('validate')->once()->andReturn([
            'status' => ReservationStatus::NO_AVAILABILITY,
            'error' => 'Selected calendar is no longer available.',
            'total' => null,
            'rooms' => [],
        ]);

        $service = new ReservationService(
            $reservations, $hotels, $rooms, $guests,
            Mockery::mock(ReservationPurchaseSegmentRepositoryInterface::class),
            $calendarService, $validator
        );
        $created = $service->createRequest([
            'agency_id' => 1,
            'check_in' => '2027-03-04',
            'check_out' => '2027-03-05',
            'first_name' => 'Afshin',
            'last_name' => 'Efati',
            'mobile' => '09120000000',
            'hotel' => ['rooms' => [[
                'room_calendar_id' => 5480,
                'price' => 25000000,
                'guests' => [[
                    'type' => 1,
                    'first_name' => 'Ali',
                    'last_name' => 'Ahmadi',
                    'country_id' => 990001,
                    'national_id' => '0012345678',
                ]],
            ]]],
        ]);

        $this->assertSame(100, $created->id);
    }
}
