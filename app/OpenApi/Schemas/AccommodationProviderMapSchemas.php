<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="AccommodationProviderMapResource",
 *     title="Accommodation provider map resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=14),
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_property_id", type="string", nullable=true, example="HTL-55"),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="هتل در تامین‌کننده"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Provider hotel"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="accommodation", ref="#/components/schemas/AccommodationResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationProviderMapRequest",
 *     title="Accommodation provider map create payload",
 *     type="object",
 *     required={"accommodation_id", "provider_id"},
 *     @OA\Property(property="accommodation_id", type="integer", example=1201),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_property_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationProviderMapUpdateRequest",
 *     title="Accommodation provider map update payload",
 *     type="object",
 *     @OA\Property(property="accommodation_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_property_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationProviderMapResourceResponse",
 *     title="Accommodation provider map response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/AccommodationProviderMapResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="AccommodationProviderMapCollectionResponse",
 *     title="Accommodation provider map collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/AccommodationProviderMapResource")
 *             )
 *         )
 *     }
 * )
 */
class AccommodationProviderMapSchemas
{
}
