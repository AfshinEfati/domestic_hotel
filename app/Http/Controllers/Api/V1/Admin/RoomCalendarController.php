<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RoomCalendarDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRoomCalendarRequest;
use App\Http\Requests\UpdateRoomCalendarRequest;
use App\Http\Resources\RoomCalendarResource;
use App\Models\RoomCalendar;
use App\Services\RoomCalendarService;
use Illuminate\Http\JsonResponse;

class RoomCalendarController
{
    public function __construct(public RoomCalendarService $service) {}
    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RoomCalendarResource::collection($data));
    }
    public function store(StoreRoomCalendarRequest $request): JsonResponse
    {
        $dto = RoomCalendarDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomCalendarResource($model), 'created', 201);
    }

    public function show(RoomCalendar $roomCalendar): JsonResponse
    {
        $roomCalendar->load(['ratePlan','provider','accommodation','roomType']);

        return StatusHelper::successResponse(new RoomCalendarResource($roomCalendar));
    }
    public function update(UpdateRoomCalendarRequest $request, RoomCalendar $roomCalendar): JsonResponse
    {
        $dto = RoomCalendarDTO::fromRequest($request);
        $updated = $this->service->update($roomCalendar->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }
        $roomCalendar->refresh();
        return StatusHelper::successResponse(new RoomCalendarResource($roomCalendar), 'updated');
    }

    public function destroy(RoomCalendar $roomCalendar): JsonResponse
    {
        $deleted = $this->service->destroy($roomCalendar->id);
        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
