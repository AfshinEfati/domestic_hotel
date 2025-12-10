<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FacilityResource",
 *     title="Facility resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=33),
 *     @OA\Property(property="facility_group_id", type="integer", example=12),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="امکان"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Facility"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="group", ref="#/components/schemas/FacilityGroupResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="FacilityRequest",
 *     title="Facility create payload",
 *     type="object",
 *     required={"facility_group_id"},
 *     @OA\Property(property="facility_group_id", type="integer", example=12),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="FacilityUpdateRequest",
 *     title="Facility update payload",
 *     type="object",
 *     @OA\Property(property="facility_group_id", type="integer", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="FacilityResourceResponse",
 *     title="Facility response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/FacilityResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="FacilityCollectionResponse",
 *     title="Facility collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/FacilityResource")
 *             )
 *         )
 *     }
 * )
 */
class FacilitySchemas
{
}
