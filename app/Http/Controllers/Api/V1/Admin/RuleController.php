<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Rule\CreateRuleAction;
use App\Actions\Rule\DeleteRuleAction;
use App\Actions\Rule\ListRuleAction;
use App\Actions\Rule\ShowRuleAction;
use App\Actions\Rule\UpdateRuleAction;
use App\DTOs\RuleDTO;
use App\Helpers\ApiResponseHelper;
use App\Http\Requests\Rule\StoreRuleRequest;
use App\Http\Requests\Rule\UpdateRuleRequest;
use App\Http\Resources\RuleResource;
use App\Models\Rule;
use Illuminate\Http\JsonResponse;

class RuleController
{
    public function __construct(
        public ListRuleAction $listAction,
        public ShowRuleAction $showAction,
        public CreateRuleAction $createAction,
        public UpdateRuleAction $updateAction,
        public DeleteRuleAction $deleteAction,
    ) {

    }

    public function index()
    {
        $data = ($this->listAction)();
        return ApiResponseHelper::successResponse(RuleResource::collection($data));
    }

    public function store(StoreRuleRequest $request)
    {
        $dto = RuleDTO::fromRequest($request);
        $model = ($this->createAction)($dto);
        return ApiResponseHelper::successResponse(new RuleResource($model), 'created', 201);
    }

    public function show(Rule $rule): JsonResponse
    {
        $model = ($this->showAction)($rule->getKey());
        if (!$model) {
            return ApiResponseHelper::errorResponse('not found', 404);
        }
        $model->load(['accommodations']);

        return ApiResponseHelper::successResponse(new RuleResource($model));
    }

    public function update(UpdateRuleRequest $request, Rule $rule)
    {
        $dto = RuleDTO::fromRequest($request);
        $model = ($this->updateAction)($rule->getKey(), $dto);
        if (!$model) {
            return ApiResponseHelper::errorResponse('update failed', 422);
        }
        $model->load(['accommodations']);

        return ApiResponseHelper::successResponse(new RuleResource($model), 'updated');
    }

    public function destroy(Rule $rule)
    {
        $deleted = ($this->deleteAction)($rule->getKey());
        return $deleted
            ? ApiResponseHelper::successResponse(null, 'deleted')
            : ApiResponseHelper::errorResponse('delete failed', 422);
    }
}
