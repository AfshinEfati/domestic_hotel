<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RatePlanDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRatePlanRequest;
use App\Http\Requests\UpdateRatePlanRequest;
use App\Http\Resources\RatePlanResource;
use App\Models\RatePlan;
use App\Services\RatePlanService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Rate Plans",
 *     description="Manage accommodation rate plans."
 * )
 */
class RatePlanController
{
    public function __construct(public RatePlanService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plans",
     *     operationId="listRatePlans",
     *     summary="List rate plans",
     *     tags={"Rate Plans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanCollectionResponse")
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

        return StatusHelper::successResponse(RatePlanResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rate-plans",
     *     operationId="createRatePlan",
     *     summary="Create a rate plan",
     *     tags={"Rate Plans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rate plan created",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanResourceResponse")
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
    public function store(StoreRatePlanRequest $request): JsonResponse
    {
        $dto = RatePlanDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RatePlanResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     operationId="showRatePlan",
     *     summary="Retrieve a rate plan",
     *     tags={"Rate Plans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan",
     *         in="path",
     *         required=true,
     *         description="Rate plan identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate plan not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(RatePlan $ratePlan): JsonResponse
    {
        $ratePlan = $this->service->loadRelations($ratePlan);

        return StatusHelper::successResponse(new RatePlanResource($ratePlan), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     operationId="updateRatePlan",
     *     summary="Update a rate plan",
     *     tags={"Rate Plans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan",
     *         in="path",
     *         required=true,
     *         description="Rate plan identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rate plan updated",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate plan not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the rate plan",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateRatePlanRequest $request, RatePlan $ratePlan): JsonResponse
    {
        $dto = RatePlanDTO::fromRequest($request);
        $updated = $this->service->update($ratePlan->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $ratePlan->refresh();
        $ratePlan = $this->service->loadRelations($ratePlan);

        return StatusHelper::successResponse(new RatePlanResource($ratePlan), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rate-plans/{rate_plan}",
     *     operationId="deleteRatePlan",
     *     summary="Delete a rate plan",
     *     tags={"Rate Plans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan",
     *         in="path",
     *         required=true,
     *         description="Rate plan identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Rate plan deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rate plan not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(RatePlan $ratePlan): JsonResponse
    {
        $deleted = $this->service->destroy($ratePlan->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
