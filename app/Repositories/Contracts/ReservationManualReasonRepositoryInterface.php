<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationManualReason;

interface ReservationManualReasonRepositoryInterface extends BaseRepositoryInterface
{
    public function store(array $data): ReservationManualReason;
}
