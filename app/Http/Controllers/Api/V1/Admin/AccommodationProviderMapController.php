<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\AccommodationProviderMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreAccommodationProviderMapRequest;
use App\Http\Requests\UpdateAccommodationProviderMapRequest;
use App\Http\Resources\AccommodationProviderMapResource;
use App\Models\AccommodationProviderMap;
use App\Services\AccommodationProviderMapService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Accommodation Provider Maps",
 *     description="Link accommodations to external providers."
 * )
 */
class AccommodationProviderMapController
{
    public function __construct(public AccommodationProviderMapService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-provider-maps",
     *     operationId="listAccommodationProviderMaps",
     *     summary="List accommodation provider mappings",
     *     tags={"Accommodation Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapCollectionResponse")
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

        return StatusHelper::successResponse(AccommodationProviderMapResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/accommodation-provider-maps",
     *     operationId="createAccommodationProviderMap",
     *     summary="Create an accommodation provider mapping",
     *     tags={"Accommodation Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mapping created",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapResourceResponse")
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
    public function store(StoreAccommodationProviderMapRequest $request): JsonResponse
    {
        $dto = AccommodationProviderMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new AccommodationProviderMapResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     operationId="showAccommodationProviderMap",
     *     summary="Retrieve an accommodation provider mapping",
     *     tags={"Accommodation Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapResourceResponse")
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
    public function show(AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        $accommodationProviderMap = $this->service->loadRelations($accommodationProviderMap);

        return StatusHelper::successResponse(new AccommodationProviderMapResource($accommodationProviderMap), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     operationId="updateAccommodationProviderMap",
     *     summary="Update an accommodation provider mapping",
     *     tags={"Accommodation Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mapping updated",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationProviderMapResourceResponse")
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
    public function update(UpdateAccommodationProviderMapRequest $request, AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        $dto = AccommodationProviderMapDTO::fromRequest($request);
        $updated = $this->service->update($accommodationProviderMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $accommodationProviderMap->refresh();
        $accommodationProviderMap = $this->service->loadRelations($accommodationProviderMap);

        return StatusHelper::successResponse(new AccommodationProviderMapResource($accommodationProviderMap), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/accommodation-provider-maps/{accommodation_provider_map}",
     *     operationId="deleteAccommodationProviderMap",
     *     summary="Delete an accommodation provider mapping",
     *     tags={"Accommodation Provider Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation_provider_map",
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
     *     )
     * )
     */
    public function destroy(AccommodationProviderMap $accommodationProviderMap): JsonResponse
    {
        $deleted = $this->service->destroy($accommodationProviderMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
