<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StateResource",
 *     title="State resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="تهران"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Tehran"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="country", ref="#/components/schemas/CountryResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StateRequest",
 *     title="State create payload",
 *     type="object",
 *     required={"country_id"},
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StateUpdateRequest",
 *     title="State update payload",
 *     type="object",
 *     @OA\Property(property="country_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StateResourceResponse",
 *     title="State response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/StateResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="StateCollectionResponse",
 *     title="State collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/StateResource")
 *             )
 *         )
 *     }
 * )
 */
class StateSchemas
{
}
