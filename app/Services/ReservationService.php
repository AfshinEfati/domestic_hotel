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
use App\Repositories\Contracts\ReservationRoomNightRepositoryInterface;
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
        private readonly ReservationRoomNightRepositoryInterface $reservationRoomNightRepository,
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
        // Local room/hotel/guest compatibility is a hard validation boundary.
        // It runs before the first INSERT, while provider validation remains a separate
        // post-create operational check for the agents' purchase workflow.
        $this->createValidator->validateGuestSelection($data);

        $originalTotal = (int) $data['expected_total_price'];
        $accommodationId = (int) $data['hotel']['accommodation_id'];
        $roomIds = [];
        // date => calendar_id => night-row id, per room index; used to update validated_price per night.
        $nightIdsByRoom = [];

        $reservation = DB::transaction(function () use (
            $data, $originalTotal, $accommodationId, &$roomIds, &$nightIdsByRoom
        ): Reservation {
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
            // Compatibility for existing internal consumers; the public identifier is always reservations.id.
            $this->reservationRepository->update($reservation->id, [
                'reservation_number' => (string) $reservation->id,
            ]);

            // Nullable accommodation preserves the hotel, room and guest snapshot even when
            // every selected calendar has been pruned since Availability was shown.
            $hotel = $this->reservationHotelRepository->store([
                'reservation_id' => $reservation->id,
                'accommodation_id' => $accommodationId,
                'type' => ReservationHotelType::REQUESTED,
                'is_final' => true,
            ]);

            foreach ($data['hotel']['rooms'] as $index => $roomData) {
                // The first calendar entry belonging to check_in is the snapshot that used
                // to be the sole room_calendar_id; it still identifies the room/rate/provider.
                $firstNight = collect($roomData['calendar'])
                    ->firstWhere('date', $data['check_in']);
                $firstCalendarId = (int) ($firstNight['calendar_id'] ?? $roomData['calendar'][0]['calendar_id']);
                $calendar = $this->calendarService->show($firstCalendarId);
                $matches = $calendar !== null
                    && (int) $calendar->accommodation_id === $accommodationId
                    && $calendar->day?->toDateString() === $data['check_in'];

                $room = $this->reservationRoomRepository->store([
                    'reservation_hotel_id' => $hotel->id,
                    'room_number' => $roomData['room_number'],
                    'type' => ReservationRoomType::REQUESTED,
                    'is_final' => true,
                    'room_calendar_id' => $firstCalendarId,
                    'provider_id' => $matches ? (int) $calendar->provider_id : null,
                    'room_type_id' => $matches ? $calendar->room_type_id : null,
                    'rate_plan_id' => $matches ? $calendar->rate_plan_id : null,
                    'room_name' => $matches ? $calendar->roomType?->fa_name : null,
                    'initial_price' => (int) $roomData['expected_total_price'],
                ]);
                $roomIds[$index] = $room->id;

                $nightIdsByRoom[$index] = [];
                foreach ($roomData['calendar'] as $night) {
                    $nightRow = $this->reservationRoomNightRepository->store([
                        'reservation_room_id' => $room->id,
                        'date' => $night['date'],
                        'room_calendar_id' => (int) $night['calendar_id'],
                        'initial_price' => (int) $night['expected_price'],
                    ]);
                    $nightIdsByRoom[$index][$night['date']] = $nightRow->id;
                }

                foreach ($roomData['guests'] as $guestData) {
                    $this->reservationGuestRepository->store([
                        'reservation_room_id' => $room->id,
                        'type' => $guestData['type'],
                        'first_name' => $guestData['first_name'],
                        'last_name' => $guestData['last_name'],
                        'gender' => $guestData['gender'] ?? null,
                        // The API contract receives birthday. Keep the immutable
                        // guest snapshot; pricing never trusts the submitted age/type.
                        'birth_date' => $guestData['birthday'] ?? $guestData['birth_date'] ?? null,
                        'country_id' => $guestData['country_id'],
                        'national_id' => $guestData['national_id'] ?? null,
                        'passport_number' => $guestData['passport_number'] ?? null,
                        'passport_issuer_country_id' => $guestData['passport_issuer_country_id'] ?? null,
                        'passport_expiry_date' => $guestData['passport_expiry_date'] ?? null,
                    ]);
                }
            }
            return $reservation;
        });

        // No external provider call is made while the initial database transaction is open.
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

        DB::transaction(function () use ($reservation, $check, $roomIds, $nightIdsByRoom, $originalTotal): void {
            foreach ($check['rooms'] as $index => $room) {
                $this->reservationRoomRepository->update($roomIds[$index], [
                    'validated_price' => $room['price'],
                    'provider_id' => $room['provider_id'],
                ]);
                foreach ($room['nights'] as $night) {
                    $nightId = $nightIdsByRoom[$index][$night['date']] ?? null;
                    if ($nightId !== null) {
                        $this->reservationRoomNightRepository->update($nightId, [
                            'validated_price' => $night['price'],
                        ]);
                    }
                }
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
