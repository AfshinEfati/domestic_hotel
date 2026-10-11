<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\StatusHelper;
use App\Http\Resources\ReservationResource;
use App\Services\Contracts\OnlineReservationPurchaseRecoveryServiceInterface;
use Illuminate\Http\JsonResponse;

final class OnlineReservationPurchaseController
{
    public function __construct(
        private readonly OnlineReservationPurchaseRecoveryServiceInterface $service,
    ) {
    }

    public function retry(int $reservationId, int $reservationPurchase): JsonResponse
    {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->retry($reservationId, $reservationPurchase)
            ),
            'online purchase retry queued',
        );
    }

    public function convertToManual(int $reservationId, int $reservationPurchase): JsonResponse
    {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->convertToManual($reservationId, $reservationPurchase)
            ),
            'purchase converted to manual',
        );
    }
}
