<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\RatePlanProviderMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreRatePlanProviderMapRequest;
use App\Http\Requests\UpdateRatePlanProviderMapRequest;
use App\Http\Resources\RatePlanProviderMapResource;
use App\Models\RatePlanProviderMap;
use App\Services\RatePlanProviderMapService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Rate Plan Provider Maps",
 *     description="Map internal rate plans to external provider references."
 * )
 */
class RatePlanProviderMapController
{
    public function __construct(public RatePlanProviderMapService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plan-provider-maps",
     *     operationId="listRatePlanProviderMaps",
     *     summary="List rate plan provider mappings",
     *     tags={"Rate Plan Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapCollectionResponse")
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

        return StatusHelper::successResponse(RatePlanProviderMapResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/rate-plan-provider-maps",
     *     operationId="createRatePlanProviderMap",
     *     summary="Create a rate plan provider mapping",
     *     tags={"Rate Plan Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mapping created",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapResourceResponse")
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
    public function store(StoreRatePlanProviderMapRequest $request): JsonResponse
    {
        $dto = RatePlanProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new RatePlanProviderMapResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/rate-plan-provider-maps/{rate_plan_provider_map}",
     *     operationId="showRatePlanProviderMap",
     *     summary="Retrieve a rate plan provider mapping",
     *     tags={"Rate Plan Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapResourceResponse")
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
    public function show(RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        $ratePlanProviderMap = $this->service->loadRelations($ratePlanProviderMap);

        return StatusHelper::successResponse(new RatePlanProviderMapResource($ratePlanProviderMap), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/rate-plan-provider-maps/{rate_plan_provider_map}",
     *     operationId="updateRatePlanProviderMap",
     *     summary="Update a rate plan provider mapping",
     *     tags={"Rate Plan Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mapping updated",
     *         @OA\JsonContent(ref="#/components/schemas/RatePlanProviderMapResourceResponse")
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
    public function update(UpdateRatePlanProviderMapRequest $request, RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        $dto = RatePlanProviderMapDTO::fromRequest($request);
        $updated = $this->service->update($ratePlanProviderMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $ratePlanProviderMap->refresh();
        $ratePlanProviderMap = $this->service->loadRelations($ratePlanProviderMap);

        return StatusHelper::successResponse(new RatePlanProviderMapResource($ratePlanProviderMap), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/rate-plan-provider-maps/{rate_plan_provider_map}",
     *     operationId="deleteRatePlanProviderMap",
     *     summary="Delete a rate plan provider mapping",
     *     tags={"Rate Plan Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rate_plan_provider_map",
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
    public function destroy(RatePlanProviderMap $ratePlanProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($ratePlanProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
