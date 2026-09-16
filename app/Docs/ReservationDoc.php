<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Reservation",
 *     description="Reservation requests received from the upstream Main Backend. Creating a reservation only persists the request aggregate; purchase mode resolution and provider calls happen in later stages."
 * )
 */
class ReservationDoc
{
    /**
     * @OA\Post(
     *     path="/api/v1/reservations",
     *     summary="Create reservation request",
     *     tags={"Reservation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"agency_id","check_in","check_out","sale_amount","booker","hotel"},
     *             @OA\Property(property="agency_id", type="integer", example=15),
     *             @OA\Property(property="check_in", type="string", format="date", example="2026-10-10"),
     *             @OA\Property(property="check_out", type="string", format="date", example="2026-10-13"),
     *             @OA\Property(property="sale_amount", type="integer", format="int64", example=350000000, description="Final sale amount in IRR"),
     *             @OA\Property(property="acc_code", type="string", nullable=true, example="AG-1001"),
     *             @OA\Property(
     *                 property="booker",
     *                 type="object",
     *                 required={"first_name","last_name","mobile"},
     *                 @OA\Property(property="first_name", type="string", example="Afshin"),
     *                 @OA\Property(property="last_name", type="string", example="Efati"),
     *                 @OA\Property(property="mobile", type="string", example="09120000000"),
     *                 @OA\Property(property="email", type="string", format="email", nullable=true, example="guest@example.com")
     *             ),
     *             @OA\Property(
     *                 property="hotel",
     *                 type="object",
     *                 required={"accommodation_id","rooms"},
     *                 @OA\Property(property="accommodation_id", type="integer", example=123),
     *                 @OA\Property(
     *                     property="rooms",
     *                     type="array",
     *                     minItems=1,
     *                     @OA\Items(
     *                         type="object",
     *                         required={"guests"},
     *                         @OA\Property(property="room_type_id", type="integer", nullable=true, example=22),
     *                         @OA\Property(property="rate_plan_id", type="integer", nullable=true, example=7),
     *                         @OA\Property(property="room_name", type="string", nullable=true, example="Double Room"),
     *                         @OA\Property(
     *                             property="guests",
     *                             type="array",
     *                             minItems=1,
     *                             @OA\Items(
     *                                 type="object",
     *                                 required={"type","first_name","last_name"},
     *                                 @OA\Property(property="type", type="integer", enum={1,2,3}, example=1, description="1 adult, 2 child, 3 infant"),
     *                                 @OA\Property(property="first_name", type="string", example="Ali"),
     *                                 @OA\Property(property="last_name", type="string", example="Ahmadi"),
     *                                 @OA\Property(property="gender", type="integer", nullable=true, enum={1,2}, example=1),
     *                                 @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-01-15"),
     *                                 @OA\Property(property="country_id", type="integer", nullable=true, example=1),
     *                                 @OA\Property(property="national_id", type="string", nullable=true, example="0012345678"),
     *                                 @OA\Property(property="passport_number", type="string", nullable=true, example="P12345678"),
     *                                 @OA\Property(property="passport_issuer_country_id", type="integer", nullable=true, example=2),
     *                                 @OA\Property(property="passport_expiry_date", type="string", format="date", nullable=true, example="2030-01-15")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reservation request created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="reservation created"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reservation_number", type="string", example="DH-20260916-A1B2C3D4E5"),
     *                 @OA\Property(property="agency_id", type="integer", example=15),
     *                 @OA\Property(
     *                     property="status",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="requested"),
     *                     @OA\Property(property="fa_name", type="string", example="درخواست رزرو"),
     *                     @OA\Property(property="code", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="check_in", type="string", format="date", example="2026-10-10"),
     *                 @OA\Property(property="check_out", type="string", format="date", example="2026-10-13"),
     *                 @OA\Property(property="sale_amount", type="integer", format="int64", example=350000000),
     *                 @OA\Property(property="tax_amount", type="integer", format="int64", example=0),
     *                 @OA\Property(property="commission_amount", type="integer", format="int64", nullable=true, example=null)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function postApiV1Reservations(): void
    {
    }
}
