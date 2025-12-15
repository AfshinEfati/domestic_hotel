<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="City")
 */
class CityDoc
{
    /**
     * @OA\Schema(
     *     schema="CityResource",
     *     type="object",
     *     required={"id","country_id","fa_name","is_popular","is_active"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="country_id", type="integer", example=1),
     *     @OA\Property(property="state_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *     @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *     @OA\Property(property="osm_id", type="string", nullable=true, example="Osm Id"),
     *     @OA\Property(property="is_popular", type="integer", example=0),
     *     @OA\Property(property="is_active", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":99.99,"lng":99.99,"osm_id":"Osm Id","is_popular":"0","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function citySchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/cities",
     *     summary="List City",
     *     tags={"City"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(property="state_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                     @OA\Property(property="osm_id", type="string", nullable=true, example="Osm Id"),
     *                     @OA\Property(property="is_popular", type="integer", example=0),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":99.99,"lng":99.99,"osm_id":"Osm Id","is_popular":"0","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminCities(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/cities",
     *     summary="Create City",
     *     tags={"City"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","country_id","state_id","fa_name","en_name","lat","lng","osm_id","is_popular","is_active","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="state_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="lat", type="integer", example=42),
     *                 @OA\Property(property="lng", type="integer", example=42),
     *                 @OA\Property(property="osm_id", type="string", example="Osm Id"),
     *                 @OA\Property(property="is_popular", type="boolean", example=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":42,"lng":42,"osm_id":"Osm Id","is_popular":true,"is_active":true,"created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","country_id","fa_name","is_popular","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="state_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="osm_id", type="string", nullable=true, example="Osm Id"),
     *                 @OA\Property(property="is_popular", type="integer", example=0),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":99.99,"lng":99.99,"osm_id":"Osm Id","is_popular":"0","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminCities(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/cities/{city}",
     *     summary="Show City",
     *     tags={"City"},
     *     @OA\Parameter(
     *         name="city",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","country_id","fa_name","is_popular","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="state_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="osm_id", type="string", nullable=true, example="Osm Id"),
     *                 @OA\Property(property="is_popular", type="integer", example=0),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":99.99,"lng":99.99,"osm_id":"Osm Id","is_popular":"0","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminCitiesCity(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/cities/{city}",
     *     summary="Update City",
     *     tags={"City"},
     *     @OA\Parameter(
     *         name="city",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="state_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="lat", type="integer", example=42),
     *                 @OA\Property(property="lng", type="integer", example=42),
     *                 @OA\Property(property="osm_id", type="string", example="Osm Id"),
     *                 @OA\Property(property="is_popular", type="boolean", example=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":42,"lng":42,"osm_id":"Osm Id","is_popular":true,"is_active":true,"created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","country_id","fa_name","is_popular","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="state_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="lat", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="lng", type="number", format="float", nullable=true, example=99.99),
     *                 @OA\Property(property="osm_id", type="string", nullable=true, example="Osm Id"),
     *                 @OA\Property(property="is_popular", type="integer", example=0),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"country_id":1,"state_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","lat":99.99,"lng":99.99,"osm_id":"Osm Id","is_popular":"0","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminCitiesCity(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/cities/{city}",
     *     summary="Delete City",
     *     tags={"City"},
     *     @OA\Parameter(
     *         name="city",
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
    public function deleteApiV1AdminCitiesCity(){}
}
