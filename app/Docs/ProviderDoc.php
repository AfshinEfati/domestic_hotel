<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Provider")
 */
class ProviderDoc
{
    /**
     * @OA\Schema(
     *     schema="ProviderResource",
     *     type="object",
     *     required={"id","fa_name","code","is_active"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="class", type="string", nullable=true, example="Class"),
     *     @OA\Property(property="code", type="string", example="Code"),
     *     @OA\Property(property="config", type="object", nullable=true, example="Config"),
     *     @OA\Property(property="is_active", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","class":"Class","code":"Code","config":"Config","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function providerSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/providers",
     *     summary="List Provider",
     *     tags={"Provider"},
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
     *                     @OA\Property(property="class", type="string", nullable=true, example="Class"),
     *                     @OA\Property(property="code", type="string", example="Code"),
     *                     @OA\Property(property="config", type="object", nullable=true, example="Config"),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","class":"Class","code":"Code","config":"Config","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminProviders(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/providers",
     *     summary="Create Provider",
     *     tags={"Provider"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name","en_name","code","config","is_active","created_at","updated_at","auth_token","expire_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="code", type="string", example="Code"),
     *                 @OA\Property(property="config", type="array", example="Config", @OA\Items(type="object")),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 @OA\Property(property="auth_token", type="string", example="Auth Token"),
     *                 @OA\Property(property="expire_at", type="string", example="Expire At"),
     *                 example={"id":"Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","code":"Code","config":"Config","is_active":true,"created_at":"Created At","updated_at":"Updated At","auth_token":"Auth Token","expire_at":"Expire At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name","code","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="class", type="string", nullable=true, example="Class"),
     *                 @OA\Property(property="code", type="string", example="Code"),
     *                 @OA\Property(property="config", type="object", nullable=true, example="Config"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","class":"Class","code":"Code","config":"Config","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminProviders(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/providers/{provider}",
     *     summary="Show Provider",
     *     tags={"Provider"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name","code","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="class", type="string", nullable=true, example="Class"),
     *                 @OA\Property(property="code", type="string", example="Code"),
     *                 @OA\Property(property="config", type="object", nullable=true, example="Config"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","class":"Class","code":"Code","config":"Config","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminProvidersProvider(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/providers/{provider}",
     *     summary="Update Provider",
     *     tags={"Provider"},
     *     @OA\Parameter(
     *         name="provider",
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
     *                 @OA\Property(property="code", type="string", example="Code"),
     *                 @OA\Property(property="config", type="array", example="Config", @OA\Items(type="object")),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 @OA\Property(property="auth_token", type="string", example="Auth Token"),
     *                 @OA\Property(property="expire_at", type="string", example="Expire At"),
     *                 example={"id":"Id","fa_name":"Sample Fa Name","en_name":"Sample En Name","code":"Code","config":"Config","is_active":true,"created_at":"Created At","updated_at":"Updated At","auth_token":"Auth Token","expire_at":"Expire At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","fa_name","code","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="class", type="string", nullable=true, example="Class"),
     *                 @OA\Property(property="code", type="string", example="Code"),
     *                 @OA\Property(property="config", type="object", nullable=true, example="Config"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","class":"Class","code":"Code","config":"Config","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminProvidersProvider(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/providers/{provider}",
     *     summary="Delete Provider",
     *     tags={"Provider"},
     *     @OA\Parameter(
     *         name="provider",
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
    public function deleteApiV1AdminProvidersProvider(){}
}
