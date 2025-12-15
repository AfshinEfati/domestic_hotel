<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RoomCalendarSnapshotDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRoomCalendarSnapshotRequest;
use App\Http\Requests\UpdateRoomCalendarSnapshotRequest;
use App\Http\Resources\RoomCalendarSnapshotResource;
use App\Models\RoomCalendarSnapshot;
use App\Services\RoomCalendarSnapshotService;
use Illuminate\Http\JsonResponse;

class RoomCalendarSnapshotController
{
    public function __construct(public RoomCalendarSnapshotService $service) {}
    public function index(): JsonResponse
    {
        $data = $this->service->index();
        return StatusHelper::successResponse(RoomCalendarSnapshotResource::collection($data));
    }
    public function store(StoreRoomCalendarSnapshotRequest $request): JsonResponse
    {
        $dto = RoomCalendarSnapshotDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($model), 'created', 201);
    }
    public function show(RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $roomCalendarSnapshot->load(['provider','roomCalendar']);

        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($roomCalendarSnapshot));
    }
    public function update(UpdateRoomCalendarSnapshotRequest $request, RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $dto = RoomCalendarSnapshotDTO::fromRequest($request);
        $updated = $this->service->update($roomCalendarSnapshot->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }
        $roomCalendarSnapshot->refresh();
        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($roomCalendarSnapshot), 'updated');
    }

    public function destroy(RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $deleted = $this->service->destroy($roomCalendarSnapshot->id);
        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
