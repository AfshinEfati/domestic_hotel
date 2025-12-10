<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RatePlanResource",
 *     title="Rate plan resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=77),
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="نرخ استاندارد"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Standard Rate"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="accommodation", ref="#/components/schemas/AccommodationResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanRequest",
 *     title="Rate plan create payload",
 *     type="object",
 *     required={"accommodation_id"},
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="meal_type", type="string", nullable=true),
 *     @OA\Property(property="food_board_type", type="string", nullable=true),
 *     @OA\Property(property="cancelable", type="boolean", nullable=true),
 *     @OA\Property(property="sleeps", type="integer", nullable=true),
 *     @OA\Property(property="min_stay", type="integer", nullable=true),
 *     @OA\Property(property="max_stay", type="integer", nullable=true),
 *     @OA\Property(
 *         property="facilities",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(type="integer")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanUpdateRequest",
 *     title="Rate plan update payload",
 *     type="object",
 *     @OA\Property(property="accommodation_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="meal_type", type="string", nullable=true),
 *     @OA\Property(property="food_board_type", type="string", nullable=true),
 *     @OA\Property(property="cancelable", type="boolean", nullable=true),
 *     @OA\Property(property="sleeps", type="integer", nullable=true),
 *     @OA\Property(property="min_stay", type="integer", nullable=true),
 *     @OA\Property(property="max_stay", type="integer", nullable=true),
 *     @OA\Property(
 *         property="facilities",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(type="integer")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanResourceResponse",
 *     title="Rate plan response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RatePlanResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanCollectionResponse",
 *     title="Rate plan collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RatePlanResource")
 *             )
 *         )
 *     }
 * )
 */
class RatePlanSchemas
{
}
