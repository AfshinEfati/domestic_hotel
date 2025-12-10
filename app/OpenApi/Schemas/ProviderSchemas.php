<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProviderResource",
 *     title="Provider resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="تأمین‌کننده نمونه"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample Provider"),
 *     @OA\Property(property="code", type="string", nullable=true, example="SPRV"),
 *     @OA\Property(property="config", type="object", nullable=true),
 *     @OA\Property(property="is_active", ref="#/components/schemas/Status", nullable=true),
 *     @OA\Property(property="auth_token", type="string", nullable=true, example="token-123"),
 *     @OA\Property(property="expire_at", ref="#/components/schemas/Timestamp", nullable=true),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(
 *         property="city_maps",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ProviderCityMapResource")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProviderRequest",
 *     title="Provider create payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="code", type="string", nullable=true),
 *     @OA\Property(property="config", type="object", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="auth_token", type="string", nullable=true),
 *     @OA\Property(property="expire_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProviderUpdateRequest",
 *     title="Provider update payload",
 *     type="object",
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="code", type="string", nullable=true),
 *     @OA\Property(property="config", type="object", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="auth_token", type="string", nullable=true),
 *     @OA\Property(property="expire_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProviderResourceResponse",
 *     title="Provider response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/ProviderResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ProviderCollectionResponse",
 *     title="Provider collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ProviderResource")
 *             )
 *         )
 *     }
 * )
 */
class ProviderSchemas
{
}
