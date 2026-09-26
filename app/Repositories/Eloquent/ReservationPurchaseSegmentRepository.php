<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationPurchaseSegment;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;

class ReservationPurchaseSegmentRepository extends BaseRepository implements ReservationPurchaseSegmentRepositoryInterface
{
    public function __construct(ReservationPurchaseSegment $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ReservationPurchaseSegment
    {
        /** @var ReservationPurchaseSegment|null */
        return parent::find($id);
    }

    public function store(array $data): ReservationPurchaseSegment
    {
        /** @var ReservationPurchaseSegment */
        return parent::store($data);
    }

    public function firstOrCreateForPurchaseRoom(
        int $reservationPurchaseId,
        int $reservationRoomId,
        string $fromDate,
        string $toDate,
    ): ReservationPurchaseSegment {
        /** @var ReservationPurchaseSegment */
        return $this->model->newQuery()->firstOrCreate([
            'reservation_purchase_id' => $reservationPurchaseId,
            'reservation_room_id' => $reservationRoomId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }
}
