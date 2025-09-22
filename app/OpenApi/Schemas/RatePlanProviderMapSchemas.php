<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RatePlanProviderMapResource",
 *     title="Rate plan provider map resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=11),
 *     @OA\Property(property="rate_plan_id", type="integer", example=77),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="PLN-44"),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="پلان تأمین‌کننده"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Provider plan"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="rate_plan", ref="#/components/schemas/RatePlanResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanProviderMapRequest",
 *     title="Rate plan provider map create payload",
 *     type="object",
 *     required={"rate_plan_id", "provider_id"},
 *     @OA\Property(property="rate_plan_id", type="integer", example=77),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanProviderMapUpdateRequest",
 *     title="Rate plan provider map update payload",
 *     type="object",
 *     @OA\Property(property="rate_plan_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanProviderMapResourceResponse",
 *     title="Rate plan provider map response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RatePlanProviderMapResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RatePlanProviderMapCollectionResponse",
 *     title="Rate plan provider map collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RatePlanProviderMapResource")
 *             )
 *         )
 *     }
 * )
 */
class RatePlanProviderMapSchemas
{
}
