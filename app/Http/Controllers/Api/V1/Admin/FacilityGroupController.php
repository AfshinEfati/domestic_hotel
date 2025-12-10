<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\FacilityGroupDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreFacilityGroupRequest;
use App\Http\Requests\UpdateFacilityGroupRequest;
use App\Http\Resources\FacilityGroupResource;
use App\Models\FacilityGroup;
use App\Services\FacilityGroupService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Facility Groups",
 *     description="Manage facility group catalogs."
 * )
 */
class FacilityGroupController
{
    public function __construct(public FacilityGroupService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/facility-groups",
     *     operationId="listFacilityGroups",
     *     summary="List facility groups",
     *     tags={"Facility Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupCollectionResponse")
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

        return StatusHelper::successResponse(FacilityGroupResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/facility-groups",
     *     operationId="createFacilityGroup",
     *     summary="Create a facility group",
     *     tags={"Facility Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Facility group created",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupResourceResponse")
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
    public function store(StoreFacilityGroupRequest $request): JsonResponse
    {
        $dto = FacilityGroupDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new FacilityGroupResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/facility-groups/{facility_group}",
     *     operationId="showFacilityGroup",
     *     summary="Retrieve a facility group",
     *     tags={"Facility Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility_group",
     *         in="path",
     *         required=true,
     *         description="Facility group identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility group not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(FacilityGroup $facilityGroup): JsonResponse
    {
        $facilityGroup = $this->service->loadRelations($facilityGroup);

        return StatusHelper::successResponse(new FacilityGroupResource($facilityGroup), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/facility-groups/{facility_group}",
     *     operationId="updateFacilityGroup",
     *     summary="Update a facility group",
     *     tags={"Facility Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility_group",
     *         in="path",
     *         required=true,
     *         description="Facility group identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facility group updated",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityGroupResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility group not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the facility group",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateFacilityGroupRequest $request, FacilityGroup $facilityGroup): JsonResponse
    {
        $dto = FacilityGroupDTO::fromRequest($request);
        $updated = $this->service->update($facilityGroup->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $facilityGroup->refresh();
        $facilityGroup = $this->service->loadRelations($facilityGroup);

        return StatusHelper::successResponse(new FacilityGroupResource($facilityGroup), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/facility-groups/{facility_group}",
     *     operationId="deleteFacilityGroup",
     *     summary="Delete a facility group",
     *     tags={"Facility Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility_group",
     *         in="path",
     *         required=true,
     *         description="Facility group identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Facility group deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility group not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(FacilityGroup $facilityGroup): JsonResponse
    {
        $deleted = $this->service->destroy($facilityGroup->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
