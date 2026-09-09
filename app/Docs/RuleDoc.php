<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Rule")
 */
class RuleDoc
{
    /**
     * @OA\Schema(
     *     schema="RuleResource",
     *     type="object",
     *     required={"id","hotel_id","provider_rule_id","status"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="hotel_id", type="integer", example=1),
     *     @OA\Property(property="rule_category_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *     @OA\Property(property="rule_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="type", type="string", nullable=true, example="Type"),
     *     @OA\Property(property="name", type="string", nullable=true, example="Sample Name"),
     *     @OA\Property(property="name_ar", type="string", nullable=true, example="Sample Name Ar"),
     *     @OA\Property(property="name_en", type="string", nullable=true, example="Sample Name En"),
     *     @OA\Property(property="conditions", type="object", nullable=true, example="Conditions"),
     *     @OA\Property(property="room_type_id", type="string", nullable=true, example="Room Type Id"),
     *     @OA\Property(property="rate_plan_id", type="string", nullable=true, example="Rate Plan Id"),
     *     @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *     @OA\Property(property="description_ar", type="string", nullable=true, example="Sample Description Ar goes here."),
     *     @OA\Property(property="description_en", type="string", nullable=true, example="Sample Description En goes here."),
     *     @OA\Property(property="status", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function ruleSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rules",
     *     summary="List Rule",
     *     tags={"Rule"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="hotel_id", type="integer", example=1),
     *                     @OA\Property(property="rule_category_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                     @OA\Property(property="rule_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="type", type="string", nullable=true, example="Type"),
     *                     @OA\Property(property="name", type="string", nullable=true, example="Sample Name"),
     *                     @OA\Property(property="name_ar", type="string", nullable=true, example="Sample Name Ar"),
     *                     @OA\Property(property="name_en", type="string", nullable=true, example="Sample Name En"),
     *                     @OA\Property(property="conditions", type="object", nullable=true, example="Conditions"),
     *                     @OA\Property(property="room_type_id", type="string", nullable=true, example="Room Type Id"),
     *                     @OA\Property(property="rate_plan_id", type="string", nullable=true, example="Rate Plan Id"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                     @OA\Property(property="description_ar", type="string", nullable=true, example="Sample Description Ar goes here."),
     *                     @OA\Property(property="description_en", type="string", nullable=true, example="Sample Description En goes here."),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRules(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rules",
     *     summary="Create Rule",
     *     tags={"Rule"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"hotel_id","rule_category_id","provider_rule_id","rule_id","type","name","name_ar","name_en","conditions","room_type_id","rate_plan_id","description","description_ar","description_en","status"},
     *                 @OA\Property(property="hotel_id", type="integer", example=1),
     *                 @OA\Property(property="rule_category_id", type="integer", example=1),
     *                 @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                 @OA\Property(property="rule_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="Type"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="name_ar", type="string", example="Sample Name Ar"),
     *                 @OA\Property(property="name_en", type="string", example="Sample Name En"),
     *                 @OA\Property(property="conditions", type="array", example="Conditions", @OA\Items(type="object")),
     *                 @OA\Property(property="room_type_id", type="string", example="Room Type Id"),
     *                 @OA\Property(property="rate_plan_id", type="string", example="Rate Plan Id"),
     *                 @OA\Property(property="description", type="string", example="Sample Description goes here."),
     *                 @OA\Property(property="description_ar", type="string", example="Sample Description Ar goes here."),
     *                 @OA\Property(property="description_en", type="string", example="Sample Description En goes here."),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 example={"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":true}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","hotel_id","provider_rule_id","status"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="hotel_id", type="integer", example=1),
     *                 @OA\Property(property="rule_category_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                 @OA\Property(property="rule_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="type", type="string", nullable=true, example="Type"),
     *                 @OA\Property(property="name", type="string", nullable=true, example="Sample Name"),
     *                 @OA\Property(property="name_ar", type="string", nullable=true, example="Sample Name Ar"),
     *                 @OA\Property(property="name_en", type="string", nullable=true, example="Sample Name En"),
     *                 @OA\Property(property="conditions", type="object", nullable=true, example="Conditions"),
     *                 @OA\Property(property="room_type_id", type="string", nullable=true, example="Room Type Id"),
     *                 @OA\Property(property="rate_plan_id", type="string", nullable=true, example="Rate Plan Id"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                 @OA\Property(property="description_ar", type="string", nullable=true, example="Sample Description Ar goes here."),
     *                 @OA\Property(property="description_en", type="string", nullable=true, example="Sample Description En goes here."),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminRules(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rules/{rule}",
     *     summary="Show Rule",
     *     tags={"Rule"},
     *     @OA\Parameter(
     *         name="rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","hotel_id","provider_rule_id","status"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="hotel_id", type="integer", example=1),
     *                 @OA\Property(property="rule_category_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                 @OA\Property(property="rule_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="type", type="string", nullable=true, example="Type"),
     *                 @OA\Property(property="name", type="string", nullable=true, example="Sample Name"),
     *                 @OA\Property(property="name_ar", type="string", nullable=true, example="Sample Name Ar"),
     *                 @OA\Property(property="name_en", type="string", nullable=true, example="Sample Name En"),
     *                 @OA\Property(property="conditions", type="object", nullable=true, example="Conditions"),
     *                 @OA\Property(property="room_type_id", type="string", nullable=true, example="Room Type Id"),
     *                 @OA\Property(property="rate_plan_id", type="string", nullable=true, example="Rate Plan Id"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                 @OA\Property(property="description_ar", type="string", nullable=true, example="Sample Description Ar goes here."),
     *                 @OA\Property(property="description_en", type="string", nullable=true, example="Sample Description En goes here."),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRulesRule(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/rules/{rule}",
     *     summary="Update Rule",
     *     tags={"Rule"},
     *     @OA\Parameter(
     *         name="rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="hotel_id", type="integer", example=1),
     *                 @OA\Property(property="rule_category_id", type="integer", example=1),
     *                 @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                 @OA\Property(property="rule_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="Type"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="name_ar", type="string", example="Sample Name Ar"),
     *                 @OA\Property(property="name_en", type="string", example="Sample Name En"),
     *                 @OA\Property(property="conditions", type="array", example="Conditions", @OA\Items(type="object")),
     *                 @OA\Property(property="room_type_id", type="string", example="Room Type Id"),
     *                 @OA\Property(property="rate_plan_id", type="string", example="Rate Plan Id"),
     *                 @OA\Property(property="description", type="string", example="Sample Description goes here."),
     *                 @OA\Property(property="description_ar", type="string", example="Sample Description Ar goes here."),
     *                 @OA\Property(property="description_en", type="string", example="Sample Description En goes here."),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 example={"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":true}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","hotel_id","provider_rule_id","status"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="hotel_id", type="integer", example=1),
     *                 @OA\Property(property="rule_category_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="provider_rule_id", type="string", example="Provider Rule Id"),
     *                 @OA\Property(property="rule_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="type", type="string", nullable=true, example="Type"),
     *                 @OA\Property(property="name", type="string", nullable=true, example="Sample Name"),
     *                 @OA\Property(property="name_ar", type="string", nullable=true, example="Sample Name Ar"),
     *                 @OA\Property(property="name_en", type="string", nullable=true, example="Sample Name En"),
     *                 @OA\Property(property="conditions", type="object", nullable=true, example="Conditions"),
     *                 @OA\Property(property="room_type_id", type="string", nullable=true, example="Room Type Id"),
     *                 @OA\Property(property="rate_plan_id", type="string", nullable=true, example="Rate Plan Id"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                 @OA\Property(property="description_ar", type="string", nullable=true, example="Sample Description Ar goes here."),
     *                 @OA\Property(property="description_en", type="string", nullable=true, example="Sample Description En goes here."),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"hotel_id":1,"rule_category_id":1,"provider_rule_id":"Provider Rule Id","rule_id":1,"type":"Type","name":"Sample Name","name_ar":"Sample Name Ar","name_en":"Sample Name En","conditions":"Conditions","room_type_id":"Room Type Id","rate_plan_id":"Rate Plan Id","description":"Sample Description goes here.","description_ar":"Sample Description Ar goes here.","description_en":"Sample Description En goes here.","status":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminRulesRule(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rules/{rule}",
     *     summary="Delete Rule",
     *     tags={"Rule"},
     *     @OA\Parameter(
     *         name="rule",
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
    public function deleteApiV1AdminRulesRule(){}
}
