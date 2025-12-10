<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RoomTypeResource",
 *     title="Room type resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=300),
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="provider_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="اتاق استاندارد"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Standard Room"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="accommodation", ref="#/components/schemas/AccommodationResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeRequest",
 *     title="Room type create payload",
 *     type="object",
 *     required={"accommodation_id"},
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="capacity", type="integer", nullable=true),
 *     @OA\Property(property="extra_capacity", type="integer", nullable=true),
 *     @OA\Property(property="single_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="double_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="sofa_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="out_of_service", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeUpdateRequest",
 *     title="Room type update payload",
 *     type="object",
 *     @OA\Property(property="accommodation_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="capacity", type="integer", nullable=true),
 *     @OA\Property(property="extra_capacity", type="integer", nullable=true),
 *     @OA\Property(property="single_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="double_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="sofa_bed_count", type="integer", nullable=true),
 *     @OA\Property(property="out_of_service", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeResourceResponse",
 *     title="Room type response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RoomTypeResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeCollectionResponse",
 *     title="Room type collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RoomTypeResource")
 *             )
 *         )
 *     }
 * )
 */
class RoomTypeSchemas
{
}
