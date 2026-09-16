<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationManualReason;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;

class ReservationManualReasonRepository extends BaseRepository implements ReservationManualReasonRepositoryInterface
{
    public function __construct(ReservationManualReason $model)
    {
        parent::__construct($model);
    }

    public function store(array $data): ReservationManualReason
    {
        /** @var ReservationManualReason */
        return parent::store($data);
    }
}
