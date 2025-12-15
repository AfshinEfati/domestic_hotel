<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\AccommodationProviderMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreAccommodationProviderMapRequest;
use App\Http\Requests\UpdateAccommodationProviderMapRequest;
use App\Http\Resources\AccommodationProviderMapResource;
use App\Models\AccommodationProviderMap;
use App\Services\AccommodationProviderMapService;
use Illuminate\Http\JsonResponse;


class AccommodationProviderMapController
{
    public function __construct(public AccommodationProviderMapService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(AccommodationProviderMapResource::collection($data));
    }
    public function store(StoreAccommodationProviderMapRequest $request): JsonResponse
    {
        $dto = AccommodationProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new AccommodationProviderMapResource($model), 'created', 201);
    }
    public function show(AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        return StatusHelper::successResponse(new AccommodationProviderMapResource($accommodationProviderMap));
    }
    public function update(UpdateAccommodationProviderMapRequest $request, AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        $dto = AccommodationProviderMapDTO::fromRequest($request);
        $updated = $this->service->update($accommodationProviderMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $accommodationProviderMap->refresh();
        return StatusHelper::successResponse(new AccommodationProviderMapResource($accommodationProviderMap), 'updated');
    }
    public function destroy(AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($accommodationProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
