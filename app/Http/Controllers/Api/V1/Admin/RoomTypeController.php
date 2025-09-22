<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RoomTypeDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRoomTypeRequest;
use App\Http\Requests\UpdateRoomTypeRequest;
use App\Http\Resources\RoomTypeResource;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Http\JsonResponse;

class RoomTypeController
{
    public function __construct(public RoomTypeService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RoomTypeResource::collection($data), 'success');
    }

    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $dto = RoomTypeDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomTypeResource($model), 'created', 201);
    }

    public function show(RoomType $roomType): JsonResponse
    {
        return StatusHelper::successResponse(new RoomTypeResource($roomType), 'success');
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $dto = RoomTypeDTO::fromRequest($request);
        $updated = $this->service->update($roomType->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $roomType->refresh();

        return StatusHelper::successResponse(new RoomTypeResource($roomType), 'updated');
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $deleted = $this->service->destroy($roomType->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
