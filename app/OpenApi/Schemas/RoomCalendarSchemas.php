<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RoomCalendarResource",
 *     title="Room calendar resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=901),
 *     @OA\Property(property="room_type_id", type="integer", example=300),
 *     @OA\Property(property="rate_plan_id", type="integer", example=77),
 *     @OA\Property(property="provider_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="board", type="string", nullable=true, example="BB"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-05-01"),
 *     @OA\Property(property="price", type="number", format="float", nullable=true, example=125.50),
 *     @OA\Property(property="allotment", type="integer", nullable=true, example=10),
 *     @OA\Property(property="sold", type="integer", nullable=true, example=2),
 *     @OA\Property(property="release", type="integer", nullable=true, example=3),
 *     @OA\Property(property="minimum_stay", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="accommodation", ref="#/components/schemas/AccommodationResource", nullable=true),
 *     @OA\Property(property="room_type", ref="#/components/schemas/RoomTypeResource", nullable=true),
 *     @OA\Property(property="rate_plan", ref="#/components/schemas/RatePlanResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarRequest",
 *     title="Room calendar create payload",
 *     type="object",
 *     required={"accommodation_id", "room_type_id", "rate_plan_id", "day"},
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="room_type_id", type="integer", example=300),
 *     @OA\Property(property="rate_plan_id", type="integer", example=77),
 *     @OA\Property(property="day", type="string", format="date", example="2024-05-01"),
 *     @OA\Property(property="rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="min_stay", type="integer", nullable=true),
 *     @OA\Property(property="max_stay", type="integer", nullable=true),
 *     @OA\Property(property="cta", type="boolean", nullable=true),
 *     @OA\Property(property="ctd", type="boolean", nullable=true),
 *     @OA\Property(property="closed", type="boolean", nullable=true),
 *     @OA\Property(property="inventory", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_property_id", type="string", nullable=true),
 *     @OA\Property(property="provider_room_type_id", type="string", nullable=true),
 *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarUpdateRequest",
 *     title="Room calendar update payload",
 *     type="object",
 *     @OA\Property(property="accommodation_id", type="integer", nullable=true),
 *     @OA\Property(property="room_type_id", type="integer", nullable=true),
 *     @OA\Property(property="rate_plan_id", type="integer", nullable=true),
 *     @OA\Property(property="day", type="string", format="date", nullable=true),
 *     @OA\Property(property="rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="baby_cot_grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_rack_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_daily_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="extend_bed_grs_rate", type="number", format="float", nullable=true),
 *     @OA\Property(property="min_stay", type="integer", nullable=true),
 *     @OA\Property(property="max_stay", type="integer", nullable=true),
 *     @OA\Property(property="cta", type="boolean", nullable=true),
 *     @OA\Property(property="ctd", type="boolean", nullable=true),
 *     @OA\Property(property="closed", type="boolean", nullable=true),
 *     @OA\Property(property="inventory", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_property_id", type="string", nullable=true),
 *     @OA\Property(property="provider_room_type_id", type="string", nullable=true),
 *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarResourceResponse",
 *     title="Room calendar response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RoomCalendarResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarCollectionResponse",
 *     title="Room calendar collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RoomCalendarResource")
 *             )
 *         )
 *     }
 * )
 */
class RoomCalendarSchemas
{
}
