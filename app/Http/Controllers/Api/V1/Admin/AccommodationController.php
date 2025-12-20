<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Accommodation;
use App\Services\AccommodationService;
use App\Helpers\StatusHelper;
use App\Http\Resources\AccommodationResource;
use App\DTOs\AccommodationDTO;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use Illuminate\Http\JsonResponse;

class AccommodationController
{
    public function __construct(public AccommodationService $service) {}

    public function index()
    {
        $data = $this->service->index();
        $data->load(['city','type']);
        return StatusHelper::successResponse(AccommodationResource::collection($data));
    }

    public function store(StoreAccommodationRequest $request)
    {
        $dto = AccommodationDTO::fromRequest($request);

        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new AccommodationResource($model), 'created', 201);
    }

    public function show(Accommodation $accommodation): JsonResponse
    {
        $accommodation->load(['city','facilities','type','rooms']);

        return StatusHelper::successResponse(new AccommodationResource($accommodation));
    }

    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation)
    {
        $dto = AccommodationDTO::fromRequest($request);
        $updated = $this->service->update($accommodation->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $accommodation->refresh();
        $accommodation->load(['city','facilities','type']);

        return StatusHelper::successResponse(new AccommodationResource($accommodation), 'updated');
    }
    public function destroy(Accommodation $accommodation)
    {
        $deleted = $this->service->destroy($accommodation->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
