<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin Manual Reservation",
 *     description="Admin workflow for offline/manual hotel procurement. All write actions are serialized with a reservation row lock and return the full reservation aggregate."
 * )
 */
class ManualReservationPurchaseDoc
{
    /**
     * @OA\Schema(
     *     schema="ManualPurchasePaymentInput",
     *     type="object",
     *     required={"amount","source","paid_at"},
     *     @OA\Property(property="amount", type="integer", format="int64", minimum=1, example=55000000),
     *     @OA\Property(
     *         property="source",
     *         type="integer",
     *         enum={1,2,3,4},
     *         example=3,
     *         description="Selectable payment source only; it does not change purchase workflow. 1=credit, 2=gateway/bank transfer, 3=card_to_card, 4=cash."
     *     ),
     *     @OA\Property(property="paid_at", type="string", format="date-time", example="2026-09-27T14:30:00+03:30")
     * )
     */
    public function paymentInputSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ManualReservationAggregateRequest",
     *     type="object",
     *     required={"acc_code"},
     *     @OA\Property(
     *         property="acc_code",
     *         type="string",
     *         maxLength=100,
     *         example="A-102",
     *         description="Accounting code of the hotel operator. The hotel service does not manage users or permissions."
     *     ),
     *     @OA\Property(
     *         property="hotel",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="accommodation_id", type="integer", example=15),
     *         @OA\Property(property="select", type="boolean", example=true)
     *     ),
     *     @OA\Property(
     *         property="rooms",
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             required={"room_number"},
     *             @OA\Property(property="reservation_hotel_id", type="integer", nullable=true, example=55),
     *             @OA\Property(property="room_number", type="integer", minimum=1, example=1),
     *             @OA\Property(property="room_type_id", type="integer", nullable=true, example=42),
     *             @OA\Property(property="rate_plan_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="room_name", type="string", nullable=true, example="اتاق دو تخته دلوکس"),
     *             @OA\Property(property="select", type="boolean", example=true),
     *             @OA\Property(
     *                 property="guest_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=101)
     *             )
     *         )
     *     ),
     *     @OA\Property(
     *         property="purchases",
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             required={"id"},
     *             @OA\Property(property="id", type="integer", example=12),
     *             @OA\Property(property="provider_id", type="integer", nullable=true, example=2),
     *             @OA\Property(
     *                 property="use_hotel_as_provider",
     *                 type="boolean",
     *                 example=false,
     *                 description="When true, creates/reuses the direct hotel provider for the final accommodation. Direct hotel providers never enter online rate/capacity/booking flows."
     *             ),
     *             @OA\Property(property="purchase_amount", type="integer", format="int64", nullable=true, minimum=1, example=135000000),
     *             @OA\Property(property="confirmation_code", type="string", nullable=true, example="DR-45821"),
     *             @OA\Property(property="description", type="string", nullable=true, example="رزرو تلفنی تایید شد"),
     *             @OA\Property(
     *                 property="room_ids",
     *                 type="array",
     *                 description="Required to disambiguate room-to-purchase ownership when a reservation has multiple purchases.",
     *                 @OA\Items(type="integer", example=61)
     *             ),
     *             @OA\Property(
     *                 property="payments",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ManualPurchasePaymentInput")
     *             )
     *         )
     *     )
     * )
     */
    public function aggregateInputSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/reservations/{reservation_id}",
     *     summary="Get reservation for admin manual purchase",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Full reservation aggregate",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Reservation not found")
     * )
     */
    public function show(): void
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/reservations/{reservation_id}/manual-purchase",
     *     summary="Apply combined manual purchase changes atomically",
     *     description="Can change/select hotel, change/select rooms, reassign guests, change provider, set actual purchase amount, set confirmation code and register multiple already-paid payments in one database transaction. sale_amount is intentionally not writable.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ManualReservationAggregateRequest")),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Reservation not found"),
     *     @OA\Response(response=422, description="Validation/business rule error")
     * )
     */
    public function updateAggregate(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/hotels",
     *     summary="Add or select an alternative hotel",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code","accommodation_id"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="accommodation_id", type="integer", example=15),
     *             @OA\Property(property="select", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse"))),
     *     @OA\Response(response=422, description="Validation/business rule error")
     * )
     */
    public function addHotel(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/hotels/{reservation_hotel}/select",
     *     summary="Select an existing reservation hotel as final",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reservation_hotel", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"acc_code"}, @OA\Property(property="acc_code", type="string", example="A-102"))),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function selectHotel(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/rooms",
     *     summary="Add or replace an alternative room",
     *     description="If an alternative already exists for the same room_number it is updated rather than creating history versions. Use room_type_id for a known room or room_name for a manually described room.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code","room_number"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="reservation_hotel_id", type="integer", nullable=true, example=55),
     *             @OA\Property(property="room_number", type="integer", example=1),
     *             @OA\Property(property="room_type_id", type="integer", nullable=true, example=42),
     *             @OA\Property(property="rate_plan_id", type="integer", nullable=true),
     *             @OA\Property(property="room_name", type="string", nullable=true, example="سوئیت رویال"),
     *             @OA\Property(property="select", type="boolean", example=true),
     *             @OA\Property(property="guest_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function addRoom(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/rooms/{reservation_room}/select",
     *     summary="Select an existing reservation room as final for its room slot",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reservation_room", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"acc_code"}, @OA\Property(property="acc_code", type="string", example="A-102"))),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function selectRoom(): void
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/reservations/{reservation_id}/guest-assignments",
     *     summary="Replace guest-to-room layout",
     *     description="Every reservation guest must be included exactly once. Guest rows are moved between rooms; they are not duplicated.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code","rooms"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(
     *                 property="rooms",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"room_id","guest_ids"},
     *                     @OA\Property(property="room_id", type="integer", example=61),
     *                     @OA\Property(property="guest_ids", type="array", @OA\Items(type="integer", example=101))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function assignGuests(): void
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/reservations/{reservation_id}/purchases/{reservation_purchase}",
     *     summary="Update manual purchase details",
     *     description="Changes actual procurement data only. provider_quoted_amount remains the original quote and reservation sale_amount is not writable.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reservation_purchase", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="provider_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="use_hotel_as_provider", type="boolean", example=false),
     *             @OA\Property(property="purchase_amount", type="integer", format="int64", nullable=true, minimum=1, example=135000000),
     *             @OA\Property(property="confirmation_code", type="string", nullable=true, example="DR-45821"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="room_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function updatePurchase(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/purchases/{reservation_purchase}/payments",
     *     summary="Record a payment already made for the hotel purchase",
     *     description="The service does not execute payments. It only records amount, payment method and the date/time of a payment that has already happened.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reservation_purchase", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code","amount","source","paid_at"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="amount", type="integer", format="int64", minimum=1, example=55000000),
     *             @OA\Property(property="source", type="integer", enum={1,2,3,4}, example=2),
     *             @OA\Property(property="paid_at", type="string", format="date-time", example="2026-09-27T14:30:00+03:30")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Recorded", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function addPayment(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/confirm",
     *     summary="Confirm manual reservation purchase",
     *     description="Requires exactly one final hotel, final rooms, every guest assigned to a final room, a valid provider for every purchase and purchase_amount on every purchase. With one purchase, final room segments are rebuilt automatically. With multiple purchases, final rooms must already be assigned exactly once across purchase room_ids. Every purchase must have recorded payments totaling at least purchase_amount. If any purchase is underpaid, confirmation returns validation error 422 and no status changes are committed. A successful confirmation sets the purchase and reservation status to ISSUE_SUCCESS (6).",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="description", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Confirmed", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse"))),
     *     @OA\Response(response=422, description="Reservation is not ready to confirm or full purchase payment has not been recorded")
     * )
     */
    public function confirm(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/reservations/{reservation_id}/reject",
     *     summary="Reject the purchase from admin",
     *     description="Sets reservation and purchase status to ISSUE_FAILED (7). Status 7 is an explicit panel rejection and is not produced by online/offline automatic purchase flows.",
     *     tags={"Admin Manual Reservation"},
     *     @OA\Parameter(name="reservation_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"acc_code"},
     *             @OA\Property(property="acc_code", type="string", example="A-102"),
     *             @OA\Property(property="description", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rejected", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")))
     * )
     */
    public function reject(): void
    {
    }
}
