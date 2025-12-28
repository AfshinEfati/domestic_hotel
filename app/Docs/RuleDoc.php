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
     *     required={"id","title","name","is_active"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="title", type="string", example="Sample Title"),
     *     @OA\Property(property="name", type="string", example="Sample Name"),
     *     @OA\Property(property="is_active", type="integer", example=1),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"title":"Sample Title","name":"Sample Name","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
     *                     @OA\Property(property="title", type="string", example="Sample Title"),
     *                     @OA\Property(property="name", type="string", example="Sample Name"),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"title":"Sample Title","name":"Sample Name","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
     *                 required={"title","name","is_active"},
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 example={"title":"Sample Title","name":"Sample Name","is_active":true}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","title","name","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"title":"Sample Title","name":"Sample Name","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
     *                 required={"id","title","name","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"title":"Sample Title","name":"Sample Name","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 example={"title":"Sample Title","name":"Sample Name","is_active":"1"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","title","name","is_active"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="name", type="string", example="Sample Name"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"title":"Sample Title","name":"Sample Name","is_active":"1","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
