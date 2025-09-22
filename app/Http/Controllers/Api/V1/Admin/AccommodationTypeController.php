<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\AccommodationType;
use App\Services\AccommodationTypeService;
use App\Helpers\StatusHelper;
use App\Http\Resources\AccommodationTypeResource;
use App\DTOs\AccommodationTypeDTO;
use App\Http\Requests\StoreAccommodationTypeRequest;
use App\Http\Requests\UpdateAccommodationTypeRequest;

class AccommodationTypeController
{
    public function __construct(public AccommodationTypeService $service) {}

    public function index()
    {
        $data = $this->service->index();
        return StatusHelper::successResponse(AccommodationTypeResource::collection($data), 'success');
    }

    public function store(StoreAccommodationTypeRequest $request)
    {
        $dto = AccommodationTypeDTO::fromRequest($request);
        $model = $this->service->store($dto);
        return StatusHelper::successResponse(new AccommodationTypeResource($model), 'created', 201);
    }

    public function show(AccommodationType $accommodationType): mixed
    {
        // no relations to load

        return StatusHelper::successResponse(new AccommodationTypeResource($accommodationType), 'success');
    }

    public function update(UpdateAccommodationTypeRequest $request, AccommodationType $accommodationType)
    {
        $dto = AccommodationTypeDTO::fromRequest($request);
        $updated = $this->service->update($accommodationType->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }
        $accommodationType->refresh();
        // no relations to load

        return StatusHelper::successResponse(new AccommodationTypeResource($accommodationType), 'updated');
    }

    public function destroy(AccommodationType $accommodationType)
    {
        $deleted = $this->service->destroy($accommodationType->id);
        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
