<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProviderCityMapResource",
 *     title="Provider city map resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=44),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="city_id", type="integer", example=210),
 *     @OA\Property(property="provider_city_id", type="string", nullable=true, example="7890"),
 *     @OA\Property(property="city_name", type="string", nullable=true, example="Shiraz"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="city", ref="#/components/schemas/CityResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProviderCityMapRequest",
 *     title="Provider city map create payload",
 *     type="object",
 *     required={"city_id", "provider_id"},
 *     @OA\Property(property="city_id", type="integer", example=210),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_city_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProviderCityMapUpdateRequest",
 *     title="Provider city map update payload",
 *     type="object",
 *     @OA\Property(property="city_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_city_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProviderCityMapResourceResponse",
 *     title="Provider city map response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/ProviderCityMapResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ProviderCityMapCollectionResponse",
 *     title="Provider city map collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ProviderCityMapResource")
 *             )
 *         )
 *     }
 * )
 */
class ProviderCityMapSchemas
{
}
