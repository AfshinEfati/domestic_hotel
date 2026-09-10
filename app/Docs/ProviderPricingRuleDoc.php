<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Provider Pricing Rule",
 *     description="Runtime pricing rules used to calculate final sell rates per provider."
 * )
 */
class ProviderPricingRuleDoc
{
    /**
     * @OA\Schema(
     *     schema="ProviderPricingRuleResource",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="provider_id", type="integer", example=1),
     *     @OA\Property(property="percentage", type="number", format="float", example=5),
     *     @OA\Property(property="fixed_amount", type="integer", format="int64", example=0),
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
    public function providerPricingRuleSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/provider-pricing-rules",
     *     summary="List provider pricing rules",
     *     tags={"Provider Pricing Rule"},
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
     *                 @OA\Items(ref="#/components/schemas/ProviderPricingRuleResource")
     *             )
     *         )
     *     )
     * )
     */
    public function getApiV1AdminProviderPricingRules(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/provider-pricing-rules",
     *     summary="Create provider pricing rule",
     *     description="Each provider can have only one pricing rule. The final rate is calculated from the provider source rate using percentage plus fixed_amount.",
     *     tags={"Provider Pricing Rule"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"provider_id","percentage","fixed_amount","is_active"},
     *             @OA\Property(property="provider_id", type="integer", example=1),
     *             @OA\Property(property="percentage", type="number", format="float", minimum=0, example=5),
     *             @OA\Property(property="fixed_amount", type="integer", format="int64", minimum=0, example=0),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"provider_id":1,"percentage":5,"fixed_amount":0,"is_active":true}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="created"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProviderPricingRuleResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error. provider_id must exist and must not already have a pricing rule.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The provider id has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function postApiV1AdminProviderPricingRules(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/provider-pricing-rules/{provider_pricing_rule}",
     *     summary="Show provider pricing rule",
     *     tags={"Provider Pricing Rule"},
     *     @OA\Parameter(
     *         name="provider_pricing_rule",
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
     *             @OA\Property(property="data", ref="#/components/schemas/ProviderPricingRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function getApiV1AdminProviderPricingRulesProviderPricingRule(): void
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/provider-pricing-rules/{provider_pricing_rule}",
     *     summary="Replace/update provider pricing rule",
     *     tags={"Provider Pricing Rule"},
     *     @OA\Parameter(
     *         name="provider_pricing_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="provider_id", type="integer", example=1),
     *             @OA\Property(property="percentage", type="number", format="float", minimum=0, example=7),
     *             @OA\Property(property="fixed_amount", type="integer", format="int64", minimum=0, example=100000),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"provider_id":1,"percentage":7,"fixed_amount":100000,"is_active":true}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProviderPricingRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function putApiV1AdminProviderPricingRulesProviderPricingRule(): void
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/provider-pricing-rules/{provider_pricing_rule}",
     *     summary="Partially update provider pricing rule",
     *     tags={"Provider Pricing Rule"},
     *     @OA\Parameter(
     *         name="provider_pricing_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="provider_id", type="integer", example=1),
     *             @OA\Property(property="percentage", type="number", format="float", minimum=0, example=7),
     *             @OA\Property(property="fixed_amount", type="integer", format="int64", minimum=0, example=100000),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={"percentage":7,"fixed_amount":100000}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProviderPricingRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function patchApiV1AdminProviderPricingRulesProviderPricingRule(): void
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/provider-pricing-rules/{provider_pricing_rule}",
     *     summary="Delete provider pricing rule",
     *     tags={"Provider Pricing Rule"},
     *     @OA\Parameter(
     *         name="provider_pricing_rule",
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
    public function deleteApiV1AdminProviderPricingRulesProviderPricingRule(): void
    {
    }
}
