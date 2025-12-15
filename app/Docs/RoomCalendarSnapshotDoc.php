<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="RoomCalendarSnapshot")
 */
class RoomCalendarSnapshotDoc
{
    /**
     * @OA\Schema(
     *     schema="RoomCalendarSnapshotResource",
     *     type="object",
     *     required={"id","room_calendar_id","provider_id","day"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="room_calendar_id", type="integer", example=1),
     *     @OA\Property(property="provider_id", type="integer", example=1),
     *     @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *     @OA\Property(property="payload", type="object", nullable=true, example="Payload"),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"room_calendar_id":1,"provider_id":1,"day":"2024-01-01","payload":"Payload","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function roomCalendarSnapshotSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendar-snapshots",
     *     summary="List RoomCalendarSnapshot",
     *     tags={"RoomCalendarSnapshot"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                     @OA\Property(property="provider_id", type="integer", example=1),
     *                     @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                     @OA\Property(property="payload", type="object", nullable=true, example="Payload"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"room_calendar_id":1,"provider_id":1,"day":"2024-01-01","payload":"Payload","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     *                 )
     *             )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     * )
     */
    public function getApiV1AdminRoomCalendarSnapshots(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-calendar-snapshots",
     *     summary="Create RoomCalendarSnapshot",
     *     tags={"RoomCalendarSnapshot"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","room_calendar_id","provider_id","day","payload","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", example="Day"),
     *                 @OA\Property(property="payload", type="array", example="Payload", @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","room_calendar_id":1,"provider_id":1,"day":"Day","payload":"Payload","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","room_calendar_id","provider_id","day"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="payload", type="object", nullable=true, example="Payload"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"room_calendar_id":1,"provider_id":1,"day":"2024-01-01","payload":"Payload","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     ),
     * )
     */
    public function postApiV1AdminRoomCalendarSnapshots(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     summary="Show RoomCalendarSnapshot",
     *     tags={"RoomCalendarSnapshot"},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","room_calendar_id","provider_id","day"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="payload", type="object", nullable=true, example="Payload"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"room_calendar_id":1,"provider_id":1,"day":"2024-01-01","payload":"Payload","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     * )
     */
    public function getApiV1AdminRoomCalendarSnapshotsRoomCalendarSnapshot(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     summary="Update RoomCalendarSnapshot",
     *     tags={"RoomCalendarSnapshot"},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", example="Day"),
     *                 @OA\Property(property="payload", type="array", example="Payload", @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","room_calendar_id":1,"provider_id":1,"day":"Day","payload":"Payload","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","room_calendar_id","provider_id","day"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="room_calendar_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="payload", type="object", nullable=true, example="Payload"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"room_calendar_id":1,"provider_id":1,"day":"2024-01-01","payload":"Payload","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     ),
     * )
     */
    public function putApiV1AdminRoomCalendarSnapshotsRoomCalendarSnapshot(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     summary="Delete RoomCalendarSnapshot",
     *     tags={"RoomCalendarSnapshot"},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Deleted",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     * )
     */
    public function deleteApiV1AdminRoomCalendarSnapshotsRoomCalendarSnapshot(){}
}
