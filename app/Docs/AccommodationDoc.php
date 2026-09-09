<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Accommodation")
 */
class AccommodationDoc
{
    /**
     * @OA\Schema(
     *     schema="AccommodationResource",
     *     type="object",
     *     required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="city_id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *     @OA\Property(property="star", type="integer", nullable=true, example=42),
     *     @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *     @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *     @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *     @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *     @OA\Property(property="is_active", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function accommodationSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodations",
     *     summary="List Accommodation",
     *     tags={"Accommodation"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="city_id", type="integer", example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                     @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                     @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                     @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminAccommodations(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/accommodations",
     *     summary="Create Accommodation",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","en_name","accommodation_type_id","star","grade","address","lat","lng","is_active","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="string", example="Star"),
     *                 @OA\Property(property="grade", type="string", example="Grade"),
     *                 @OA\Property(property="address", type="string", example="Address"),
     *                 @OA\Property(property="lat", type="string", example="Lat"),
     *                 @OA\Property(property="lng", type="string", example="Lng"),
     *                 @OA\Property(property="is_active", type="string", example="Is Active"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":"Star","grade":"Grade","address":"Address","lat":"Lat","lng":"Lng","is_active":"Is Active","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminAccommodations(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     summary="Show Accommodation",
     *     tags={"Accommodation"},
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminAccommodationsAccommodation(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     summary="Update Accommodation",
     *     tags={"Accommodation"},
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="string", example="Star"),
     *                 @OA\Property(property="grade", type="string", example="Grade"),
     *                 @OA\Property(property="address", type="string", example="Address"),
     *                 @OA\Property(property="lat", type="string", example="Lat"),
     *                 @OA\Property(property="lng", type="string", example="Lng"),
     *                 @OA\Property(property="is_active", type="string", example="Is Active"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":"Star","grade":"Grade","address":"Address","lat":"Lat","lng":"Lng","is_active":"Is Active","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminAccommodationsAccommodation(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     summary="Delete Accommodation",
     *     tags={"Accommodation"},
     *     @OA\Parameter(
     *         name="accommodation",
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
    public function deleteApiV1AdminAccommodationsAccommodation(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/accommodations/list",
     *     summary="List",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="city_id", type="integer", example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                     @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                     @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                     @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     *                 )
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
    public function postApiV1FrontAccommodationsList(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/accommodations/availability",
     *     summary="Getavailability",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"state","city","check_in","check_out","rooms","rooms.*.passengers","rooms.*.passengers.*.type","rooms.*.passengers.*.age","rooms.*.passengers.*.title"},
     *                 @OA\Property(property="state", type="string", example="State"),
     *                 @OA\Property(property="city", type="string", example="City"),
     *                 @OA\Property(property="check_in", type="string", example="Check In"),
     *                 @OA\Property(property="check_out", type="string", example="Check Out"),
     *                 @OA\Property(property="rooms", type="array", example="Rooms", @OA\Items(type="object")),
     *                 @OA\Property(property="rooms.*.passengers", type="array", example="Rooms.*.Passengers", @OA\Items(type="object")),
     *                 @OA\Property(property="rooms.*.passengers.*.type", type="string", example="Rooms.*.Passengers.*.Type"),
     *                 @OA\Property(property="rooms.*.passengers.*.age", type="integer", example=42),
     *                 @OA\Property(property="rooms.*.passengers.*.title", type="string", example="Sample Rooms.*.Passengers.*.Title"),
     *                 example={"state":"State","city":"City","check_in":"Check In","check_out":"Check Out","rooms":"Rooms","rooms.*.passengers":"Rooms.*.Passengers","rooms.*.passengers.*.type":"Rooms.*.Passengers.*.Type","rooms.*.passengers.*.age":42,"rooms.*.passengers.*.title":"Sample Rooms.*.Passengers.*.Title"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontAccommodationsAvailability(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/facility-groups/list",
     *     summary="Getfacilitygroups",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontFacilityGroupsList(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/facilities/list",
     *     summary="Getfacilities",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontFacilitiesList(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/room-types/list",
     *     summary="Getroomtype",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontRoomTypesList(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/rules/list",
     *     summary="Getrules",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontRulesList(){}

    /**
     * @OA\Post(
     *     path="/api/v1/front/rules/child-policy",
     *     summary="Getchildpolicy",
     *     tags={"Accommodation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"from","to"},
     *                 @OA\Property(property="from", type="integer", example=42),
     *                 @OA\Property(property="to", type="integer", example=42),
     *                 example={"from":42,"to":42}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","city_id","fa_name","accommodation_type_id","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="accommodation_type_id", type="integer", example=1),
     *                 @OA\Property(property="star", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="grade", type="string", nullable=true, example="Grade"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="Address"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"city_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","accommodation_type_id":1,"star":42,"grade":"Grade","address":"Address","lat":99.99,"lng":99.99,"is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1FrontRulesChildPolicy(){}
}
