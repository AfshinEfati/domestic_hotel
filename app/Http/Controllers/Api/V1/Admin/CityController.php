<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\City;
use App\Services\CityService;
use App\Helpers\StatusHelper;
use App\Http\Resources\CityResource;
use App\DTOs\CityDTO;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use Illuminate\Http\JsonResponse;

class CityController
{
    public function __construct(public CityService $service) {}
    public function index(): JsonResponse
    {
        $data = $this->service->index();
        return StatusHelper::successResponse(CityResource::collection($data));
    }
    public function store(StoreCityRequest $request): JsonResponse
    {
        $dto = CityDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new CityResource($model), 'created', 201);
    }
    public function show(City $city): JsonResponse
    {
        $city = $city->load(['state']);

        return StatusHelper::successResponse(new CityResource($city));
    }
    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $dto = CityDTO::fromRequest($request);
        $updated = $this->service->update($city->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $city->refresh();
        $city = $city->load(['state']);

        return StatusHelper::successResponse(new CityResource($city), 'updated');
    }
    public function destroy(City $city): JsonResponse
    {
        $deleted = $this->service->destroy($city->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
