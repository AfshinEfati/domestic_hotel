<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ManualReservationPurchaseDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\ManualReservationActionRequest;
use App\Http\Requests\StoreManualReservationHotelRequest;
use App\Http\Requests\StoreManualReservationRoomRequest;
use App\Http\Requests\StoreReservationPurchasePaymentRequest;
use App\Http\Requests\UpdateManualGuestAssignmentsRequest;
use App\Http\Requests\UpdateManualReservationPurchaseRecordRequest;
use App\Http\Requests\UpdateManualReservationPurchaseRequest;
use App\Http\Resources\ReservationResource;
use App\Services\Contracts\ManualReservationPurchaseServiceInterface;
use Illuminate\Http\JsonResponse;

class ManualReservationPurchaseController
{
    public function __construct(
        public ManualReservationPurchaseServiceInterface $service
    ) {}

    public function show(int $reservationId): JsonResponse
    {
        return StatusHelper::successResponse(
            new ReservationResource($this->service->show($reservationId))
        );
    }

    public function update(
        UpdateManualReservationPurchaseRequest $request,
        int $reservationId
    ): JsonResponse {
        $reservation = $this->service->update(
            $reservationId,
            ManualReservationPurchaseDTO::fromRequest($request)->toArray()
        );

        return StatusHelper::successResponse(
            new ReservationResource($reservation),
            'updated'
        );
    }

    public function storeHotel(
        StoreManualReservationHotelRequest $request,
        int $reservationId
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->addHotel($reservationId, $request->validated())
            ),
            'updated'
        );
    }

    public function selectHotel(
        ManualReservationActionRequest $request,
        int $reservationId,
        int $reservationHotel
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->selectHotel(
                    $reservationId,
                    $reservationHotel,
                    (string) $request->validated('acc_code')
                )
            ),
            'updated'
        );
    }

    public function storeRoom(
        StoreManualReservationRoomRequest $request,
        int $reservationId
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->addRoom($reservationId, $request->validated())
            ),
            'updated'
        );
    }

    public function selectRoom(
        ManualReservationActionRequest $request,
        int $reservationId,
        int $reservationRoom
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->selectRoom(
                    $reservationId,
                    $reservationRoom,
                    (string) $request->validated('acc_code')
                )
            ),
            'updated'
        );
    }

    public function assignGuests(
        UpdateManualGuestAssignmentsRequest $request,
        int $reservationId
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->assignGuests($reservationId, $request->validated())
            ),
            'updated'
        );
    }

    public function updatePurchase(
        UpdateManualReservationPurchaseRecordRequest $request,
        int $reservationId,
        int $reservationPurchase
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->updatePurchase(
                    $reservationId,
                    $reservationPurchase,
                    $request->validated()
                )
            ),
            'updated'
        );
    }

    public function storePayment(
        StoreReservationPurchasePaymentRequest $request,
        int $reservationId,
        int $reservationPurchase
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->addPayment(
                    $reservationId,
                    $reservationPurchase,
                    $request->validated()
                )
            ),
            'created',
            201
        );
    }

    public function confirm(
        ManualReservationActionRequest $request,
        int $reservationId
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->confirm($reservationId, $request->validated())
            ),
            'confirmed'
        );
    }

    public function reject(
        ManualReservationActionRequest $request,
        int $reservationId
    ): JsonResponse {
        return StatusHelper::successResponse(
            new ReservationResource(
                $this->service->reject($reservationId, $request->validated())
            ),
            'rejected'
        );
    }
}
