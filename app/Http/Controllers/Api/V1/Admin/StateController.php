<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\State;
use App\Services\StateService;
use App\Helpers\StatusHelper;
use App\Http\Resources\StateResource;
use App\DTOs\StateDTO;
use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="States",
 *     description="Manage state or province records."
 * )
 */
class StateController
{
    public function __construct(public StateService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/states",
     *     operationId="listStates",
     *     summary="List states",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/StateCollectionResponse")
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

        return StatusHelper::successResponse(StateResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/states",
     *     operationId="createState",
     *     summary="Create a state",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="State created",
     *         @OA\JsonContent(ref="#/components/schemas/StateResourceResponse")
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
    public function store(StoreStateRequest $request): JsonResponse
    {
        $dto = StateDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new StateResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/states/{state}",
     *     operationId="showState",
     *     summary="Retrieve a state",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="state",
     *         in="path",
     *         required=true,
     *         description="State identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/StateResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="State not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(State $state): JsonResponse
    {
        $state = $this->service->loadRelations($state);

        return StatusHelper::successResponse(new StateResource($state), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/states/{state}",
     *     operationId="updateState",
     *     summary="Update a state",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="state",
     *         in="path",
     *         required=true,
     *         description="State identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StateUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="State updated",
     *         @OA\JsonContent(ref="#/components/schemas/StateResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="State not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the state",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateStateRequest $request, State $state): JsonResponse
    {
        $dto = StateDTO::fromRequest($request);
        $updated = $this->service->update($state->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $state->refresh();
        $state = $this->service->loadRelations($state);

        return StatusHelper::successResponse(new StateResource($state), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/states/{state}",
     *     operationId="deleteState",
     *     summary="Delete a state",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="state",
     *         in="path",
     *         required=true,
     *         description="State identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="State deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="State not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(State $state): JsonResponse
    {
        $deleted = $this->service->destroy($state->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
