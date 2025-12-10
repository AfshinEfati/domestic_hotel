<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FacilityGroupResource",
 *     title="Facility group resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="گروه امکانات"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Facility Group"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(
 *         property="facilities",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/FacilityResource")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="FacilityGroupRequest",
 *     title="Facility group create payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="FacilityGroupUpdateRequest",
 *     title="Facility group update payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="FacilityGroupResourceResponse",
 *     title="Facility group response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/FacilityGroupResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="FacilityGroupCollectionResponse",
 *     title="Facility group collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/FacilityGroupResource")
 *             )
 *         )
 *     }
 * )
 */
class FacilityGroupSchemas
{
}
