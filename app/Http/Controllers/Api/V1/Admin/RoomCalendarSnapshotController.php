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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Room Calendar Snapshots",
 *     description="Inspect daily payload snapshots received from providers."
 * )
 */
class RoomCalendarSnapshotController
{
    public function __construct(public RoomCalendarSnapshotService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendar-snapshots",
     *     operationId="listRoomCalendarSnapshots",
     *     summary="List room calendar snapshots",
     *     tags={"Room Calendar Snapshots"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotCollectionResponse")
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

        return StatusHelper::successResponse(RoomCalendarSnapshotResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-calendar-snapshots",
     *     operationId="createRoomCalendarSnapshot",
     *     summary="Create a room calendar snapshot",
     *     tags={"Room Calendar Snapshots"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Snapshot stored",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotResourceResponse")
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
    public function store(StoreRoomCalendarSnapshotRequest $request): JsonResponse
    {
        $dto = RoomCalendarSnapshotDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     operationId="showRoomCalendarSnapshot",
     *     summary="Retrieve a room calendar snapshot",
     *     tags={"Room Calendar Snapshots"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         description="Snapshot identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Snapshot not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $roomCalendarSnapshot = $this->service->loadRelations($roomCalendarSnapshot);

        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($roomCalendarSnapshot), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     operationId="updateRoomCalendarSnapshot",
     *     summary="Update a room calendar snapshot",
     *     tags={"Room Calendar Snapshots"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         description="Snapshot identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Snapshot updated",
     *         @OA\JsonContent(ref="#/components/schemas/RoomCalendarSnapshotResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Snapshot not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the snapshot",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateRoomCalendarSnapshotRequest $request, RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $dto = RoomCalendarSnapshotDTO::fromRequest($request);
        $updated = $this->service->update($roomCalendarSnapshot->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $roomCalendarSnapshot->refresh();
        $roomCalendarSnapshot = $this->service->loadRelations($roomCalendarSnapshot);

        return StatusHelper::successResponse(new RoomCalendarSnapshotResource($roomCalendarSnapshot), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-calendar-snapshots/{room_calendar_snapshot}",
     *     operationId="deleteRoomCalendarSnapshot",
     *     summary="Delete a room calendar snapshot",
     *     tags={"Room Calendar Snapshots"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_calendar_snapshot",
     *         in="path",
     *         required=true,
     *         description="Snapshot identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Snapshot deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Snapshot not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to delete the snapshot",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(RoomCalendarSnapshot $roomCalendarSnapshot): JsonResponse
    {
        $deleted = $this->service->destroy($roomCalendarSnapshot->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
