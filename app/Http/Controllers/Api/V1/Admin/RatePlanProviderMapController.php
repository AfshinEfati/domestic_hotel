<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RatePlanProviderMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRatePlanProviderMapRequest;
use App\Http\Requests\UpdateRatePlanProviderMapRequest;
use App\Http\Resources\RatePlanProviderMapResource;
use App\Models\RatePlanProviderMap;
use App\Services\RatePlanProviderMapService;
use Illuminate\Http\JsonResponse;

class RatePlanProviderMapController
{
    public function __construct(public RatePlanProviderMapService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RatePlanProviderMapResource::collection($data), 'success');
    }

    public function store(StoreRatePlanProviderMapRequest $request): JsonResponse
    {
        $dto = RatePlanProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RatePlanProviderMapResource($model), 'created', 201);
    }

    public function show(RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        return StatusHelper::successResponse(new RatePlanProviderMapResource($ratePlanProviderMap), 'success');
    }

    public function update(UpdateRatePlanProviderMapRequest $request, RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        $dto = RatePlanProviderMapDTO::fromRequest($request);
        $updated = $this->service->update($ratePlanProviderMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $ratePlanProviderMap->refresh();

        return StatusHelper::successResponse(new RatePlanProviderMapResource($ratePlanProviderMap), 'updated');
    }

    public function destroy(RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($ratePlanProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
