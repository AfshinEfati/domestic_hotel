<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\FacilityDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Facilities",
 *     description="Manage facility records."
 * )
 */
class FacilityController
{
    public function __construct(public FacilityService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/facilities",
     *     operationId="listFacilities",
     *     summary="List facilities",
     *     tags={"Facilities"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityCollectionResponse")
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

        return StatusHelper::successResponse(FacilityResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/facilities",
     *     operationId="createFacility",
     *     summary="Create a facility",
     *     tags={"Facilities"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FacilityRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Facility created",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityResourceResponse")
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
    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $dto = FacilityDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new FacilityResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/facilities/{facility}",
     *     operationId="showFacility",
     *     summary="Retrieve a facility",
     *     tags={"Facilities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility",
     *         in="path",
     *         required=true,
     *         description="Facility identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Facility $facility): JsonResponse
    {
        $facility = $this->service->loadRelations($facility);

        return StatusHelper::successResponse(new FacilityResource($facility), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/facilities/{facility}",
     *     operationId="updateFacility",
     *     summary="Update a facility",
     *     tags={"Facilities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility",
     *         in="path",
     *         required=true,
     *         description="Facility identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FacilityUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facility updated",
     *         @OA\JsonContent(ref="#/components/schemas/FacilityResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the facility",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $dto = FacilityDTO::fromRequest($request);
        $updated = $this->service->update($facility->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $facility->refresh();
        $facility = $this->service->loadRelations($facility);

        return StatusHelper::successResponse(new FacilityResource($facility), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/facilities/{facility}",
     *     operationId="deleteFacility",
     *     summary="Delete a facility",
     *     tags={"Facilities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="facility",
     *         in="path",
     *         required=true,
     *         description="Facility identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Facility deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Facility $facility): JsonResponse
    {
        $deleted = $this->service->destroy($facility->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
