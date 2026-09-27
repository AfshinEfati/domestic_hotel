<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Purchase Manual Rule",
 *     description="Admin rules that force reservation purchases into the offline/manual flow."
 * )
 */
class PurchaseManualRuleDoc
{
    /**
     * @OA\Schema(
     *     schema="PurchaseManualRuleResource",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="All providers offline"),
     *     @OA\Property(
     *         property="provider_id",
     *         type="integer",
     *         nullable=true,
     *         example=1,
     *         description="Null means the rule is not limited to a provider."
     *     ),
     *     @OA\Property(
     *         property="accommodation_id",
     *         type="integer",
     *         nullable=true,
     *         example=3,
     *         description="Null means the rule is not limited to an accommodation."
     *     ),
     *     @OA\Property(
     *         property="minimum_amount",
     *         type="integer",
     *         format="int64",
     *         nullable=true,
     *         example=100000000,
     *         description="Minimum provider purchase amount in IRR. Null means no minimum."
     *     ),
     *     @OA\Property(
     *         property="maximum_amount",
     *         type="integer",
     *         format="int64",
     *         nullable=true,
     *         example=500000000,
     *         description="Maximum provider purchase amount in IRR. Null means no maximum."
     *     ),
     *     @OA\Property(
     *         property="start_time",
     *         type="string",
     *         nullable=true,
     *         example="21:00",
     *         description="Daily start time in Asia/Tehran. Null means no lower time bound."
     *     ),
     *     @OA\Property(
     *         property="end_time",
     *         type="string",
     *         nullable=true,
     *         example="09:00",
     *         description="Daily end time in Asia/Tehran. Overnight ranges such as 21:00 to 09:00 are supported."
     *     ),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(property="provider", type="object", nullable=true),
     *     @OA\Property(property="accommodation", type="object", nullable=true),
     *     @OA\Property(property="created_at", type="object", nullable=true),
     *     @OA\Property(property="updated_at", type="object", nullable=true)
     * )
     */
    public function purchaseManualRuleSchema(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/purchase-manual-rules",
     *     summary="List offline purchase rules",
     *     tags={"Purchase Manual Rule"},
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
     *                 @OA\Items(ref="#/components/schemas/PurchaseManualRuleResource")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/purchase-manual-rules",
     *     summary="Create offline purchase rule",
     *     description="All non-null conditions in one rule must match together. Null provider/accommodation/amount/time fields act as wildcards. A rule with every optional condition null applies globally.",
     *     tags={"Purchase Manual Rule"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name","is_active"},
     *             @OA\Property(property="name", type="string", maxLength=150, example="Night manual purchase"),
     *             @OA\Property(property="provider_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="accommodation_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="minimum_amount", type="integer", format="int64", nullable=true, minimum=0, example=null),
     *             @OA\Property(property="maximum_amount", type="integer", format="int64", nullable=true, minimum=0, example=null),
     *             @OA\Property(property="start_time", type="string", nullable=true, example="21:00"),
     *             @OA\Property(property="end_time", type="string", nullable=true, example="09:00"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             example={
     *                 "name":"Night manual purchase",
     *                 "provider_id":null,
     *                 "accommodation_id":null,
     *                 "minimum_amount":null,
     *                 "maximum_amount":null,
     *                 "start_time":"21:00",
     *                 "end_time":"09:00",
     *                 "is_active":true
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="created"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseManualRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/purchase-manual-rules/{purchase_manual_rule}",
     *     summary="Show offline purchase rule",
     *     tags={"Purchase Manual Rule"},
     *     @OA\Parameter(
     *         name="purchase_manual_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseManualRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(): void
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/purchase-manual-rules/{purchase_manual_rule}",
     *     summary="Update offline purchase rule",
     *     tags={"Purchase Manual Rule"},
     *     @OA\Parameter(
     *         name="purchase_manual_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", maxLength=150, example="GRS manual over limit"),
     *             @OA\Property(property="provider_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="accommodation_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="minimum_amount", type="integer", format="int64", nullable=true, minimum=0, example=1000000000),
     *             @OA\Property(property="maximum_amount", type="integer", format="int64", nullable=true, minimum=0, example=null),
     *             @OA\Property(property="start_time", type="string", nullable=true, example=null),
     *             @OA\Property(property="end_time", type="string", nullable=true, example=null),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseManualRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePut(): void
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/purchase-manual-rules/{purchase_manual_rule}",
     *     summary="Partially update offline purchase rule",
     *     tags={"Purchase Manual Rule"},
     *     @OA\Parameter(
     *         name="purchase_manual_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", maxLength=150),
     *             @OA\Property(property="provider_id", type="integer", nullable=true),
     *             @OA\Property(property="accommodation_id", type="integer", nullable=true),
     *             @OA\Property(property="minimum_amount", type="integer", format="int64", nullable=true, minimum=0),
     *             @OA\Property(property="maximum_amount", type="integer", format="int64", nullable=true, minimum=0),
     *             @OA\Property(property="start_time", type="string", nullable=true, example="21:00"),
     *             @OA\Property(property="end_time", type="string", nullable=true, example="09:00"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseManualRuleResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePatch(): void
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/purchase-manual-rules/{purchase_manual_rule}",
     *     summary="Delete offline purchase rule",
     *     tags={"Purchase Manual Rule"},
     *     @OA\Parameter(
     *         name="purchase_manual_rule",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
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
    public function destroy(): void
    {
    }
}
