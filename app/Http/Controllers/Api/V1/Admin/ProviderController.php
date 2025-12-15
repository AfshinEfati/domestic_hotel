<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProviderDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Http\JsonResponse;

class ProviderController
{
    public function __construct(public ProviderService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(ProviderResource::collection($data));
    }
    public function store(StoreProviderRequest $request): JsonResponse
    {
        $dto = ProviderDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new ProviderResource($model), 'created', 201);
    }

    public function show(Provider $provider): JsonResponse
    {
        $provider->load(['countries','accommodations','facilityGroups']);

        return StatusHelper::successResponse(new ProviderResource($provider));
    }

    public function update(UpdateProviderRequest $request, Provider $provider): JsonResponse
    {
        $dto = ProviderDTO::fromRequest($request);
        $updated = $this->service->update($provider->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }
        $provider->refresh();
        return StatusHelper::successResponse(new ProviderResource($provider), 'updated');
    }
    public function destroy(Provider $provider): JsonResponse
    {
        $deleted = $this->service->destroy($provider->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
