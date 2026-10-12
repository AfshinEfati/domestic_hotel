<?php

namespace Tests\Unit;

use App\Domain\Hotel\Repositories\ProviderStayPackageRepository;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomNightRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;
use App\Services\ReservationCreateValidator;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReservationSingleProviderSelectionTest extends TestCase
{
    public function test_multiple_rooms_from_same_provider_are_accepted(): void
    {
        $calendars = $this->createMock(RoomCalendarServiceInterface::class);
        $calendars->expects($this->exactly(2))
            ->method('show')
            ->willReturnMap([
                [101, $this->calendar(101, 7)],
                [202, $this->calendar(202, 7)],
            ]);

        $method = new ReflectionMethod($this->service($calendars), 'assertSingleProviderSelection');
        $method->invoke($this->service($calendars), $this->payload(101, 202));

        $this->addToAssertionCount(1);
    }

    public function test_rooms_from_different_providers_are_rejected(): void
    {
        $calendars = $this->createMock(RoomCalendarServiceInterface::class);
        $calendars->expects($this->exactly(2))
            ->method('show')
            ->willReturnMap([
                [101, $this->calendar(101, 7)],
                [202, $this->calendar(202, 9)],
            ]);

        $service = $this->service($calendars);
        $method = new ReflectionMethod($service, 'assertSingleProviderSelection');

        try {
            $method->invoke($service, $this->payload(101, 202));
            $this->fail('Mixed-provider reservation selection was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['All selected rooms in one reservation must belong to the same provider.'],
                $exception->errors()['hotel.rooms'] ?? null,
            );
        }
    }

    public function test_room_without_provider_is_rejected(): void
    {
        $calendars = $this->createMock(RoomCalendarServiceInterface::class);
        $calendars->expects($this->once())
            ->method('show')
            ->with(101)
            ->willReturn($this->calendar(101, null));

        $service = $this->service($calendars);
        $method = new ReflectionMethod($service, 'assertSingleProviderSelection');

        try {
            $method->invoke($service, $this->payload(101));
            $this->fail('Reservation room without provider was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Selected room does not have a valid provider.'],
                $exception->errors()['hotel.rooms.0.calendar'] ?? null,
            );
        }
    }

    private function service(RoomCalendarServiceInterface $calendars): ReservationService
    {
        $validator = new ReservationCreateValidator(
            $this->createMock(RoomCalendarRepositoryInterface::class),
            $this->createMock(ProviderRepositoryInterface::class),
            $this->createMock(ProviderStayPackageRepository::class),
        );

        return new ReservationService(
            $this->createMock(ReservationRepositoryInterface::class),
            $this->createMock(ReservationHotelRepositoryInterface::class),
            $this->createMock(ReservationRoomRepositoryInterface::class),
            $this->createMock(ReservationRoomNightRepositoryInterface::class),
            $this->createMock(ReservationGuestRepositoryInterface::class),
            $this->createMock(ReservationPurchaseSegmentRepositoryInterface::class),
            $calendars,
            $validator,
        );
    }

    private function calendar(int $id, ?int $providerId): RoomCalendar
    {
        return new RoomCalendar([
            'id' => $id,
            'provider_id' => $providerId,
        ]);
    }

    /** @return array<string,mixed> */
    private function payload(int ...$calendarIds): array
    {
        return [
            'check_in' => '2026-10-20',
            'hotel' => [
                'rooms' => array_map(
                    static fn (int $calendarId): array => [
                        'calendar' => [[
                            'date' => '2026-10-20',
                            'calendar_id' => $calendarId,
                        ]],
                    ],
                    $calendarIds,
                ),
            ],
        ];
    }
}
