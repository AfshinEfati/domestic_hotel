<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationHotel;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;

class ReservationHotelRepository extends BaseRepository implements ReservationHotelRepositoryInterface
{
    public function __construct(ReservationHotel $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ReservationHotel
    {
        /** @var ReservationHotel|null */
        return parent::find($id);
    }

    public function store(array $data): ReservationHotel
    {
        /** @var ReservationHotel */
        return parent::store($data);
    }

    public function findForReservation(int $reservationId, int $reservationHotelId): ?ReservationHotel
    {
        return $this->model
            ->newQuery()
            ->where('reservation_id', $reservationId)
            ->whereKey($reservationHotelId)
            ->first();
    }

    public function findByReservationAndAccommodation(int $reservationId, int $accommodationId): ?ReservationHotel
    {
        return $this->model
            ->newQuery()
            ->where('reservation_id', $reservationId)
            ->where('accommodation_id', $accommodationId)
            ->first();
    }

    public function clearFinalByReservation(int $reservationId): void
    {
        $this->model
            ->newQuery()
            ->where('reservation_id', $reservationId)
            ->where('is_final', true)
            ->update(['is_final' => false]);
    }

    public function markFinal(int $reservationHotelId): bool
    {
        return $this->model
            ->newQuery()
            ->whereKey($reservationHotelId)
            ->update(['is_final' => true]) === 1;
    }
}
