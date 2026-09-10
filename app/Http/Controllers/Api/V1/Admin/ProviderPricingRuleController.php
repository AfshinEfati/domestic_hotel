<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProviderPricingRuleDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreProviderPricingRuleRequest;
use App\Http\Requests\UpdateProviderPricingRuleRequest;
use App\Http\Resources\ProviderPricingRuleResource;
use App\Models\ProviderPricingRule;
use App\Services\Contracts\ProviderPricingRuleServiceInterface;
use Illuminate\Http\JsonResponse;

class ProviderPricingRuleController
{
    public function __construct(public ProviderPricingRuleServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return StatusHelper::successResponse(
            ProviderPricingRuleResource::collection($this->service->index())
        );
    }

    public function store(StoreProviderPricingRuleRequest $request): JsonResponse
    {
        $rule = $this->service->store(ProviderPricingRuleDTO::fromRequest($request));

        return StatusHelper::successResponse(
            new ProviderPricingRuleResource($rule),
            'created',
            201
        );
    }

    public function show(ProviderPricingRule $providerPricingRule): JsonResponse
    {
        return StatusHelper::successResponse(
            new ProviderPricingRuleResource($providerPricingRule)
        );
    }

    public function update(
        UpdateProviderPricingRuleRequest $request,
        ProviderPricingRule $providerPricingRule
    ): JsonResponse {
        $updated = $this->service->update(
            $providerPricingRule->id,
            ProviderPricingRuleDTO::fromRequest($request)
        );

        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $providerPricingRule->refresh();

        return StatusHelper::successResponse(
            new ProviderPricingRuleResource($providerPricingRule),
            'updated'
        );
    }

    public function destroy(ProviderPricingRule $providerPricingRule): JsonResponse
    {
        $deleted = $this->service->destroy($providerPricingRule->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
