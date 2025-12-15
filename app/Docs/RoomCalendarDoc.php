<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="RoomCalendar")
 */
class RoomCalendarDoc
{
    /**
     * @OA\Schema(
     *     schema="RoomCalendarResource",
     *     type="object",
     *     required={"id","accommodation_id","room_type_id","rate_plan_id","day","cta","ctd","closed","provider_id"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="accommodation_id", type="integer", example=1),
     *     @OA\Property(property="room_type_id", type="integer", example=1),
     *     @OA\Property(property="rate_plan_id", type="integer", example=1),
     *     @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *     @OA\Property(property="rack_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="daily_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="grs_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="baby_cot_rack_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="baby_cot_daily_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="baby_cot_grs_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="extend_bed_rack_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="extend_bed_daily_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="extend_bed_grs_rate", type="integer", nullable=true, example=42),
     *     @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *     @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *     @OA\Property(property="cta", type="integer", example=0),
     *     @OA\Property(property="ctd", type="integer", example=0),
     *     @OA\Property(property="closed", type="integer", example=0),
     *     @OA\Property(property="inventory", type="integer", nullable=true, example=42),
     *     @OA\Property(property="provider_id", type="integer", example=1),
     *     @OA\Property(property="provider_property_id", type="string", nullable=true, example="Provider Property Id"),
     *     @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="Provider Room Type Id"),
     *     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="Provider Rate Plan Id"),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"2024-01-01","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":"0","ctd":"0","closed":"0","inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function roomCalendarSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendars",
     *     summary="List RoomCalendar",
     *     tags={"RoomCalendar"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="accommodation_id", type="integer", example=1),
     *                     @OA\Property(property="room_type_id", type="integer", example=1),
     *                     @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                     @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                     @OA\Property(property="rack_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="daily_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="grs_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="baby_cot_rack_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="baby_cot_daily_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="baby_cot_grs_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="extend_bed_rack_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="extend_bed_daily_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="extend_bed_grs_rate", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="cta", type="integer", example=0),
     *                     @OA\Property(property="ctd", type="integer", example=0),
     *                     @OA\Property(property="closed", type="integer", example=0),
     *                     @OA\Property(property="inventory", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="provider_id", type="integer", example=1),
     *                     @OA\Property(property="provider_property_id", type="string", nullable=true, example="Provider Property Id"),
     *                     @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="Provider Room Type Id"),
     *                     @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="Provider Rate Plan Id"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"2024-01-01","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":"0","ctd":"0","closed":"0","inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRoomCalendars(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-calendars",
     *     summary="Create RoomCalendar",
     *     tags={"RoomCalendar"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","room_type_id","rate_plan_id","day","rack_rate","daily_rate","grs_rate","baby_cot_rack_rate","baby_cot_daily_rate","baby_cot_grs_rate","extend_bed_rack_rate","extend_bed_daily_rate","extend_bed_grs_rate","min_stay","max_stay","cta","ctd","closed","inventory","provider_id","provider_property_id","provider_room_type_id","provider_rate_plan_id","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="room_type_id", type="integer", example=1),
     *                 @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", example="Day"),
     *                 @OA\Property(property="rack_rate", type="integer", example=42),
     *                 @OA\Property(property="daily_rate", type="integer", example=42),
     *                 @OA\Property(property="grs_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_rack_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_daily_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_grs_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_rack_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_daily_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_grs_rate", type="integer", example=42),
     *                 @OA\Property(property="min_stay", type="integer", example=42),
     *                 @OA\Property(property="max_stay", type="integer", example=42),
     *                 @OA\Property(property="cta", type="boolean", example=true),
     *                 @OA\Property(property="ctd", type="boolean", example=true),
     *                 @OA\Property(property="closed", type="boolean", example=true),
     *                 @OA\Property(property="inventory", type="integer", example=42),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="provider_room_type_id", type="string", example="Provider Room Type Id"),
     *                 @OA\Property(property="provider_rate_plan_id", type="string", example="Provider Rate Plan Id"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"Day","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":true,"ctd":true,"closed":true,"inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","room_type_id","rate_plan_id","day","cta","ctd","closed","provider_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="room_type_id", type="integer", example=1),
     *                 @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="cta", type="integer", example=0),
     *                 @OA\Property(property="ctd", type="integer", example=0),
     *                 @OA\Property(property="closed", type="integer", example=0),
     *                 @OA\Property(property="inventory", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", nullable=true, example="Provider Property Id"),
     *                 @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="Provider Room Type Id"),
     *                 @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="Provider Rate Plan Id"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"2024-01-01","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":"0","ctd":"0","closed":"0","inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminRoomCalendars(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     summary="Show RoomCalendar",
     *     tags={"RoomCalendar"},
     *     @OA\Parameter(
     *         name="room_calendar",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","room_type_id","rate_plan_id","day","cta","ctd","closed","provider_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="room_type_id", type="integer", example=1),
     *                 @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="cta", type="integer", example=0),
     *                 @OA\Property(property="ctd", type="integer", example=0),
     *                 @OA\Property(property="closed", type="integer", example=0),
     *                 @OA\Property(property="inventory", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", nullable=true, example="Provider Property Id"),
     *                 @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="Provider Room Type Id"),
     *                 @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="Provider Rate Plan Id"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"2024-01-01","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":"0","ctd":"0","closed":"0","inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRoomCalendarsRoomCalendar(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     summary="Update RoomCalendar",
     *     tags={"RoomCalendar"},
     *     @OA\Parameter(
     *         name="room_calendar",
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
     *                 @OA\Property(property="room_type_id", type="integer", example=1),
     *                 @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", example="Day"),
     *                 @OA\Property(property="rack_rate", type="integer", example=42),
     *                 @OA\Property(property="daily_rate", type="integer", example=42),
     *                 @OA\Property(property="grs_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_rack_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_daily_rate", type="integer", example=42),
     *                 @OA\Property(property="baby_cot_grs_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_rack_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_daily_rate", type="integer", example=42),
     *                 @OA\Property(property="extend_bed_grs_rate", type="integer", example=42),
     *                 @OA\Property(property="min_stay", type="integer", example=42),
     *                 @OA\Property(property="max_stay", type="integer", example=42),
     *                 @OA\Property(property="cta", type="boolean", example=true),
     *                 @OA\Property(property="ctd", type="boolean", example=true),
     *                 @OA\Property(property="closed", type="boolean", example=true),
     *                 @OA\Property(property="inventory", type="integer", example=42),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="provider_room_type_id", type="string", example="Provider Room Type Id"),
     *                 @OA\Property(property="provider_rate_plan_id", type="string", example="Provider Rate Plan Id"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"Day","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":true,"ctd":true,"closed":true,"inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","room_type_id","rate_plan_id","day","cta","ctd","closed","provider_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="room_type_id", type="integer", example=1),
     *                 @OA\Property(property="rate_plan_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="baby_cot_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_rack_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_daily_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="extend_bed_grs_rate", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="cta", type="integer", example=0),
     *                 @OA\Property(property="ctd", type="integer", example=0),
     *                 @OA\Property(property="closed", type="integer", example=0),
     *                 @OA\Property(property="inventory", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", nullable=true, example="Provider Property Id"),
     *                 @OA\Property(property="provider_room_type_id", type="string", nullable=true, example="Provider Room Type Id"),
     *                 @OA\Property(property="provider_rate_plan_id", type="string", nullable=true, example="Provider Rate Plan Id"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"room_type_id":1,"rate_plan_id":1,"day":"2024-01-01","rack_rate":42,"daily_rate":42,"grs_rate":42,"baby_cot_rack_rate":42,"baby_cot_daily_rate":42,"baby_cot_grs_rate":42,"extend_bed_rack_rate":42,"extend_bed_daily_rate":42,"extend_bed_grs_rate":42,"min_stay":42,"max_stay":42,"cta":"0","ctd":"0","closed":"0","inventory":42,"provider_id":1,"provider_property_id":"Provider Property Id","provider_room_type_id":"Provider Room Type Id","provider_rate_plan_id":"Provider Rate Plan Id","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminRoomCalendarsRoomCalendar(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     summary="Delete RoomCalendar",
     *     tags={"RoomCalendar"},
     *     @OA\Parameter(
     *         name="room_calendar",
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
    public function deleteApiV1AdminRoomCalendarsRoomCalendar(){}
}
