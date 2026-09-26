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
            ->with($this->detailRelations())
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

    public function findWithDetails(int $reservationId): ?Reservation
    {
        return $this->model
            ->newQuery()
            ->with($this->detailRelations())
            ->whereKey($reservationId)
            ->first();
    }

    public function findForPurchase(int $reservationId, bool $lockForUpdate = false): ?Reservation
    {
        $query = $this->model
            ->newQuery()
            ->with($this->detailRelations())
            ->whereKey($reservationId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function detailRelations(): array
    {
        return [
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
        ];
    }
}
