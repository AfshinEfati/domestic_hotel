<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="System Setting",
 *     description="Runtime business configuration for Domestic Hotel GDS. These values are stored in the database and are intended to be managed by Admin APIs instead of config files or environment variables."
 * )
 */
class SystemSettingDoc
{
    /**
     * @OA\Schema(
     *     schema="SystemSettingResource",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="key", type="string", example="pricing.default_percentage"),
     *     @OA\Property(property="group", type="string", example="pricing"),
     *     @OA\Property(property="value", type="string", example="5", description="Serialized stored value. The application resolves it using value_type when reading the setting."),
     *     @OA\Property(property="value_type", type="string", enum={"string","integer","float","boolean","json"}, example="integer"),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(
     *         property="created_at",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="date", type="string", format="date", example="2026-09-10"),
     *         @OA\Property(property="time", type="string", example="10:30:00"),
     *         @OA\Property(property="fa_date", type="string", example="1405-06-19"),
     *         @OA\Property(property="iso", type="string", format="date-time", example="2026-09-10T10:30:00+00:00")
     *     ),
     *     @OA\Property(
     *         property="updated_at",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="date", type="string", format="date", example="2026-09-10"),
     *         @OA\Property(property="time", type="string", example="10:30:00"),
     *         @OA\Property(property="fa_date", type="string", example="1405-06-19"),
     *         @OA\Property(property="iso", type="string", format="date-time", example="2026-09-10T10:30:00+00:00")
     *     )
     * )
     */
    public function systemSettingSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/system-settings",
     *     summary="List system settings",
     *     tags={"System Setting"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SystemSettingResource")
     *             )
     *         )
     *     )
     * )
     */
    public function getApiV1AdminSystemSettings(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/system-settings",
     *     summary="Create system setting",
     *     tags={"System Setting"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"key","group","value","value_type","is_active"},
     *             @OA\Property(property="key", type="string", maxLength=150, example="pricing.test_percentage"),
     *             @OA\Property(property="group", type="string", maxLength=100, example="pricing"),
     *             @OA\Property(property="value", example=5, description="Value may be string, integer, float, boolean, object or array depending on value_type."),
     *             @OA\Property(property="value_type", type="string", enum={"string","integer","float","boolean","json"}, example="integer"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"key":"pricing.test_percentage","group":"pricing","value":5,"value_type":"integer","is_active":true}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="created"),
     *             @OA\Property(property="data", ref="#/components/schemas/SystemSettingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The key has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function postApiV1AdminSystemSettings(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/system-settings/{system_setting}",
     *     summary="Show system setting",
     *     tags={"System Setting"},
     *     @OA\Parameter(
     *         name="system_setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/SystemSettingResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function getApiV1AdminSystemSettingsSystemSetting(): void
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/system-settings/{system_setting}",
     *     summary="Replace/update system setting",
     *     tags={"System Setting"},
     *     @OA\Parameter(
     *         name="system_setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="key", type="string", maxLength=150, example="pricing.default_percentage"),
     *             @OA\Property(property="group", type="string", maxLength=100, example="pricing"),
     *             @OA\Property(property="value", example=7, description="Value may be string, integer, float, boolean, object or array depending on value_type."),
     *             @OA\Property(property="value_type", type="string", enum={"string","integer","float","boolean","json"}, example="integer"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"key":"pricing.default_percentage","group":"pricing","value":7,"value_type":"integer","is_active":true}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/SystemSettingResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function putApiV1AdminSystemSettingsSystemSetting(): void
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/system-settings/{system_setting}",
     *     summary="Partially update system setting",
     *     tags={"System Setting"},
     *     @OA\Parameter(
     *         name="system_setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="key", type="string", maxLength=150, example="pricing.default_percentage"),
     *             @OA\Property(property="group", type="string", maxLength=100, example="pricing"),
     *             @OA\Property(property="value", example=7, description="When value_type is omitted, the existing value_type is used by the service."),
     *             @OA\Property(property="value_type", type="string", enum={"string","integer","float","boolean","json"}, example="integer"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"value":7}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/SystemSettingResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function patchApiV1AdminSystemSettingsSystemSetting(): void
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/system-settings/{system_setting}",
     *     summary="Delete system setting",
     *     tags={"System Setting"},
     *     @OA\Parameter(
     *         name="system_setting",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="deleted"),
     *             @OA\Property(property="data", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteApiV1AdminSystemSettingsSystemSetting(): void
    {
    }
}
