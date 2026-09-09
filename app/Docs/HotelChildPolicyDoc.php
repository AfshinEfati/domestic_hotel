<?php

namespace App\Docs;

/**
 * @OA\Tag(name="HotelChildPolicy")
 *
 * Note: This file contains OpenAPI annotations that can be processed by:
 * - zircote/swagger-php (https://github.com/zircote/swagger-php)
 * - darkaonline/l5-swagger (wrapper for swagger-php)
 *
 * These packages are optional. If not installed, you can still use
 * php artisan swagger:generate for JSON-based documentation.
 */
class HotelChildPolicyDoc
{

    /**
     * @OA\Schema(
     *     schema="HotelChildPolicyResource",
     *     type="object",
     *     required={"accommodation_id","max_infant_age","max_child_age","infant_when_disabled","child_when_disabled","infant_service_condition","child_service_condition","infant_pricing_type","child_pricing_type","status"},
     *     @OA\Property(property="accommodation_id", type="integer", example=1),
     *     @OA\Property(property="max_infant_age", type="integer", example=0),
     *     @OA\Property(property="max_child_age", type="integer", example=0),
     *     @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *     @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *     @OA\Property(property="infant_service_condition", type="string", example="any"),
     *     @OA\Property(property="child_service_condition", type="string", example="any"),
     *     @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *     @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *     @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *     @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *     @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *     @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *     @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *     @OA\Property(property="status", type="integer", example=1),
     *     example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
     * )
     */
    public function hotelChildPolicySchema(): void
    {
    }

    /**
     * @OA\Get(
     *      path="/api/v1/hotelchildpolicies",
     *      summary="List HotelChildPolicy",
     *      tags={"HotelChildPolicy"},
     *      @OA\Response(
     *              response=200,
     *              description="Successful response",
     *              @OA\JsonContent(
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="accommodation_id", type="integer", example=1),
     *                          @OA\Property(property="max_infant_age", type="integer", example=0),
     *                          @OA\Property(property="max_child_age", type="integer", example=0),
     *                          @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                          @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                          @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                          @OA\Property(property="child_service_condition", type="string", example="any"),
     *                          @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                          @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                          @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                          @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                          @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                          @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                          @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                          example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
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
     *      path="/api/v1/hotelchildpolicies",
     *      summary="Create HotelChildPolicy",
     *      tags={"HotelChildPolicy"},
     *      @OA\RequestBody(
     *              required=true,
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"accommodation_id","max_infant_age","max_child_age","infant_when_disabled","child_when_disabled","infant_service_condition","child_service_condition","infant_pricing_type","child_pricing_type","status"},
     *                      @OA\Property(property="accommodation_id", type="integer", example=1),
     *                      @OA\Property(property="max_infant_age", type="integer", example=0),
     *                      @OA\Property(property="max_child_age", type="integer", example=0),
     *                      @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                      @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                      @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                      @OA\Property(property="child_service_condition", type="string", example="any"),
     *                      @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                      @OA\Property(property="status", type="integer", example=1),
     *                      example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=201,
     *              description="Created",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"accommodation_id","max_infant_age","max_child_age","infant_when_disabled","child_when_disabled","infant_service_condition","child_service_condition","infant_pricing_type","child_pricing_type","status"},
     *                      @OA\Property(property="accommodation_id", type="integer", example=1),
     *                      @OA\Property(property="max_infant_age", type="integer", example=0),
     *                      @OA\Property(property="max_child_age", type="integer", example=0),
     *                      @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                      @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                      @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                      @OA\Property(property="child_service_condition", type="string", example="any"),
     *                      @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                      @OA\Property(property="status", type="integer", example=1),
     *                      example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
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
     *      path="/api/v1/hotelchildpolicies/{hotelChildPolicy}",
     *      summary="Show HotelChildPolicy",
     *      tags={"HotelChildPolicy"},
     *      @OA\Parameter(
     *              name="hotelChildPolicy",
     *              in="path",
     *              required=true,
     *              @OA\Schema(type="integer")
     *          ),
     *      @OA\Response(
     *              response=200,
     *              description="Successful response",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"accommodation_id","max_infant_age","max_child_age","infant_when_disabled","child_when_disabled","infant_service_condition","child_service_condition","infant_pricing_type","child_pricing_type","status"},
     *                      @OA\Property(property="accommodation_id", type="integer", example=1),
     *                      @OA\Property(property="max_infant_age", type="integer", example=0),
     *                      @OA\Property(property="max_child_age", type="integer", example=0),
     *                      @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                      @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                      @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                      @OA\Property(property="child_service_condition", type="string", example="any"),
     *                      @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                      @OA\Property(property="status", type="integer", example=1),
     *                      example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
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
     *      path="/api/v1/hotelchildpolicies/{hotelChildPolicy}",
     *      summary="Update HotelChildPolicy",
     *      tags={"HotelChildPolicy"},
     *      @OA\Parameter(
     *              name="hotelChildPolicy",
     *              in="path",
     *              required=true,
     *              @OA\Schema(type="integer")
     *          ),
     *      @OA\RequestBody(
     *              required=true,
     *              @OA\JsonContent(
     *                      type="object",
     *                      @OA\Property(property="accommodation_id", type="integer", example=1),
     *                      @OA\Property(property="max_infant_age", type="integer", example=0),
     *                      @OA\Property(property="max_child_age", type="integer", example=0),
     *                      @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                      @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                      @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                      @OA\Property(property="child_service_condition", type="string", example="any"),
     *                      @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                      @OA\Property(property="status", type="integer", example=1),
     *                      example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
     *                  )
     *          ),
     *      @OA\Response(
     *              response=200,
     *              description="Updated",
     *              @OA\JsonContent(
     *                      type="object",
     *                      required={"accommodation_id","max_infant_age","max_child_age","infant_when_disabled","child_when_disabled","infant_service_condition","child_service_condition","infant_pricing_type","child_pricing_type","status"},
     *                      @OA\Property(property="accommodation_id", type="integer", example=1),
     *                      @OA\Property(property="max_infant_age", type="integer", example=0),
     *                      @OA\Property(property="max_child_age", type="integer", example=0),
     *                      @OA\Property(property="infant_when_disabled", type="string", example="as_child"),
     *                      @OA\Property(property="child_when_disabled", type="string", example="as_adult"),
     *                      @OA\Property(property="infant_service_condition", type="string", example="any"),
     *                      @OA\Property(property="child_service_condition", type="string", example="any"),
     *                      @OA\Property(property="max_children_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="max_infants_covered", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="infant_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="infant_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="child_pricing_type", type="string", example="adult"),
     *                      @OA\Property(property="child_pricing_value", type="integer", nullable=true, example=42),
     *                      @OA\Property(property="description", type="string", nullable=true, example="Sample Description goes here."),
     *                      @OA\Property(property="status", type="integer", example=1),
     *                      example={"accommodation_id":1,"max_infant_age":"0","max_child_age":"0","infant_when_disabled":"as_child","child_when_disabled":"as_adult","infant_service_condition":"any","child_service_condition":"any","max_children_covered":42,"max_infants_covered":42,"infant_pricing_type":"adult","infant_pricing_value":42,"child_pricing_type":"adult","child_pricing_value":42,"description":"Sample Description goes here.","status":"1"}
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
     *      path="/api/v1/hotelchildpolicies/{hotelChildPolicy}",
     *      summary="Delete HotelChildPolicy",
     *      tags={"HotelChildPolicy"},
     *      @OA\Parameter(
     *              name="hotelChildPolicy",
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
