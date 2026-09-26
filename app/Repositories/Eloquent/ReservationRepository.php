<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationRepository extends BaseRepository implements ReservationRepositoryInterface
{
    public function __construct(Reservation $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?Reservation
    {
        /** @var Reservation|null */
        return parent::find($id);
    }

    public function store(array $data): Reservation
    {
        /** @var Reservation */
        return parent::store($data);
    }

    public function findByReservationNumber(string $reservationNumber): ?Reservation
    {
        return $this->model
            ->newQuery()
            ->with([
                'hotels.accommodation',
                'hotels.rooms.roomType',
                'hotels.rooms.ratePlan',
                'hotels.rooms.nights',
                'hotels.rooms.guests.country',
                'hotels.rooms.guests.passportIssuerCountry',
                'hotels.purchases.provider',
                'hotels.purchases.quotedProvider',
                'hotels.purchases.segments',
                'hotels.purchases.manualPurchase',
                'hotels.purchases.payments',
            ])
            ->where('reservation_number', $reservationNumber)
            ->first();
    }

    public function existsByReservationNumber(string $reservationNumber): bool
    {
        return $this->model
            ->newQuery()
            ->where('reservation_number', $reservationNumber)
            ->exists();
    }

    public function findForUpdate(int $id): ?Reservation
    {
        return $this->model
            ->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function findByReservationNumberForPurchase(
        string $reservationNumber,
        bool $lockForUpdate = false
    ): ?Reservation {
        $query = $this->model
            ->newQuery()
            ->with([
                'hotels.rooms',
                'hotels.purchases.provider',
                'hotels.purchases.quotedProvider',
                'hotels.purchases.segments',
                'hotels.purchases.manualPurchase',
                'hotels.purchases.payments',
            ])
            ->where('reservation_number', $reservationNumber);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
