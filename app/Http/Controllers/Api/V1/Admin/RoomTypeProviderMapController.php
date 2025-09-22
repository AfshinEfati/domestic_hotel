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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Room Type Provider Maps",
 *     description="Map room types to provider identifiers."
 * )
 */
class RoomTypeProviderMapController
{
    public function __construct(public RoomTypeProviderMapService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-type-provider-maps",
     *     operationId="listRoomTypeProviderMaps",
     *     summary="List room type provider mappings",
     *     tags={"Room Type Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapCollectionResponse")
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

        return StatusHelper::successResponse(RoomTypeProviderMapResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/room-type-provider-maps",
     *     operationId="createRoomTypeProviderMap",
     *     summary="Create a room type provider mapping",
     *     tags={"Room Type Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mapping created",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapResourceResponse")
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
    public function store(StoreRoomTypeProviderMapRequest $request): JsonResponse
    {
        $dto = RoomTypeProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RoomTypeProviderMapResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/room-type-provider-maps/{room_type_provider_map}",
     *     operationId="showRoomTypeProviderMap",
     *     summary="Retrieve a room type provider mapping",
     *     tags={"Room Type Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mapping not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(RoomTypeProviderMap $roomTypeProviderMap): JsonResponse
    {
        $roomTypeProviderMap = $this->service->loadRelations($roomTypeProviderMap);

        return StatusHelper::successResponse(new RoomTypeProviderMapResource($roomTypeProviderMap), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/room-type-provider-maps/{room_type_provider_map}",
     *     operationId="updateRoomTypeProviderMap",
     *     summary="Update a room type provider mapping",
     *     tags={"Room Type Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mapping updated",
     *         @OA\JsonContent(ref="#/components/schemas/RoomTypeProviderMapResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mapping not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the mapping",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/room-type-provider-maps/{room_type_provider_map}",
     *     operationId="deleteRoomTypeProviderMap",
     *     summary="Delete a room type provider mapping",
     *     tags={"Room Type Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="room_type_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Mapping deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mapping not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to delete the mapping",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(RoomTypeProviderMap $roomTypeProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($roomTypeProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
