<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="RoomType")
 */
class RoomTypeDoc
{
    /**
     * @OA\Schema(
     *     schema="RoomTypeResource",
     *     type="object",
     *     required={"id","accommodation_id","fa_name","out_of_service"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="accommodation_id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="capacity", type="integer", nullable=true, example=42),
     *     @OA\Property(property="extra_capacity", type="integer", nullable=true, example=42),
     *     @OA\Property(property="single_bed_count", type="integer", nullable=true, example=3),
     *     @OA\Property(property="double_bed_count", type="integer", nullable=true, example=3),
     *     @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=3),
     *     @OA\Property(property="out_of_service", type="integer", example=0),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":"0","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function roomTypeSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-types",
     *     summary="List RoomType",
     *     tags={"RoomType"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="accommodation_id", type="integer", example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="capacity", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="extra_capacity", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="single_bed_count", type="integer", nullable=true, example=3),
     *                     @OA\Property(property="double_bed_count", type="integer", nullable=true, example=3),
     *                     @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=3),
     *                     @OA\Property(property="out_of_service", type="integer", example=0),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":"0","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRoomTypes(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-types",
     *     summary="Create RoomType",
     *     tags={"RoomType"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","en_name","capacity","extra_capacity","single_bed_count","double_bed_count","sofa_bed_count","out_of_service","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="capacity", type="integer", example=42),
     *                 @OA\Property(property="extra_capacity", type="integer", example=42),
     *                 @OA\Property(property="single_bed_count", type="integer", example=3),
     *                 @OA\Property(property="double_bed_count", type="integer", example=3),
     *                 @OA\Property(property="sofa_bed_count", type="integer", example=3),
     *                 @OA\Property(property="out_of_service", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":true,"created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","out_of_service"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extra_capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="single_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="double_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="out_of_service", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":"0","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminRoomTypes(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     summary="Show RoomType",
     *     tags={"RoomType"},
     *     @OA\Parameter(
     *         name="room_type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","out_of_service"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extra_capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="single_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="double_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="out_of_service", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":"0","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRoomTypesRoomType(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     summary="Update RoomType",
     *     tags={"RoomType"},
     *     @OA\Parameter(
     *         name="room_type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="capacity", type="integer", example=42),
     *                 @OA\Property(property="extra_capacity", type="integer", example=42),
     *                 @OA\Property(property="single_bed_count", type="integer", example=3),
     *                 @OA\Property(property="double_bed_count", type="integer", example=3),
     *                 @OA\Property(property="sofa_bed_count", type="integer", example=3),
     *                 @OA\Property(property="out_of_service", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":true,"created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","out_of_service"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extra_capacity", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="single_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="double_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="sofa_bed_count", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="out_of_service", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","capacity":42,"extra_capacity":42,"single_bed_count":3,"double_bed_count":3,"sofa_bed_count":3,"out_of_service":"0","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminRoomTypesRoomType(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     summary="Delete RoomType",
     *     tags={"RoomType"},
     *     @OA\Parameter(
     *         name="room_type",
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
    public function deleteApiV1AdminRoomTypesRoomType(){}
}
