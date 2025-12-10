<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CountryResource",
 *     title="Country resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="ایران"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Iran"),
 *     @OA\Property(property="iso2", type="string", nullable=true, example="IR"),
 *     @OA\Property(property="iso3", type="string", nullable=true, example="IRN"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="CountryRequest",
 *     title="Country create payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="iso2", type="string", nullable=true, minLength=2, maxLength=2),
 *     @OA\Property(property="iso3", type="string", nullable=true, minLength=3, maxLength=3),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CountryUpdateRequest",
 *     title="Country update payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="iso2", type="string", nullable=true, minLength=2, maxLength=2),
 *     @OA\Property(property="iso3", type="string", nullable=true, minLength=3, maxLength=3),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CountryResourceResponse",
 *     title="Country response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/CountryResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CountryCollectionResponse",
 *     title="Country collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/CountryResource")
 *             )
 *         )
 *     }
 * )
 */
class CountrySchemas
{
}
