<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * Swagger documentation for the independent front available-rooms endpoint.
 */
class AvailableRoomsDoc
{
    /**
     * @OA\Schema(
     *     schema="AvailableRoomsCalendarItem",
     *     type="object",
     *     @OA\Property(property="day", type="string", format="date", example="2026-09-15"),
     *     @OA\Property(property="inventory", type="integer", example=4),
     *     @OA\Property(property="provider_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="rack_rate", type="integer", format="int64", nullable=true, example=37000000),
     *     @OA\Property(property="daily_rate", type="integer", format="int64", nullable=true, example=32000000),
     *     @OA\Property(property="grs_rate", type="integer", format="int64", nullable=true, example=31080000),
     *     @OA\Property(property="final_rate", type="integer", format="int64", nullable=true, example=32634000, description="Final sell rate calculated by the configured provider pricing rule."),
     *     @OA\Property(property="baby_cot_rack_rate", type="integer", format="int64", nullable=true, example=5000000),
     *     @OA\Property(property="baby_cot_daily_rate", type="integer", format="int64", nullable=true, example=4800000),
     *     @OA\Property(property="baby_cot_grs_rate", type="integer", format="int64", nullable=true, example=4500000),
     *     @OA\Property(property="baby_cot_final_rate", type="integer", format="int64", nullable=true, example=4725000),
     *     @OA\Property(property="extend_bed_rack_rate", type="integer", format="int64", nullable=true, example=7000000),
     *     @OA\Property(property="extend_bed_daily_rate", type="integer", format="int64", nullable=true, example=6500000),
     *     @OA\Property(property="extend_bed_grs_rate", type="integer", format="int64", nullable=true, example=6000000),
     *     @OA\Property(property="extend_bed_final_rate", type="integer", format="int64", nullable=true, example=6300000),
     *     @OA\Property(property="min_stay", type="integer", nullable=true, example=1),
     *     @OA\Property(property="max_stay", type="integer", nullable=true, example=10),
     *     @OA\Property(property="cta", type="boolean", nullable=true, example=false),
     *     @OA\Property(property="ctd", type="boolean", nullable=true, example=false)
     * )
     */
    public function availableRoomsCalendarItemSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="AvailableRoomsRatePlan",
     *     type="object",
     *     @OA\Property(property="id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="fa_name", type="string", nullable=true, example="صبحانه"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Breakfast"),
     *     @OA\Property(property="meal_type", type="string", nullable=true, example="breakfast"),
     *     @OA\Property(property="food_board_type", type="string", nullable=true, example="BB"),
     *     @OA\Property(property="cancelable", type="boolean", nullable=true, example=true),
     *     @OA\Property(
     *         property="calendar",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/AvailableRoomsCalendarItem")
     *     )
     * )
     */
    public function availableRoomsRatePlanSchema(): void
    {
    }

    /**
     * @OA\Schema(
     *     schema="AvailableRoomsRoom",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", nullable=true, example="اتاق دو تخته"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Double Room"),
     *     @OA\Property(property="capacity", type="integer", nullable=true, example=2),
     *     @OA\Property(property="extra_capacity", type="integer", nullable=true, example=1),
     *     @OA\Property(property="single_bed_count", type="integer", nullable=true, example=0),
     *     @OA\Property(property="double_bed_count", type="integer", nullable=true, example=1),
     *     @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=0),
     *     @OA\Property(
     *         property="room_type_name",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="fa_name", type="string", nullable=true, example="دو تخته"),
     *         @OA\Property(property="en_name", type="string", nullable=true, example="Double")
     *     ),
     *     @OA\Property(
     *         property="rate_plans",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/AvailableRoomsRatePlan")
     *     )
     * )
     */
    public function availableRoomsRoomSchema(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/front/accommodations/available-rooms",
     *     summary="Get all future available rooms for a hotel",
     *     description="Independent availability endpoint. Reads persisted room calendar data only and does not call a provider API. Returns future open calendar rows with inventory greater than zero, grouped by room and rate plan. final_rate values are calculated from the active provider pricing rule.",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"hotel_id"},
     *             @OA\Property(property="hotel_id", type="integer", example=1),
     *             example={"hotel_id":1}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Available rooms fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Available Rooms Fetched Successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="rooms",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/AvailableRoomsRoom")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The hotel id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="hotel_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The hotel id field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function postApiV1FrontAccommodationsAvailableRooms(): void
    {
    }
}
