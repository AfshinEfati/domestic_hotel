<?php

namespace App\Services;

use App\DTOs\ReservationDTO;
use App\DTOs\ReservationPurchaseSegmentDTO;
use App\DTOs\ReservationRoomDTO;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationPurchaseSegment;
use App\Models\ReservationRoom;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use App\Services\Contracts\ReservationServiceInterface;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ReservationService extends BaseService implements ReservationServiceInterface
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationHotelRepositoryInterface $reservationHotelRepository,
        private readonly ReservationRoomRepositoryInterface $reservationRoomRepository,
        private readonly ReservationPurchaseSegmentRepositoryInterface $purchaseSegmentRepository,
        private readonly ReservationReferenceGeneratorInterface $referenceGenerator,
    ) {
        parent::__construct($reservationRepository);
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
