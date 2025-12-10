<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RoomCalendarSnapshotResource",
 *     title="Room calendar snapshot resource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=55),
 *     @OA\Property(property="room_calendar_id", type="integer", example=901),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="payload", type="object", nullable=true),
 *     @OA\Property(property="created_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="updated_at", ref="#/components/schemas/Timestamp"),
 *     @OA\Property(property="room_calendar", ref="#/components/schemas/RoomCalendarResource", nullable=true),
 *     @OA\Property(property="provider", ref="#/components/schemas/ProviderResource", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarSnapshotRequest",
 *     title="Room calendar snapshot create payload",
 *     type="object",
 *     required={"room_calendar_id", "provider_id", "day"},
 *     @OA\Property(property="room_calendar_id", type="integer", example=901),
 *     @OA\Property(property="provider_id", type="integer", example=5),
 *     @OA\Property(property="day", type="string", format="date", example="2024-05-01"),
 *     @OA\Property(property="payload", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarSnapshotUpdateRequest",
 *     title="Room calendar snapshot update payload",
 *     type="object",
 *     @OA\Property(property="room_calendar_id", type="integer", nullable=true),
 *     @OA\Property(property="provider_id", type="integer", nullable=true),
 *     @OA\Property(property="day", type="string", format="date", nullable=true),
 *     @OA\Property(property="payload", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarSnapshotResourceResponse",
 *     title="Room calendar snapshot response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/RoomCalendarSnapshotResource")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RoomCalendarSnapshotCollectionResponse",
 *     title="Room calendar snapshot collection response",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/RoomCalendarSnapshotResource")
 *             )
 *         )
 *     }
 * )
 */
class RoomCalendarSnapshotSchemas
{
}
