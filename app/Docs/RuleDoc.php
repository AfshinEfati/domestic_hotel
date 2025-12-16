<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Rule")
 *
 * Note: This file contains OpenAPI annotations that can be processed by:
 * - zircote/swagger-php (https://github.com/zircote/swagger-php)
 * - darkaonline/l5-swagger (wrapper for swagger-php)
 *
 * These packages are optional. If not installed, you can still use
 * php artisan swagger:generate for JSON-based documentation.
 */
class RuleDoc
{

    /**
     * @OA\Schema(
     *     schema="RuleResource",
     *     type="object",
     *     required={"title","name","is_active"},
     *     @OA\Property(property="title", type="string", example="Sample Title"),
     *     @OA\Property(property="name", type="string", example="Sample Name"),
     *     @OA\Property(property="is_active", type="boolean", example="true"),
     *     example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     * )
     */
    public function ruleSchema(): void
    {
    }

    /**
     * @OA\Get(
     *      path="/api/v1/admin/rules",
     *      summary="List Rule",
     *      tags={"Rule"},
     *      @OA\Response(
     *              response=200,
     *              description="Successful response",
     *              @OA\JsonContent(
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="title", type="string", example="Sample Title"),
     *                          @OA\Property(property="name", type="string", example="Sample Name"),
     *                          @OA\Property(property="is_active", type="boolean", example="true"),
     *                          example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                      )
     *                  )
     *          ),
     *      @OA\Response(
     *              response=401,
     *              description="Unauthenticated",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Unauthenticated.")
     *              )
     *          )
     * )
     */
    public function index(): void
    {
    }

    /**
     * @OA\Post(
     *      path="/api/v1/admin/rules",
     *      summary="Create Rule",
     *      tags={"Rule"},
     *      @OA\RequestBody(
     *              required=true,
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"title","name","is_active"},
     *                      @OA\Property(property="title", type="string", example="Sample Title"),
     *                      @OA\Property(property="name", type="string", example="Sample Name"),
     *                      @OA\Property(property="is_active", type="boolean", example="true"),
     *                      example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=201,
     *              description="Created",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"title","name","is_active"},
     *                      @OA\Property(property="title", type="string", example="Sample Title"),
     *                      @OA\Property(property="name", type="string", example="Sample Name"),
     *                      @OA\Property(property="is_active", type="boolean", example="true"),
     *                      example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=401,
     *              description="Unauthenticated",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Unauthenticated.")
     *              )
     *          ),
     *      @OA\Response(
     *              response=422,
     *              description="Validation error",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="The given data was invalid.")
     *              )
     *          )
     * )
     */
    public function store(): void
    {
    }

    /**
     * @OA\Get(
     *      path="/api/v1/admin/rules/{rule}",
     *      summary="Show Rule",
     *      tags={"Rule"},
     *      @OA\Parameter(
     *              name="rule",
     *              in="path",
     *              required=true,
     *              @OA\Schema(type="integer")
     *          ),
     *      @OA\Response(
     *              response=200,
     *              description="Successful response",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"title","name","is_active"},
     *                      @OA\Property(property="title", type="string", example="Sample Title"),
     *                      @OA\Property(property="name", type="string", example="Sample Name"),
     *                      @OA\Property(property="is_active", type="boolean", example="true"),
     *                      example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=401,
     *              description="Unauthenticated",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Unauthenticated.")
     *              )
     *          ),
     *      @OA\Response(
     *              response=404,
     *              description="Not found",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Resource not found.")
     *              )
     *          )
     * )
     */
    public function show(): void
    {
    }

    /**
     * @OA\Put(
     *      path="/api/v1/admin/rules/{rule}",
     *      summary="Update Rule",
     *      tags={"Rule"},
     *      @OA\Parameter(
     *              name="rule",
     *              in="path",
     *              required=true,
     *              @OA\Schema(type="integer")
     *          ),
     *      @OA\RequestBody(
     *              required=true,
     *              @OA\JsonContent(
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Sample Title"),
     *                      @OA\Property(property="name", type="string", example="Sample Name"),
     *                      @OA\Property(property="is_active", type="boolean", example="true"),
     *                      example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=200,
     *              description="Updated",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"title","name","is_active"},
     *                      @OA\Property(property="title", type="string", example="Sample Title"),
     *                      @OA\Property(property="name", type="string", example="Sample Name"),
     *                      @OA\Property(property="is_active", type="boolean", example="true"),
     *                      example={"title":"Sample Title","name":"Sample Name","is_active":"true"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=401,
     *              description="Unauthenticated",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Unauthenticated.")
     *              )
     *          ),
     *      @OA\Response(
     *              response=422,
     *              description="Validation error",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="The given data was invalid.")
     *              )
     *          ),
     *      @OA\Response(
     *              response=404,
     *              description="Not found",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Resource not found.")
     *              )
     *          )
     * )
     */
    public function update(): void
    {
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/admin/rules/{rule}",
     *      summary="Delete Rule",
     *      tags={"Rule"},
     *      @OA\Parameter(
     *              name="rule",
     *              in="path",
     *              required=true,
     *              @OA\Schema(type="integer")
     *          ),
     *      @OA\Response(response=204, description="Deleted"),
     *      @OA\Response(
     *              response=401,
     *              description="Unauthenticated",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Unauthenticated.")
     *              )
     *          ),
     *      @OA\Response(
     *              response=404,
     *              description="Not found",
     *              @OA\JsonContent(
     *                  @OA\Property(property="message", type="string", example="Resource not found.")
     *              )
     *          )
     * )
     */
    public function destroy(): void
    {
    }

}
