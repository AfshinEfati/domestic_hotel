<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\City;
use App\Services\CityService;
use App\Helpers\StatusHelper;
use App\Http\Resources\CityResource;
use App\DTOs\CityDTO;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Cities",
 *     description="Manage city records."
 * )
 */
class CityController
{
    public function __construct(public CityService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/cities",
     *     operationId="listCities",
     *     summary="List cities",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/CityCollectionResponse")
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

        return StatusHelper::successResponse(CityResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/cities",
     *     operationId="createCity",
     *     summary="Create a city",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CityRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="City created",
     *         @OA\JsonContent(ref="#/components/schemas/CityResourceResponse")
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
    public function store(StoreCityRequest $request): JsonResponse
    {
        $dto = CityDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new CityResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/cities/{city}",
     *     operationId="showCity",
     *     summary="Retrieve a city",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="path",
     *         required=true,
     *         description="City identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/CityResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(City $city): JsonResponse
    {
        $city = $this->service->loadRelations($city);

        return StatusHelper::successResponse(new CityResource($city), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/cities/{city}",
     *     operationId="updateCity",
     *     summary="Update a city",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="path",
     *         required=true,
     *         description="City identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CityUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="City updated",
     *         @OA\JsonContent(ref="#/components/schemas/CityResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the city",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $dto = CityDTO::fromRequest($request);
        $updated = $this->service->update($city->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $city->refresh();
        $city = $this->service->loadRelations($city);

        return StatusHelper::successResponse(new CityResource($city), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/cities/{city}",
     *     operationId="deleteCity",
     *     summary="Delete a city",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="path",
     *         required=true,
     *         description="City identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="City deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(City $city): JsonResponse
    {
        $deleted = $this->service->destroy($city->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
