<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationPurchaseSegment;

interface ReservationPurchaseSegmentRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationPurchaseSegment;

    public function store(array $data): ReservationPurchaseSegment;

    public function firstOrCreateForPurchaseRoom(
        int $reservationPurchaseId,
        int $reservationRoomId,
        string $fromDate,
        string $toDate,
    ): ReservationPurchaseSegment;
}
