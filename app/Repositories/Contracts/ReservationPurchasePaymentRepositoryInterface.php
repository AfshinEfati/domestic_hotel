<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationPurchasePayment;

interface ReservationPurchasePaymentRepositoryInterface extends BaseRepositoryInterface
{
    public function store(array $data): ReservationPurchasePayment;

    public function findForReservation(
        int $reservationId,
        int $purchaseId,
        int $paymentId
    ): ?ReservationPurchasePayment;

    public function sumPaidForPurchase(int $purchaseId): int;
}
