<?php

namespace App\Services;

use App\DTOs\ReservationDTO;
use App\DTOs\ReservationPurchaseSegmentDTO;
use App\DTOs\ReservationRoomDTO;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationPurchaseSegment;
use App\Models\ReservationRoom;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Services\Contracts\ReservationServiceInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationRoomType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ReservationService extends BaseService implements ReservationServiceInterface
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationHotelRepositoryInterface $reservationHotelRepository,
        private readonly ReservationRoomRepositoryInterface $reservationRoomRepository,
        private readonly ReservationGuestRepositoryInterface $reservationGuestRepository,
        private readonly ReservationPurchaseSegmentRepositoryInterface $purchaseSegmentRepository,
        private readonly RoomCalendarServiceInterface $calendarService,
        private readonly ReservationCreateValidator $createValidator,
    ) {
        parent::__construct($reservationRepository);
    }

    /**
     * Commit the ticket with status 1 before any provider request. Invalid prices,
     * offline providers and network failures never erase a successfully created ticket.
     * This endpoint does not reserve, pay for, or book inventory with a provider.
     * @throws Throwable
     */
    public function createRequest(array $data): Reservation
    {
        $originalTotal = array_sum(array_map(
            static fn (array $room): int => (int) $room['price'], $data['hotel']['rooms']
        ));
        $roomIds = [];

        $reservation = DB::transaction(function () use ($data, $originalTotal, &$roomIds): Reservation {
            $reservation = $this->reservationRepository->store([
                'agency_id' => $data['agency_id'],
                'status' => ReservationStatus::REQUESTED,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'sale_amount' => $originalTotal,
                'initial_sale_amount' => $originalTotal,
                'tax_amount' => 0,
                'commission_amount' => null,
                'booker_first_name' => $data['first_name'],
                'booker_last_name' => $data['last_name'],
                'booker_mobile' => $data['mobile'],
                'booker_email' => $data['email'] ?? null,
                'acc_code' => null,
            ]);
            // Keep legacy integrations working without inventing another reservation ID.
            $this->reservationRepository->update($reservation->id, [
                'reservation_number' => (string) $reservation->id,
            ]);

            $hotel = $this->reservationHotelRepository->store([
                'reservation_id' => $reservation->id,
                'accommodation_id' => $data['hotel']['accommodation_id'],
                'type' => ReservationHotelType::REQUESTED,
                'is_final' => true,
            ]);

            foreach ($data['hotel']['rooms'] as $index => $roomData) {
                $calendar = $this->calendarService->show((int) $roomData['room_calendar_id']);
                $matches = $calendar !== null
                    && (int) $calendar->accommodation_id === (int) $data['hotel']['accommodation_id']
                    && $calendar->day?->toDateString() === $data['check_in'];
                $room = $this->reservationRoomRepository->store([
                    'reservation_hotel_id' => $hotel->id,
                    'room_number' => $index + 1,
                    'type' => ReservationRoomType::REQUESTED,
                    'is_final' => true,
                    'room_calendar_id' => (int) $roomData['room_calendar_id'],
                    'provider_id' => $matches ? (int) $calendar->provider_id : null,
                    'room_type_id' => $matches ? $calendar->room_type_id : null,
                    'rate_plan_id' => $matches ? $calendar->rate_plan_id : null,
                    'room_name' => $matches ? $calendar->roomType?->fa_name : null,
                    'initial_price' => (int) $roomData['price'],
                ]);
                $roomIds[$index] = $room->id;

                foreach ($roomData['guests'] as $guestData) {
                    $this->reservationGuestRepository->store([
                        'reservation_room_id' => $room->id,
                        'type' => $guestData['type'],
                        'first_name' => $guestData['first_name'],
                        'last_name' => $guestData['last_name'],
                        'gender' => $guestData['gender'] ?? null,
                        'birth_date' => $guestData['birth_date'] ?? null,
                        'country_id' => $guestData['country_id'] ?? null,
                        'national_id' => $guestData['national_id'] ?? null,
                        'passport_number' => $guestData['passport_number'] ?? null,
                        'passport_issuer_country_id' => $guestData['passport_issuer_country_id'] ?? null,
                        'passport_expiry_date' => $guestData['passport_expiry_date'] ?? null,
                    ]);
                }
            }
            return $reservation;
        });

        // Network work is OUTSIDE the insert transaction. Status 2 means a check
        // finished without success, not merely that a request was dispatched.
        try {
            $check = $this->createValidator->validate($data);
        } catch (Throwable $exception) {
            report($exception);
            $check = [
                'status' => ReservationStatus::CHECKED,
                'error' => 'Price validation could not be completed.',
                'total' => null,
                'rooms' => [],
            ];
        }

        DB::transaction(function () use ($reservation, $check, $roomIds, $originalTotal): void {
            foreach ($check['rooms'] as $index => $room) {
                $this->reservationRoomRepository->update($roomIds[$index], [
                    'validated_price' => $room['price'],
                    'provider_id' => $room['provider_id'],
                ]);
            }
            $validated = $check['total'];
            $this->reservationRepository->update($reservation->id, [
                'status' => $check['status'],
                'validation_error' => $check['error'],
                'validated_sale_amount' => $validated,
                'sale_amount' => $validated === null ? $originalTotal : max($originalTotal, $validated),
            ]);
        });

        return $this->reservationRepository->findByReservationNumber((string) $reservation->id)
            ?? $this->reservationRepository->find($reservation->id)
            ?? $reservation;
    }

    public function store(mixed $payload): Reservation
    {
        $data = $payload instanceof ReservationDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);
        unset($data['reservation_number']);
        if (isset($data['status']) && !ReservationStatus::isValid((int) $data['status'])) {
            throw new InvalidArgumentException('Invalid reservation status.');
        }
        $data['status'] ??= ReservationStatus::REQUESTED;
        return DB::transaction(function () use ($data): Reservation {
            $reservation = $this->reservationRepository->store($data);
            $this->reservationRepository->update($reservation->id, [
                'reservation_number' => (string) $reservation->id,
            ]);
            return $this->reservationRepository->find($reservation->id) ?? $reservation;
        });
    }

    public function findByReservationNumber(string $reservationNumber): ?Reservation
    {
        return $this->reservationRepository->findByReservationNumber($reservationNumber);
    }

    public function changeStatus(int $reservationId, int $status): bool
    {
        if (!ReservationStatus::isValid($status)) {
            throw new InvalidArgumentException('Invalid reservation status.');
        }
        return $this->reservationRepository->update($reservationId, ['status' => $status]);
    }

    public function addHotel(
        int $reservationId,
        int $accommodationId,
        int $type = ReservationHotelType::REQUESTED
    ): ReservationHotel {
        if (!ReservationHotelType::isValid($type)) {
            throw new InvalidArgumentException('Invalid reservation hotel type.');
        }
        if ($this->reservationRepository->find($reservationId) === null) {
            throw new InvalidArgumentException('Reservation not found.');
        }
        $existing = $this->reservationHotelRepository
            ->findByReservationAndAccommodation($reservationId, $accommodationId);
        if ($existing !== null) {
            return $existing;
        }
        return $this->reservationHotelRepository->store([
            'reservation_id' => $reservationId,
            'accommodation_id' => $accommodationId,
            'type' => $type,
            'is_final' => false,
        ]);
    }

    /** @throws Throwable */
    public function setFinalHotel(int $reservationId, int $reservationHotelId): ReservationHotel
    {
        return DB::transaction(function () use ($reservationId, $reservationHotelId): ReservationHotel {
            if ($this->reservationRepository->findForUpdate($reservationId) === null) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            $hotel = $this->reservationHotelRepository
                ->findForReservation($reservationId, $reservationHotelId);
            if ($hotel === null) {
                throw new InvalidArgumentException('Reservation hotel not found.');
            }
            $this->reservationHotelRepository->clearFinalByReservation($reservationId);
            if (!$this->reservationHotelRepository->markFinal($reservationHotelId)) {
                throw new RuntimeException('Unable to set final reservation hotel.');
            }
            return $this->reservationHotelRepository
                ->findForReservation($reservationId, $reservationHotelId)
                ?? throw new RuntimeException('Final reservation hotel could not be loaded.');
        });
    }

    public function addRoom(int $reservationHotelId, mixed $payload): ReservationRoom
    {
        if ($this->reservationHotelRepository->find($reservationHotelId) === null) {
            throw new InvalidArgumentException('Reservation hotel not found.');
        }
        $data = $payload instanceof ReservationRoomDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);
        $data['reservation_hotel_id'] = $reservationHotelId;
        return $this->reservationRoomRepository->store($data);
    }

    public function addPurchaseSegment(int $reservationRoomId, mixed $payload): ReservationPurchaseSegment
    {
        if ($this->reservationRoomRepository->find($reservationRoomId) === null) {
            throw new InvalidArgumentException('Reservation room not found.');
        }
        $data = $payload instanceof ReservationPurchaseSegmentDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);
        $method = (int) ($data['purchase_method'] ?? PurchaseMethod::ONLINE);
        if (!PurchaseMethod::isValid($method)) {
            throw new InvalidArgumentException('Invalid purchase method.');
        }
        $data['reservation_room_id'] = $reservationRoomId;
        $data['purchase_method'] = $method;
        return $this->purchaseSegmentRepository->store($data);
    }
}
