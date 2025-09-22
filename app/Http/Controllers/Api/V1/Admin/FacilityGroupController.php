<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\FacilityGroupDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreFacilityGroupRequest;
use App\Http\Requests\UpdateFacilityGroupRequest;
use App\Http\Resources\FacilityGroupResource;
use App\Models\FacilityGroup;
use App\Services\FacilityGroupService;
use Illuminate\Http\JsonResponse;

class FacilityGroupController
{
    public function __construct(public FacilityGroupService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(FacilityGroupResource::collection($data), 'success');
    }

    public function store(StoreFacilityGroupRequest $request): JsonResponse
    {
        $dto = FacilityGroupDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new FacilityGroupResource($model), 'created', 201);
    }

    public function show(FacilityGroup $facilityGroup): JsonResponse
    {
        $facilityGroup = $this->service->loadRelations($facilityGroup);

        return StatusHelper::successResponse(new FacilityGroupResource($facilityGroup), 'success');
    }

    public function update(UpdateFacilityGroupRequest $request, FacilityGroup $facilityGroup): JsonResponse
    {
        $dto = FacilityGroupDTO::fromRequest($request);
        $updated = $this->service->update($facilityGroup->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $facilityGroup->refresh();
        $facilityGroup = $this->service->loadRelations($facilityGroup);

        return StatusHelper::successResponse(new FacilityGroupResource($facilityGroup), 'updated');
    }

    public function destroy(FacilityGroup $facilityGroup): JsonResponse
    {
        $deleted = $this->service->destroy($facilityGroup->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
