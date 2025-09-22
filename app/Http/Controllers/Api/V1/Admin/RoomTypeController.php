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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Room Types",
 *     description="Manage accommodation room type definitions."
 * )
 */
class RoomTypeController
{
    public function __construct(public RoomTypeService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-types",
     *     operationId="listRoomTypes",
     *     summary="List room types",
     *     tags={"Room Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeCollectionResponse")
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

        return StatusHelper::successResponse(RoomTypeResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-types",
     *     operationId="createRoomType",
     *     summary="Create a room type",
     *     tags={"Room Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Room type created",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeResourceResponse")
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
    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $dto = RoomTypeDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomTypeResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     operationId="showRoomType",
     *     summary="Retrieve a room type",
     *     tags={"Room Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type",
     *         in="path",
     *         required=true,
     *         description="Room type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Room type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(RoomType $roomType): JsonResponse
    {
        $roomType = $this->service->loadRelations($roomType);

        return StatusHelper::successResponse(new RoomTypeResource($roomType), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     operationId="updateRoomType",
     *     summary="Update a room type",
     *     tags={"Room Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type",
     *         in="path",
     *         required=true,
     *         description="Room type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Room type updated",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Room type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the room type",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $dto = RoomTypeDTO::fromRequest($request);
        $updated = $this->service->update($roomType->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $roomType->refresh();
        $roomType = $this->service->loadRelations($roomType);

        return StatusHelper::successResponse(new RoomTypeResource($roomType), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-types/{room_type}",
     *     operationId="deleteRoomType",
     *     summary="Delete a room type",
     *     tags={"Room Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type",
     *         in="path",
     *         required=true,
     *         description="Room type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Room type deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Room type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to delete the room type",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(RoomType $roomType): JsonResponse
    {
        $deleted = $this->service->destroy($roomType->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
