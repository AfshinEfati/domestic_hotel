<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RoomTypeProviderMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRoomTypeProviderMapRequest;
use App\Http\Requests\UpdateRoomTypeProviderMapRequest;
use App\Http\Resources\RoomTypeProviderMapResource;
use App\Models\RoomTypeProviderMap;
use App\Services\RoomTypeProviderMapService;
use Illuminate\Http\JsonResponse;

class RoomTypeProviderMapController
{
    public function __construct(public RoomTypeProviderMapService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RoomTypeProviderMapResource::collection($data), 'success');
    }

    public function store(StoreRoomTypeProviderMapRequest $request): JsonResponse
    {
        $dto = RoomTypeProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomTypeProviderMapResource($model), 'created', 201);
    }

    public function show(RoomTypeProviderMap $roomTypeProviderMap): JsonResponse
    {
        $roomTypeProviderMap = $this->service->loadRelations($roomTypeProviderMap);

        return StatusHelper::successResponse(new RoomTypeProviderMapResource($roomTypeProviderMap), 'success');
    }

    public function update(UpdateRoomTypeProviderMapRequest $request, RoomTypeProviderMap $roomTypeProviderMap): JsonResponse
    {
        $dto = RoomTypeProviderMapDTO::fromRequest($request);
        $updated = $this->service->update($roomTypeProviderMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $roomTypeProviderMap->refresh();
        $roomTypeProviderMap = $this->service->loadRelations($roomTypeProviderMap);

        return StatusHelper::successResponse(new RoomTypeProviderMapResource($roomTypeProviderMap), 'updated');
    }

    public function destroy(RoomTypeProviderMap $roomTypeProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($roomTypeProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
