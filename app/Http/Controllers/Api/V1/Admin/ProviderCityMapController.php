<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProviderCityMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreProviderCityMapRequest;
use App\Http\Requests\UpdateProviderCityMapRequest;
use App\Http\Resources\ProviderCityMapResource;
use App\Models\ProviderCityMap;
use App\Services\ProviderCityMapService;
use Illuminate\Http\JsonResponse;

class ProviderCityMapController
{
    public function __construct(public ProviderCityMapService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(ProviderCityMapResource::collection($data), 'success');
    }

    public function store(StoreProviderCityMapRequest $request): JsonResponse
    {
        $dto = ProviderCityMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new ProviderCityMapResource($model), 'created', 201);
    }

    public function show(ProviderCityMap $providerCityMap): JsonResponse
    {
        return StatusHelper::successResponse(new ProviderCityMapResource($providerCityMap), 'success');
    }

    public function update(UpdateProviderCityMapRequest $request, ProviderCityMap $providerCityMap): JsonResponse
    {
        $dto = ProviderCityMapDTO::fromRequest($request);
        $updated = $this->service->update($providerCityMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $providerCityMap->refresh();

        return StatusHelper::successResponse(new ProviderCityMapResource($providerCityMap), 'updated');
    }

    public function destroy(ProviderCityMap $providerCityMap): JsonResponse
    {
        $deleted = $this->service->destroy($providerCityMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
