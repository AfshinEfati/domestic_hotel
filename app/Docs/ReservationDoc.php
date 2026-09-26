<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Reservation",
 *     description="Hotel reservation request endpoints"
 * )
 */
class ReservationDoc
{
    /**
     * @OA\Schema(
     *     schema="ReservationCreateCalendarInput",
     *     type="object",
     *     required={"calendar_id","date","expected_price"},
     *     @OA\Property(
     *         property="calendar_id",
     *         type="integer",
     *         minimum=1,
     *         example=101,
     *         description="Room calendar row selected by the client for this exact night."
     *     ),
     *     @OA\Property(
     *         property="date",
     *         type="string",
     *         format="date",
     *         example="2027-03-04",
     *         description="Night date. Every date from check_in up to but excluding check_out must appear exactly once for each room."
     *     ),
     *     @OA\Property(
     *         property="expected_price",
     *         type="integer",
     *         format="int64",
     *         minimum=0,
     *         example=32000000,
     *         description="Price accepted by the client for this night."
     *     )
     * )
     */
    public function reservationCreateCalendarInputSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ReservationCreateGuestInput",
     *     type="object",
     *     required={"type","first_name","last_name","country_code"},
     *     @OA\Property(
     *         property="type",
     *         type="integer",
     *         enum={1,2,3},
     *         example=1,
     *         description="Guest type: 1=adult, 2=child, 3=infant."
     *     ),
     *     @OA\Property(property="first_name", type="string", maxLength=100, example="علی"),
     *     @OA\Property(property="last_name", type="string", maxLength=100, example="احمدی"),
     *     @OA\Property(
     *         property="gender",
     *         type="integer",
     *         nullable=true,
     *         enum={1,2},
     *         example=1,
     *         description="Optional. 1=male, 2=female."
     *     ),
     *     @OA\Property(
     *         property="birth_date",
     *         type="string",
     *         format="date",
     *         nullable=true,
     *         example="1990-05-12",
     *         description="Optional and must be before today."
     *     ),
     *     @OA\Property(
     *         property="country_code",
     *         type="string",
     *         minLength=3,
     *         maxLength=3,
     *         example="IRN",
     *         description="Required ISO3 country code existing in countries.iso3. Input is normalized to uppercase. country_id is resolved internally and must not be supplied by the client."
     *     ),
     *     @OA\Property(
     *         property="national_id",
     *         type="string",
     *         nullable=true,
     *         pattern="^[0-9]{10}$",
     *         example="0012345678",
     *         description="Required when country_code is IRN. Must contain exactly 10 digits."
     *     ),
     *     @OA\Property(
     *         property="passport_number",
     *         type="string",
     *         nullable=true,
     *         maxLength=64,
     *         example="X12345678",
     *         description="Required for non-Iranian guests."
     *     ),
     *     @OA\Property(
     *         property="passport_issuer_country_code",
     *         type="string",
     *         nullable=true,
     *         minLength=3,
     *         maxLength=3,
     *         example="USA",
     *         description="Required for non-Iranian guests. Must be an existing ISO3 country code. passport_issuer_country_id is resolved internally."
     *     ),
     *     @OA\Property(
     *         property="passport_expiry_date",
     *         type="string",
     *         format="date",
     *         nullable=true,
     *         example="2031-08-20",
     *         description="Required for non-Iranian guests and must be after today."
     *     )
     * )
     */
    public function reservationCreateGuestInputSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ReservationCreateRoomInput",
     *     type="object",
     *     required={"room_number","expected_total_price","calendar","guests"},
     *     @OA\Property(
     *         property="room_number",
     *         type="integer",
     *         minimum=1,
     *         example=1,
     *         description="Client room sequence number. Must be unique inside the reservation."
     *     ),
     *     @OA\Property(
     *         property="expected_total_price",
     *         type="integer",
     *         format="int64",
     *         minimum=0,
     *         example=65000000,
     *         description="Expected room total. Must equal the sum of calendar[*].expected_price for this room."
     *     ),
     *     @OA\Property(
     *         property="calendar",
     *         type="array",
     *         minItems=1,
     *         description="Exactly one selected calendar row for every night of the stay.",
     *         @OA\Items(ref="#/components/schemas/ReservationCreateCalendarInput")
     *     ),
     *     @OA\Property(
     *         property="guests",
     *         type="array",
     *         minItems=1,
     *         @OA\Items(ref="#/components/schemas/ReservationCreateGuestInput")
     *     )
     * )
     */
    public function reservationCreateRoomInputSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ReservationCreateRequest",
     *     type="object",
     *     required={
     *         "agency_id",
     *         "check_in",
     *         "check_out",
     *         "first_name",
     *         "last_name",
     *         "mobile",
     *         "expected_total_price",
     *         "hotel"
     *     },
     *     @OA\Property(property="agency_id", type="integer", minimum=1, example=1),
     *     @OA\Property(property="check_in", type="string", format="date", example="2027-03-04"),
     *     @OA\Property(
     *         property="check_out",
     *         type="string",
     *         format="date",
     *         example="2027-03-06",
     *         description="Must be after check_in."
     *     ),
     *     @OA\Property(property="first_name", type="string", maxLength=100, example="افشین"),
     *     @OA\Property(property="last_name", type="string", maxLength=100, example="احمدی"),
     *     @OA\Property(property="mobile", type="string", maxLength=32, example="09151234567"),
     *     @OA\Property(
     *         property="email",
     *         type="string",
     *         format="email",
     *         nullable=true,
     *         maxLength=255,
     *         example="afshin@example.com"
     *     ),
     *     @OA\Property(
     *         property="expected_total_price",
     *         type="integer",
     *         format="int64",
     *         minimum=0,
     *         example=148000000,
     *         description="Expected reservation total. Must equal the sum of hotel.rooms[*].expected_total_price."
     *     ),
     *     @OA\Property(
     *         property="hotel",
     *         type="object",
     *         required={"accommodation_id","rooms"},
     *         @OA\Property(
     *             property="accommodation_id",
     *             type="integer",
     *             minimum=1,
     *             example=24,
     *             description="Existing accommodations.id."
     *         ),
     *         @OA\Property(
     *             property="rooms",
     *             type="array",
     *             minItems=1,
     *             maxItems=5,
     *             @OA\Items(ref="#/components/schemas/ReservationCreateRoomInput")
     *         )
     *     ),
     *     example={
     *         "agency_id":1,
     *         "check_in":"2027-03-04",
     *         "check_out":"2027-03-06",
     *         "first_name":"افشین",
     *         "last_name":"احمدی",
     *         "mobile":"09151234567",
     *         "email":"afshin@example.com",
     *         "expected_total_price":148000000,
     *         "hotel":{
     *             "accommodation_id":24,
     *             "rooms":{
     *                 {
     *                     "room_number":1,
     *                     "expected_total_price":65000000,
     *                     "calendar":{
     *                         {"calendar_id":101,"date":"2027-03-04","expected_price":32000000},
     *                         {"calendar_id":102,"date":"2027-03-05","expected_price":33000000}
     *                     },
     *                     "guests":{
     *                         {
     *                             "type":1,
     *                             "first_name":"علی",
     *                             "last_name":"احمدی",
     *                             "gender":1,
     *                             "birth_date":"1990-05-12",
     *                             "country_code":"IRN",
     *                             "national_id":"0012345678"
     *                         },
     *                         {
     *                             "type":2,
     *                             "first_name":"سارا",
     *                             "last_name":"احمدی",
     *                             "gender":2,
     *                             "birth_date":"2017-02-10",
     *                             "country_code":"IRN",
     *                             "national_id":"0012345679"
     *                         }
     *                     }
     *                 },
     *                 {
     *                     "room_number":2,
     *                     "expected_total_price":83000000,
     *                     "calendar":{
     *                         {"calendar_id":201,"date":"2027-03-04","expected_price":41000000},
     *                         {"calendar_id":202,"date":"2027-03-05","expected_price":42000000}
     *                     },
     *                     "guests":{
     *                         {
     *                             "type":1,
     *                             "first_name":"John",
     *                             "last_name":"Smith",
     *                             "gender":1,
     *                             "birth_date":"1988-07-21",
     *                             "country_code":"USA",
     *                             "passport_number":"X12345678",
     *                             "passport_issuer_country_code":"USA",
     *                             "passport_expiry_date":"2031-08-20"
     *                         }
     *                     }
     *                 }
     *             }
     *         }
     *     }
     * )
     */
    public function reservationCreateRequestSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ReservationDateMeta",
     *     type="object",
     *     nullable=true,
     *     @OA\Property(property="date", type="string", format="date", example="2026-09-24"),
     *     @OA\Property(property="time", type="string", example="10:30:00"),
     *     @OA\Property(property="fa_date", type="string", example="1405-07-02"),
     *     @OA\Property(property="iso", type="string", format="date-time", example="2026-09-24T10:30:00+03:30")
     * )
     */
    public function reservationDateMetaSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="ReservationCreateResponse",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=8451),
     *     @OA\Property(property="agency_id", type="integer", example=1),
     *     @OA\Property(
     *         property="status",
     *         type="integer",
     *         enum={1,2,3,4,5,6,7,8,9,10,11,12},
     *         example=4
     *     ),
     *     @OA\Property(
     *         property="status_detail",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="name", type="string", example="ready_for_payment"),
     *         @OA\Property(property="fa_name", type="string", example="آماده پرداخت"),
     *         @OA\Property(property="code", type="integer", example=4)
     *     ),
     *     @OA\Property(property="check_in", type="string", format="date", nullable=true, example="2027-03-04"),
     *     @OA\Property(property="check_out", type="string", format="date", nullable=true, example="2027-03-06"),
     *     @OA\Property(property="price", type="integer", format="int64", nullable=true, example=148000000),
     *     @OA\Property(property="price_change", type="boolean", example=false),
     *     @OA\Property(property="initial_price", type="integer", format="int64", nullable=true, example=148000000),
     *     @OA\Property(property="validated_price", type="integer", format="int64", nullable=true, example=148000000),
     *     @OA\Property(property="validation_error", type="string", nullable=true, example=null),
     *     @OA\Property(property="sale_amount", type="integer", format="int64", nullable=true, example=148000000),
     *     @OA\Property(property="tax_amount", type="integer", format="int64", nullable=true, example=0),
     *     @OA\Property(property="commission_amount", type="integer", format="int64", nullable=true, example=null),
     *     @OA\Property(
     *         property="booker",
     *         type="object",
     *         @OA\Property(property="first_name", type="string", nullable=true, example="افشین"),
     *         @OA\Property(property="last_name", type="string", nullable=true, example="احمدی"),
     *         @OA\Property(property="mobile", type="string", nullable=true, example="09151234567"),
     *         @OA\Property(property="email", type="string", nullable=true, example="afshin@example.com")
     *     ),
     *     @OA\Property(property="acc_code", type="string", nullable=true, example=null),
     *     @OA\Property(
     *         property="hotels",
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=9001),
     *             @OA\Property(property="accommodation_id", type="integer", nullable=true, example=24),
     *             @OA\Property(property="type", type="integer", example=1),
     *             @OA\Property(property="is_final", type="boolean", example=true),
     *             @OA\Property(
     *                 property="rooms",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=9101),
     *                     @OA\Property(property="room_number", type="integer", example=1),
     *                     @OA\Property(property="type", type="integer", example=1),
     *                     @OA\Property(property="is_final", type="boolean", example=true),
     *                     @OA\Property(property="room_calendar_id", type="integer", nullable=true, example=101),
     *                     @OA\Property(property="room_type_id", type="integer", nullable=true, example=11),
     *                     @OA\Property(property="rate_plan_id", type="integer", nullable=true, example=3),
     *                     @OA\Property(property="room_name", type="string", nullable=true, example="اتاق دو تخته"),
     *                     @OA\Property(property="initial_price", type="integer", format="int64", nullable=true, example=65000000),
     *                     @OA\Property(property="validated_price", type="integer", format="int64", nullable=true, example=65000000),
     *                     @OA\Property(property="price_change", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="nights",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=9201),
     *                             @OA\Property(property="date", type="string", format="date", nullable=true, example="2027-03-04"),
     *                             @OA\Property(property="room_calendar_id", type="integer", nullable=true, example=101),
     *                             @OA\Property(property="initial_price", type="integer", format="int64", nullable=true, example=32000000),
     *                             @OA\Property(property="validated_price", type="integer", format="int64", nullable=true, example=32000000),
     *                             @OA\Property(property="price_change", type="boolean", example=false)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="guests",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=9301),
     *                             @OA\Property(property="type", type="integer", enum={1,2,3}, example=1),
     *                             @OA\Property(property="first_name", type="string", example="علی"),
     *                             @OA\Property(property="last_name", type="string", example="احمدی"),
     *                             @OA\Property(property="gender", type="integer", nullable=true, enum={1,2}, example=1),
     *                             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-12"),
     *                             @OA\Property(property="country_id", type="integer", nullable=true, example=1),
     *                             @OA\Property(property="national_id", type="string", nullable=true, example="0012345678"),
     *                             @OA\Property(property="passport_number", type="string", nullable=true, example=null),
     *                             @OA\Property(property="passport_issuer_country_id", type="integer", nullable=true, example=null),
     *                             @OA\Property(property="passport_expiry_date", type="string", format="date", nullable=true, example=null)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Property(property="created_at", ref="#/components/schemas/ReservationDateMeta"),
     *     @OA\Property(property="updated_at", ref="#/components/schemas/ReservationDateMeta")
     * )
     */
    public function reservationCreateResponseSchema(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/front/reservations/create",
     *     summary="Create reservation request",
     *     description="Creates the reservation ticket and its hotel, room, night and guest snapshots before live provider validation. This endpoint does not reserve, pay for or book provider inventory. After creation, live validation may update the ticket status and validated prices. Client totals must be internally consistent: every room must contain exactly one calendar row for each stay night, each room total must equal its nightly total, and the reservation total must equal all room totals. Iranian guests require national_id. Non-Iranian guests require passport_number, passport_issuer_country_code and passport_expiry_date.",
     *     tags={"Reservation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReservationCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reservation ticket created. Provider validation result is represented in data.status, data.status_detail, data.validated_price and data.validation_error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="reservation created"),
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationCreateResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="validation error"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "expected_total_price":{"Reservation total does not match rooms total."},
     *                     "hotel.rooms.0.guests.0.national_id":{"National ID is required for Iranian guests."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="request failed"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function postApiV1FrontReservationsCreate(): void
    {
    }
    /**
     * @OA\Get(
     *     path="/api/v1/front/reservations/{reservation_number}",
     *     summary="Get reservation detail",
     *     tags={"Reservation"},
     *
     *     @OA\Parameter(
     *         name="reservation_number",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="8451"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reservation detail",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="reservation detail"
     *             ),
     *
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/ReservationCreateResponse"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Reservation not found"
     *     )
     * )
     */
    public function getReservationDetail(): void
    {
    }
    /**
     * @OA\Post(
     *     path="/api/v1/front/reservations/{reservation_number}/purchase",
     *     summary="Submit paid reservation for purchase",
     *     description="Called by the whitelist after customer payment is confirmed. The service locks the reservation, groups final rooms by their validated provider, evaluates purchase_manual_rules and online eligibility per provider purchase, creates reservation purchase records and room segments idempotently, and moves the reservation to book_requested. Offline decisions create a reservation_manual_purchases row for operator processing. This endpoint does not execute online provider booking yet.",
     *     tags={"Reservation"},
     *     @OA\Parameter(
     *         name="reservation_number",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="40")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase request accepted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="purchase request accepted"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reservation_id", type="integer", example=40),
     *                 @OA\Property(property="reservation_number", type="string", example="40"),
     *                 @OA\Property(property="status", type="integer", example=5),
     *                 @OA\Property(
     *                     property="status_detail",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="book_requested"),
     *                     @OA\Property(property="fa_name", type="string", example="پرداخت و درخواست خرید"),
     *                     @OA\Property(property="code", type="integer", example=5)
     *                 ),
     *                 @OA\Property(
     *                     property="purchases",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="reservation_hotel_id", type="integer", example=40),
     *                         @OA\Property(property="provider_id", type="integer", example=1),
     *                         @OA\Property(property="provider_code", type="string", example="grs"),
     *                         @OA\Property(property="status", type="integer", example=5),
     *                         @OA\Property(
     *                             property="purchase_mode",
     *                             type="object",
     *                             @OA\Property(property="name", type="string", example="offline"),
     *                             @OA\Property(property="fa_name", type="string", example="آفلاین"),
     *                             @OA\Property(property="code", type="integer", example=2)
     *                         ),
     *                         @OA\Property(property="manual_rule_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="provider_quoted_amount", type="integer", format="int64", example=151250000),
     *                         @OA\Property(
     *                             property="room_ids",
     *                             type="array",
     *                             @OA\Items(type="integer", example=46)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Reservation not found"),
     *     @OA\Response(response=422, description="Reservation is not ready for purchase or purchase data is incomplete")
     * )
     */
    public function postApiV1FrontReservationPurchase(): void
    {
    }

}
