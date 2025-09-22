<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\CountryDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;

class CountryController
{
    public function __construct(public CountryService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(CountryResource::collection($data), 'success');
    }

    public function store(StoreCountryRequest $request): JsonResponse
    {
        $dto = CountryDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new CountryResource($model), 'created', 201);
    }

    public function show(Country $country): JsonResponse
    {
        return StatusHelper::successResponse(new CountryResource($country), 'success');
    }

    public function update(UpdateCountryRequest $request, Country $country): JsonResponse
    {
        $dto = CountryDTO::fromRequest($request);
        $updated = $this->service->update($country->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $country->refresh();

        return StatusHelper::successResponse(new CountryResource($country), 'updated');
    }

    public function destroy(Country $country): JsonResponse
    {
        $deleted = $this->service->destroy($country->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
