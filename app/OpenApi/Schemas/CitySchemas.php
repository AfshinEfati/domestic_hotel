<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CityResource",
 *     title="City resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=210),
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="state_id", type="integer", nullable=true, example=10),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="اصفهان"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Isfahan"),
 *     @OA\Property(property="lat", type="string", nullable=true, example="32.6546"),
 *     @OA\Property(property="lng", type="string", nullable=true, example="51.6680"),
 *     @OA\Property(property="osm_id", type="string", nullable=true, example="123456"),
 *     @OA\Property(property="is_popular", ref="#/components/schemas/Status", nullable=true),
 *     @OA\Property(property="is_active", ref="#/components/schemas/Status", nullable=true),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="country", ref="#/components/schemas/CountryResource", nullable=true),
 *     @OA\Property(property="state", ref="#/components/schemas/StateResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CityRequest",
 *     title="City create payload",
 *     type="object",
 *     required={"country_id"},
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="state_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="lat", type="number", format="float", nullable=true),
 *     @OA\Property(property="lng", type="number", format="float", nullable=true),
 *     @OA\Property(property="osm_id", type="string", nullable=true),
 *     @OA\Property(property="is_popular", type="boolean", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CityUpdateRequest",
 *     title="City update payload",
 *     type="object",
 *     @OA\Property(property="country_id", type="integer", nullable=true),
 *     @OA\Property(property="state_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="lat", type="number", format="float", nullable=true),
 *     @OA\Property(property="lng", type="number", format="float", nullable=true),
 *     @OA\Property(property="osm_id", type="string", nullable=true),
 *     @OA\Property(property="is_popular", type="boolean", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CityResourceResponse",
 *     title="City response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/CityResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CityCollectionResponse",
 *     title="City collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/CityResource")
 *             )
 *         )
 *     }
 * )
 */
class CitySchemas
{
}
