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
}
