<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RoomTypeProviderMapResource",
 *     title="Room type provider map resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=21),
 *     @OA\Property(property="room_type_id", type="integer", example=300),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="RM-123"),
 *     @OA\Property(property="fa_name", type="string", nullable=true, example="کد اتاق تأمین‌کننده"),
 *     @OA\Property(property="en_name", type="string", nullable=true, example="Provider room type"),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="room_type", ref="#/components/schemas/RoomTypeResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeProviderMapRequest",
 *     title="Room type provider map create payload",
 *     type="object",
 *     required={"room_type_id", "provider_id"},
 *     @OA\Property(property="room_type_id", type="integer", example=300),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="provider_room_type_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeProviderMapUpdateRequest",
 *     title="Room type provider map update payload",
 *     type="object",
 *     @OA\Property(property="room_type_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_room_type_id", type="string", nullable=true),
 *     @OA\Property(property="fa_name", type="string", nullable=true),
 *     @OA\Property(property="en_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeProviderMapResourceResponse",
 *     title="Room type provider map response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RoomTypeProviderMapResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RoomTypeProviderMapCollectionResponse",
 *     title="Room type provider map collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RoomTypeProviderMapResource")
 *             )
 *         )
 *     }
 * )
 */
class RoomTypeProviderMapSchemas
{
}
