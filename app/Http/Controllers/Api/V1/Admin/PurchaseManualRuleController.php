<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\PurchaseManualRuleDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StorePurchaseManualRuleRequest;
use App\Http\Requests\UpdatePurchaseManualRuleRequest;
use App\Http\Resources\PurchaseManualRuleResource;
use App\Models\PurchaseManualRule;
use App\Services\Contracts\PurchaseManualRuleServiceInterface;
use Illuminate\Http\JsonResponse;

class PurchaseManualRuleController
{
    public function __construct(public PurchaseManualRuleServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return StatusHelper::successResponse(
            PurchaseManualRuleResource::collection($this->service->index())
        );
    }

    public function store(StorePurchaseManualRuleRequest $request): JsonResponse
    {
        $rule = $this->service->store(PurchaseManualRuleDTO::fromRequest($request));

        return StatusHelper::successResponse(
            new PurchaseManualRuleResource($rule),
            'created',
            201
        );
    }

    public function show(PurchaseManualRule $purchaseManualRule): JsonResponse
    {
        $purchaseManualRule->load(['provider', 'accommodation']);

        return StatusHelper::successResponse(
            new PurchaseManualRuleResource($purchaseManualRule)
        );
    }

    public function update(
        UpdatePurchaseManualRuleRequest $request,
        PurchaseManualRule $purchaseManualRule
    ): JsonResponse {
        $updated = $this->service->update(
            $purchaseManualRule->id,
            PurchaseManualRuleDTO::fromRequest($request)
        );

        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $purchaseManualRule->refresh()->load(['provider', 'accommodation']);

        return StatusHelper::successResponse(
            new PurchaseManualRuleResource($purchaseManualRule),
            'updated'
        );
    }

    public function destroy(PurchaseManualRule $purchaseManualRule): JsonResponse
    {
        $deleted = $this->service->destroy($purchaseManualRule->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
