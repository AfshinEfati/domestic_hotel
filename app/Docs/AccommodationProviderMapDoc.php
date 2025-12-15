<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="AccommodationProviderMap")
 */
class AccommodationProviderMapDoc
{
    /**
     * @OA\Schema(
     *     schema="AccommodationProviderMapResource",
     *     type="object",
     *     required={"id","accommodation_id","provider_id","provider_property_id"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="accommodation_id", type="integer", example=1),
     *     @OA\Property(property="provider_id", type="integer", example=1),
     *     @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *     @OA\Property(property="fa_name", type="string", nullable=true, example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function accommodationProviderMapSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-provider-maps",
     *     summary="List AccommodationProviderMap",
     *     tags={"AccommodationProviderMap"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="accommodation_id", type="integer", example=1),
     *                     @OA\Property(property="provider_id", type="integer", example=1),
     *                     @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                     @OA\Property(property="fa_name", type="string", nullable=true, example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminAccommodationProviderMaps(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/accommodation-provider-maps",
     *     summary="Create AccommodationProviderMap",
     *     tags={"AccommodationProviderMap"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","provider_id","provider_property_id","fa_name","en_name","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","provider_id","provider_property_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="fa_name", type="string", nullable=true, example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminAccommodationProviderMaps(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     summary="Show AccommodationProviderMap",
     *     tags={"AccommodationProviderMap"},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","provider_id","provider_property_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="fa_name", type="string", nullable=true, example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminAccommodationProviderMapsAccommodationProviderMap(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     summary="Update AccommodationProviderMap",
     *     tags={"AccommodationProviderMap"},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
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
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","provider_id","provider_property_id"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="provider_id", type="integer", example=1),
     *                 @OA\Property(property="provider_property_id", type="string", example="Provider Property Id"),
     *                 @OA\Property(property="fa_name", type="string", nullable=true, example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"provider_id":1,"provider_property_id":"Provider Property Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminAccommodationProviderMapsAccommodationProviderMap(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     summary="Delete AccommodationProviderMap",
     *     tags={"AccommodationProviderMap"},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
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
    public function deleteApiV1AdminAccommodationProviderMapsAccommodationProviderMap(){}
}
