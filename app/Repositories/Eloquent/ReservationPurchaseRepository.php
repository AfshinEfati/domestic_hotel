<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationPurchase;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use Illuminate\Support\Collection;

class ReservationPurchaseRepository extends BaseRepository implements ReservationPurchaseRepositoryInterface
{
    public function __construct(ReservationPurchase $model)
    {
        parent::__construct($model);
    }

    public function store(array $data): ReservationPurchase
    {
        /** @var ReservationPurchase */
        return parent::store($data);
    }

    public function findForReservation(int $reservationId, int $purchaseId): ?ReservationPurchase
    {
        return $this->model
            ->newQuery()
            ->with(['provider', 'quotedProvider', 'segments', 'manualPurchase', 'payments'])
            ->whereKey($purchaseId)
            ->whereHas(
                'reservationHotel',
                fn ($query) => $query->where('reservation_id', $reservationId)
            )
            ->first();
    }

    public function findByHotelAndProvider(int $reservationHotelId, int $providerId): ?ReservationPurchase
    {
        return $this->model->newQuery()
            ->where('reservation_hotel_id', $reservationHotelId)
            ->where('provider_id', $providerId)
            ->with(['segments', 'manualPurchase'])
            ->first();
    }

    public function getByReservationHotel(int $reservationHotelId): Collection
    {
        return $this->model->newQuery()
            ->where('reservation_hotel_id', $reservationHotelId)
            ->with(['provider', 'segments', 'manualPurchase'])
            ->orderBy('id')
            ->get();
    }
}
