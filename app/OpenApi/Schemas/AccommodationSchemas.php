<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="AccommodationResource",
 *     title="Accommodation resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1201),
 *     @OA\Property(property="city_id", type="integer", example=45),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="هتل نمونه"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample Hotel"),
 *     @OA\Property(property="accommodation_type_id", type="integer", example=3),
 *     @OA\Property(property="star", type="integer", nullable=true, example=5),
 *     @OA\Property(property="grade", type="string", nullable=true, example="deluxe"),
 *     @OA\Property(property="address", type="string", nullable=true, example="123 Sample St, Tehran"),
 *     @OA\Property(property="lat", type="string", nullable=true, example="35.6892"),
 *     @OA\Property(property="lng", type="string", nullable=true, example="51.3890"),
 *     @OA\Property(property="is_active", ref="#/components/schemas/Status"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="city", ref="#/components/schemas/CityResource", nullable=true),
 *     @OA\Property(property="type", ref="#/components/schemas/AccommodationTypeResource", nullable=true),
 *     @OA\Property(
 *         property="facilities",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/FacilityResource")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationRequest",
 *     title="Accommodation create payload",
 *     type="object",
 *     required={"city_id", "accommodation_type_id"},
 *     @OA\Property(property="city_id", type="integer", example=45),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="accommodation_type_id", type="integer", example=3),
 *     @OA\Property(property="star", type="integer", nullable=true),
 *     @OA\Property(property="grade", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="lat", type="string", nullable=true),
 *     @OA\Property(property="lng", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationUpdateRequest",
 *     title="Accommodation update payload",
 *     type="object",
 *     @OA\Property(property="city_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="accommodation_type_id", type="integer", nullable=true),
 *     @OA\Property(property="star", type="integer", nullable=true),
 *     @OA\Property(property="grade", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="lat", type="string", nullable=true),
 *     @OA\Property(property="lng", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationResourceResponse",
 *     title="Accommodation response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/AccommodationResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationCollectionResponse",
 *     title="Accommodation collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/AccommodationResource")
 *             )
 *         )
 *     }
 * )
 */
class AccommodationSchemas
{
}
