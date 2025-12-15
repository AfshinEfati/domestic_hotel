<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Country")
 */
class CountryDoc
{
    /**
     * @OA\Schema(
     *     schema="CountryResource",
     *     type="object",
     *     required={"id","fa_name"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="iso2", type="string", nullable=true, example="Iso2"),
     *     @OA\Property(property="iso3", type="string", nullable=true, example="Iso3"),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function countrySchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/countries",
     *     summary="List Country",
     *     tags={"Country"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="iso2", type="string", nullable=true, example="Iso2"),
     *                     @OA\Property(property="iso3", type="string", nullable=true, example="Iso3"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminCountries(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/countries",
     *     summary="Create Country",
     *     tags={"Country"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name","en_name","iso2","iso3","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="iso2", type="string", example="Iso2"),
     *                 @OA\Property(property="iso3", type="string", example="Iso3"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="iso2", type="string", nullable=true, example="Iso2"),
     *                 @OA\Property(property="iso3", type="string", nullable=true, example="Iso3"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminCountries(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/countries/{country}",
     *     summary="Show Country",
     *     tags={"Country"},
     *     @OA\Parameter(
     *         name="country",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="iso2", type="string", nullable=true, example="Iso2"),
     *                 @OA\Property(property="iso3", type="string", nullable=true, example="Iso3"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminCountriesCountry(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/countries/{country}",
     *     summary="Update Country",
     *     tags={"Country"},
     *     @OA\Parameter(
     *         name="country",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="iso2", type="string", example="Iso2"),
     *                 @OA\Property(property="iso3", type="string", example="Iso3"),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="iso2", type="string", nullable=true, example="Iso2"),
     *                 @OA\Property(property="iso3", type="string", nullable=true, example="Iso3"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","iso2":"Iso2","iso3":"Iso3","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminCountriesCountry(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/countries/{country}",
     *     summary="Delete Country",
     *     tags={"Country"},
     *     @OA\Parameter(
     *         name="country",
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
    public function deleteApiV1AdminCountriesCountry(){}
}
