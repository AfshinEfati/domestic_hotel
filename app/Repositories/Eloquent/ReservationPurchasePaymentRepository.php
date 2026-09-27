<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationPurchasePayment;
use App\Repositories\Contracts\ReservationPurchasePaymentRepositoryInterface;

class ReservationPurchasePaymentRepository extends BaseRepository implements ReservationPurchasePaymentRepositoryInterface
{
    public function __construct(ReservationPurchasePayment $model)
    {
        parent::__construct($model);
    }

    public function store(array $data): ReservationPurchasePayment
    {
        /** @var ReservationPurchasePayment */
        return parent::store($data);
    }

    public function findForReservation(
        int $reservationId,
        int $purchaseId,
        int $paymentId
    ): ?ReservationPurchasePayment {
        return $this->model
            ->newQuery()
            ->whereKey($paymentId)
            ->where('reservation_purchase_id', $purchaseId)
            ->whereHas(
                'reservationPurchase.reservationHotel',
                fn ($query) => $query->where('reservation_id', $reservationId)
            )
            ->first();
    }

    public function sumPaidForPurchase(int $purchaseId): int
    {
        return (int) $this->model
            ->newQuery()
            ->where('reservation_purchase_id', $purchaseId)
            ->sum('amount');
    }
}
