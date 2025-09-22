<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RatePlanDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRatePlanRequest;
use App\Http\Requests\UpdateRatePlanRequest;
use App\Http\Resources\RatePlanResource;
use App\Models\RatePlan;
use App\Services\RatePlanService;
use Illuminate\Http\JsonResponse;

class RatePlanController
{
    public function __construct(public RatePlanService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RatePlanResource::collection($data), 'success');
    }

    public function store(StoreRatePlanRequest $request): JsonResponse
    {
        $dto = RatePlanDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RatePlanResource($model), 'created', 201);
    }

    public function show(RatePlan $ratePlan): JsonResponse
    {
        $ratePlan = $this->service->loadRelations($ratePlan);

        return StatusHelper::successResponse(new RatePlanResource($ratePlan), 'success');
    }

    public function update(UpdateRatePlanRequest $request, RatePlan $ratePlan): JsonResponse
    {
        $dto = RatePlanDTO::fromRequest($request);
        $updated = $this->service->update($ratePlan->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $ratePlan->refresh();
        $ratePlan = $this->service->loadRelations($ratePlan);

        return StatusHelper::successResponse(new RatePlanResource($ratePlan), 'updated');
    }

    public function destroy(RatePlan $ratePlan): JsonResponse
    {
        $deleted = $this->service->destroy($ratePlan->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
