<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProviderCityMapDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreProviderCityMapRequest;
use App\Http\Requests\UpdateProviderCityMapRequest;
use App\Http\Resources\ProviderCityMapResource;
use App\Models\ProviderCityMap;
use App\Services\ProviderCityMapService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Provider City Maps",
 *     description="Map providers to internal cities."
 * )
 */
class ProviderCityMapController
{
    public function __construct(public ProviderCityMapService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/provider-city-maps",
     *     operationId="listProviderCityMaps",
     *     summary="List provider city mappings",
     *     tags={"Provider City Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapCollectionResponse")
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

        return StatusHelper::successResponse(ProviderCityMapResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/provider-city-maps",
     *     operationId="createProviderCityMap",
     *     summary="Create a provider city mapping",
     *     tags={"Provider City Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mapping created",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapResourceResponse")
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
    public function store(StoreProviderCityMapRequest $request): JsonResponse
    {
        $dto = ProviderCityMapDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new ProviderCityMapResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/provider-city-maps/{provider_city_map}",
     *     operationId="showProviderCityMap",
     *     summary="Retrieve a provider city mapping",
     *     tags={"Provider City Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider_city_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapResourceResponse")
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
    public function show(ProviderCityMap $providerCityMap): JsonResponse
    {
        $providerCityMap = $this->service->loadRelations($providerCityMap);

        return StatusHelper::successResponse(new ProviderCityMapResource($providerCityMap), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/provider-city-maps/{provider_city_map}",
     *     operationId="updateProviderCityMap",
     *     summary="Update a provider city mapping",
     *     tags={"Provider City Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider_city_map",
     *         in="path",
     *         required=true,
     *         description="Mapping identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mapping updated",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCityMapResourceResponse")
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
    public function update(UpdateProviderCityMapRequest $request, ProviderCityMap $providerCityMap): JsonResponse
    {
        $dto = ProviderCityMapDTO::fromRequest($request);
        $updated = $this->service->update($providerCityMap->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $providerCityMap->refresh();
        $providerCityMap = $this->service->loadRelations($providerCityMap);

        return StatusHelper::successResponse(new ProviderCityMapResource($providerCityMap), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/provider-city-maps/{provider_city_map}",
     *     operationId="deleteProviderCityMap",
     *     summary="Delete a provider city mapping",
     *     tags={"Provider City Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider_city_map",
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
    public function destroy(ProviderCityMap $providerCityMap): JsonResponse
    {
        $deleted = $this->service->destroy($providerCityMap->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
