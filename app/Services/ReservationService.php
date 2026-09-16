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
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use App\Services\Contracts\ReservationServiceInterface;
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
        private readonly ReservationReferenceGeneratorInterface $referenceGenerator,
    ) {
        parent::__construct($reservationRepository);
    }

    /**
     * @throws Throwable
     */
    public function createRequest(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            $reservation = $this->reservationRepository->store([
                'reservation_number' => $this->referenceGenerator->generate(),
                'agency_id' => $data['agency_id'],
                'status' => ReservationStatus::REQUESTED,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'sale_amount' => $data['sale_amount'],
                'tax_amount' => 0,
                'commission_amount' => null,
                'booker_first_name' => $data['booker']['first_name'],
                'booker_last_name' => $data['booker']['last_name'],
                'booker_mobile' => $data['booker']['mobile'],
                'booker_email' => $data['booker']['email'] ?? null,
                'acc_code' => $data['acc_code'] ?? null,
            ]);

            $hotel = $this->reservationHotelRepository->store([
                'reservation_id' => $reservation->id,
                'accommodation_id' => $data['hotel']['accommodation_id'],
                'type' => ReservationHotelType::REQUESTED,
                'is_final' => true,
            ]);

            foreach ($data['hotel']['rooms'] as $index => $roomData) {
                $room = $this->reservationRoomRepository->store([
                    'reservation_hotel_id' => $hotel->id,
                    'room_number' => $index + 1,
                    'type' => ReservationRoomType::REQUESTED,
                    'is_final' => true,
                    'room_type_id' => $roomData['room_type_id'] ?? null,
                    'rate_plan_id' => $roomData['rate_plan_id'] ?? null,
                    'room_name' => $roomData['room_name'] ?? null,
                ]);

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

            return $this->reservationRepository->findByReservationNumber($reservation->reservation_number)
                ?? $reservation;
        });
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

        $data['reservation_number'] = $this->referenceGenerator->generate();
        $data['status'] ??= ReservationStatus::REQUESTED;

        return $this->reservationRepository->store($data);
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

        return $this->reservationRepository->update($reservationId, [
            'status' => $status,
        ]);
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

    /**
     * @throws Throwable
     */
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
