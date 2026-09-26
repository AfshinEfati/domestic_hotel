<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Helpers\StatusHelper;
use App\Http\Resources\ReservationResource;
use App\Services\Contracts\ReservationPurchaseRequestServiceInterface;
use Illuminate\Http\JsonResponse;

final class ReservationPurchaseController
{
    public function __construct(
        private readonly ReservationPurchaseRequestServiceInterface $service
    ) {}

    public function store(string $reservationNumber): JsonResponse
    {
        $reservation = $this->service->request($reservationNumber);

        return StatusHelper::successResponse(
            new ReservationResource($reservation),
            'purchase request accepted',
        );
    }
}
