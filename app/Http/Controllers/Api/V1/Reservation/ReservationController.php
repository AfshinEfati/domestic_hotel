<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Helpers\StatusHelper;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\Contracts\ReservationServiceInterface;
use Illuminate\Http\JsonResponse;

class ReservationController
{
    public function __construct(
        public ReservationServiceInterface $service
    ) {}

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->service->createRequest($request->validated());

        return StatusHelper::successResponse(
            new ReservationResource($reservation),
            'reservation created',
            201
        );
    }
    public function show(string $reservationNumber): JsonResponse
    {
        $reservation = $this->service
            ->findByReservationNumber($reservationNumber);

        if (!$reservation) {
            return StatusHelper::notFound(
                'reservation not found'
            );
        }

        return StatusHelper::successResponse(
            new ReservationResource($reservation),
            'reservation detail'
        );
    }
}
