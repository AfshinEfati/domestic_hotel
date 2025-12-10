<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="AccommodationTypeResource",
 *     title="Accommodation type resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=7),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="هتل"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Hotel"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(
 *         property="accommodations",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/AccommodationResource")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationTypeRequest",
 *     title="Accommodation type create payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationTypeUpdateRequest",
 *     title="Accommodation type update payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationTypeResourceResponse",
 *     title="Accommodation type response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/AccommodationTypeResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationTypeCollectionResponse",
 *     title="Accommodation type collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/AccommodationTypeResource")
 *             )
 *         )
 *     }
 * )
 */
class AccommodationTypeSchemas
{
}
