<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationPurchase;
use Illuminate\Support\Collection;

interface ReservationPurchaseRepositoryInterface extends BaseRepositoryInterface
{
    public function store(array $data): ReservationPurchase;

    public function findByHotelAndProvider(int $reservationHotelId, int $providerId): ?ReservationPurchase;

    /** @return Collection<int, ReservationPurchase> */
    public function getByReservationHotel(int $reservationHotelId): Collection;
}
