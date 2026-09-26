<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationManualPurchase;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;

class ReservationManualPurchaseRepository extends BaseRepository implements ReservationManualPurchaseRepositoryInterface
{
    public function __construct(ReservationManualPurchase $model)
    {
        parent::__construct($model);
    }

    public function firstOrCreateForPurchase(int $reservationPurchaseId): ReservationManualPurchase
    {
        /** @var ReservationManualPurchase */
        return $this->model->newQuery()->firstOrCreate([
            'reservation_purchase_id' => $reservationPurchaseId,
        ]);
    }
}
