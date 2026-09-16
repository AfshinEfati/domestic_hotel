<?php

namespace App\Services\Contracts;

use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationPurchaseSegment;
use App\Models\ReservationRoom;

interface ReservationServiceInterface extends BaseServiceInterface
{
    public function createRequest(array $data): Reservation;

    public function store(mixed $payload): Reservation;

    public function findByReservationNumber(string $reservationNumber): ?Reservation;

    public function changeStatus(int $reservationId, int $status): bool;

    public function addHotel(int $reservationId, int $accommodationId, int $type): ReservationHotel;

    public function setFinalHotel(int $reservationId, int $reservationHotelId): ReservationHotel;

    public function addRoom(int $reservationHotelId, mixed $payload): ReservationRoom;

    public function addPurchaseSegment(int $reservationRoomId, mixed $payload): ReservationPurchaseSegment;
}
