<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\FacilityDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\JsonResponse;

class FacilityController
{
    public function __construct(public FacilityService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(FacilityResource::collection($data));
    }

    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $dto = FacilityDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new FacilityResource($model), 'created', 201);
    }

    public function show(Facility $facility): JsonResponse
    {
        $facility->load(['accommodations','group']);

        return StatusHelper::successResponse(new FacilityResource($facility));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $dto = FacilityDTO::fromRequest($request);
        $updated = $this->service->update($facility->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $facility->refresh();
        return StatusHelper::successResponse(new FacilityResource($facility), 'updated');
    }
    public function destroy(Facility $facility): JsonResponse
    {
        $deleted = $this->service->destroy($facility->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
