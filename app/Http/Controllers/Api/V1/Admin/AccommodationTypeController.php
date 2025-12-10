<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\AccommodationType;
use App\Services\AccommodationTypeService;
use App\Helpers\StatusHelper;
use App\Http\Resources\AccommodationTypeResource;
use App\DTOs\AccommodationTypeDTO;
use App\Http\Requests\StoreAccommodationTypeRequest;
use App\Http\Requests\UpdateAccommodationTypeRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Accommodation Types",
 *     description="Manage accommodation type definitions."
 * )
 */
class AccommodationTypeController
{
    public function __construct(public AccommodationTypeService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-types",
     *     operationId="listAccommodationTypes",
     *     summary="List accommodation types",
     *     tags={"Accommodation Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeCollectionResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index()
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(AccommodationTypeResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/accommodation-types",
     *     operationId="createAccommodationType",
     *     summary="Create an accommodation type",
     *     tags={"Accommodation Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Accommodation type created",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeResourceResponse")
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
    public function store(StoreAccommodationTypeRequest $request)
    {
        $dto = AccommodationTypeDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new AccommodationTypeResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-types/{accommodation_type}",
     *     operationId="showAccommodationType",
     *     summary="Retrieve an accommodation type",
     *     tags={"Accommodation Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_type",
     *         in="path",
     *         required=true,
     *         description="Accommodation type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(AccommodationType $accommodationType): JsonResponse
    {
        $accommodationType = $this->service->loadRelations($accommodationType);

        return StatusHelper::successResponse(new AccommodationTypeResource($accommodationType), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/accommodation-types/{accommodation_type}",
     *     operationId="updateAccommodationType",
     *     summary="Update an accommodation type",
     *     tags={"Accommodation Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_type",
     *         in="path",
     *         required=true,
     *         description="Accommodation type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accommodation type updated",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationTypeResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the accommodation type",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateAccommodationTypeRequest $request, AccommodationType $accommodationType): JsonResponse
    {
        $dto = AccommodationTypeDTO::fromRequest($request);
        $updated = $this->service->update($accommodationType->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $accommodationType->refresh();
        $accommodationType = $this->service->loadRelations($accommodationType);

        return StatusHelper::successResponse(new AccommodationTypeResource($accommodationType), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/accommodation-types/{accommodation_type}",
     *     operationId="deleteAccommodationType",
     *     summary="Delete an accommodation type",
     *     tags={"Accommodation Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_type",
     *         in="path",
     *         required=true,
     *         description="Accommodation type identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Accommodation type deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation type not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(AccommodationType $accommodationType): JsonResponse
    {
        $deleted = $this->service->destroy($accommodationType->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
