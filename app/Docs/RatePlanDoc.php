<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="RatePlan")
 */
class RatePlanDoc
{
    /**
     * @OA\Schema(
     *     schema="RatePlanResource",
     *     type="object",
     *     required={"id","accommodation_id","fa_name","cancelable"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="accommodation_id", type="integer", example=1),
     *     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *     @OA\Property(property="meal_type", type="string", nullable=true, example="Meal Type"),
     *     @OA\Property(property="food_board_type", type="string", nullable=true, example="Food Board Type"),
     *     @OA\Property(property="cancelable", type="integer", example=1),
     *     @OA\Property(property="sleeps", type="integer", nullable=true, example=42),
     *     @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *     @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *     @OA\Property(property="facilities", type="object", nullable=true, example="Facilities"),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *     example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":"1","sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
     * )
     */
    public function ratePlanSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plans",
     *     summary="List RatePlan",
     *     tags={"RatePlan"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="accommodation_id", type="integer", example=1),
     *                     @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                     @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                     @OA\Property(property="meal_type", type="string", nullable=true, example="Meal Type"),
     *                     @OA\Property(property="food_board_type", type="string", nullable=true, example="Food Board Type"),
     *                     @OA\Property(property="cancelable", type="integer", example=1),
     *                     @OA\Property(property="sleeps", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                     @OA\Property(property="facilities", type="object", nullable=true, example="Facilities"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                     example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":"1","sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRatePlans(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rate-plans",
     *     summary="Create RatePlan",
     *     tags={"RatePlan"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","en_name","meal_type","food_board_type","cancelable","sleeps","min_stay","max_stay","facilities","created_at","updated_at"},
     *                 @OA\Property(property="id", type="string", example="Id"),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="meal_type", type="string", example="Meal Type"),
     *                 @OA\Property(property="food_board_type", type="string", example="Food Board Type"),
     *                 @OA\Property(property="cancelable", type="boolean", example=true),
     *                 @OA\Property(property="sleeps", type="integer", example=42),
     *                 @OA\Property(property="min_stay", type="integer", example=42),
     *                 @OA\Property(property="max_stay", type="integer", example=42),
     *                 @OA\Property(property="facilities", type="array", example="Facilities", @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":true,"sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","cancelable"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="meal_type", type="string", nullable=true, example="Meal Type"),
     *                 @OA\Property(property="food_board_type", type="string", nullable=true, example="Food Board Type"),
     *                 @OA\Property(property="cancelable", type="integer", example=1),
     *                 @OA\Property(property="sleeps", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="facilities", type="object", nullable=true, example="Facilities"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":"1","sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function postApiV1AdminRatePlans(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     summary="Show RatePlan",
     *     tags={"RatePlan"},
     *     @OA\Parameter(
     *         name="rate_plan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","cancelable"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="meal_type", type="string", nullable=true, example="Meal Type"),
     *                 @OA\Property(property="food_board_type", type="string", nullable=true, example="Food Board Type"),
     *                 @OA\Property(property="cancelable", type="integer", example=1),
     *                 @OA\Property(property="sleeps", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="facilities", type="object", nullable=true, example="Facilities"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":"1","sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function getApiV1AdminRatePlansRatePlan(){}

    /**
     * @OA\Put(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     summary="Update RatePlan",
     *     tags={"RatePlan"},
     *     @OA\Parameter(
     *         name="rate_plan",
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
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", example="Sample En Name"),
     *                 @OA\Property(property="meal_type", type="string", example="Meal Type"),
     *                 @OA\Property(property="food_board_type", type="string", example="Food Board Type"),
     *                 @OA\Property(property="cancelable", type="boolean", example=true),
     *                 @OA\Property(property="sleeps", type="integer", example=42),
     *                 @OA\Property(property="min_stay", type="integer", example=42),
     *                 @OA\Property(property="max_stay", type="integer", example=42),
     *                 @OA\Property(property="facilities", type="array", example="Facilities", @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", example="Created At"),
     *                 @OA\Property(property="updated_at", type="string", example="Updated At"),
     *                 example={"id":"Id","accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":true,"sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"Created At","updated_at":"Updated At"}
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *                 type="object",
     *                 required={"id","accommodation_id","fa_name","cancelable"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="accommodation_id", type="integer", example=1),
     *                 @OA\Property(property="fa_name", type="string", example="Sample Fa Name"),
     *                 @OA\Property(property="en_name", type="string", nullable=true, example="Sample En Name"),
     *                 @OA\Property(property="meal_type", type="string", nullable=true, example="Meal Type"),
     *                 @OA\Property(property="food_board_type", type="string", nullable=true, example="Food Board Type"),
     *                 @OA\Property(property="cancelable", type="integer", example=1),
     *                 @OA\Property(property="sleeps", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="min_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="max_stay", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="facilities", type="object", nullable=true, example="Facilities"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T10:00:00Z"),
     *                 example={"id":1,"accommodation_id":1,"fa_name":"Sample Fa Name","en_name":"Sample En Name","meal_type":"Meal Type","food_board_type":"Food Board Type","cancelable":"1","sleeps":42,"min_stay":42,"max_stay":42,"facilities":"Facilities","created_at":"2024-01-01T10:00:00Z","updated_at":"2024-01-01T10:00:00Z"}
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
    public function putApiV1AdminRatePlansRatePlan(){}

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     summary="Delete RatePlan",
     *     tags={"RatePlan"},
     *     @OA\Parameter(
     *         name="rate_plan",
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
    public function deleteApiV1AdminRatePlansRatePlan(){}
}
