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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Room Calendars",
 *     description="Manage daily availability and pricing entries for room types."
 * )
 */
class RoomCalendarController
{
    public function __construct(public RoomCalendarService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendars",
     *     operationId="listRoomCalendars",
     *     summary="List room calendar entries",
     *     tags={"Room Calendars"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarCollectionResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(RoomCalendarResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-calendars",
     *     operationId="createRoomCalendar",
     *     summary="Create a room calendar entry",
     *     tags={"Room Calendars"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Entry created",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(StoreRoomCalendarRequest $request): JsonResponse
    {
        $dto = RoomCalendarDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomCalendarResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     operationId="showRoomCalendar",
     *     summary="Retrieve a room calendar entry",
     *     tags={"Room Calendars"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar",
     *         in="path",
     *         required=true,
     *         description="Room calendar identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(RoomCalendar $roomCalendar): JsonResponse
    {
        $roomCalendar = $this->service->loadRelations($roomCalendar);

        return StatusHelper::successResponse(new RoomCalendarResource($roomCalendar), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     operationId="updateRoomCalendar",
     *     summary="Update a room calendar entry",
     *     tags={"Room Calendars"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar",
     *         in="path",
     *         required=true,
     *         description="Room calendar identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Entry updated",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the entry",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateRoomCalendarRequest $request, RoomCalendar $roomCalendar): JsonResponse
    {
        $dto = RoomCalendarDTO::fromRequest($request);
        $updated = $this->service->update($roomCalendar->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $roomCalendar->refresh();
        $roomCalendar = $this->service->loadRelations($roomCalendar);

        return StatusHelper::successResponse(new RoomCalendarResource($roomCalendar), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-calendars/{room_calendar}",
     *     operationId="deleteRoomCalendar",
     *     summary="Delete a room calendar entry",
     *     tags={"Room Calendars"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar",
     *         in="path",
     *         required=true,
     *         description="Room calendar identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Entry deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to delete the entry",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(RoomCalendar $roomCalendar): JsonResponse
    {
        $deleted = $this->service->destroy($roomCalendar->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
