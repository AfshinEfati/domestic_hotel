<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\CountryDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Countries",
 *     description="Manage country records."
 * )
 */
class CountryController
{
    public function __construct(public CountryService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/countries",
     *     operationId="listCountries",
     *     summary="List countries",
     *     tags={"Countries"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/CountryCollectionResponse")
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

        return StatusHelper::successResponse(CountryResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/countries",
     *     operationId="createCountry",
     *     summary="Create a country",
     *     tags={"Countries"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CountryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Country created",
     *         @OA\JsonContent(ref="#/components/schemas/CountryResourceResponse")
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
    public function store(StoreCountryRequest $request): JsonResponse
    {
        $dto = CountryDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new CountryResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/countries/{country}",
     *     operationId="showCountry",
     *     summary="Retrieve a country",
     *     tags={"Countries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="country",
     *         in="path",
     *         required=true,
     *         description="Country identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/CountryResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Country $country): JsonResponse
    {
        return StatusHelper::successResponse(new CountryResource($country), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/countries/{country}",
     *     operationId="updateCountry",
     *     summary="Update a country",
     *     tags={"Countries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="country",
     *         in="path",
     *         required=true,
     *         description="Country identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CountryUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country updated",
     *         @OA\JsonContent(ref="#/components/schemas/CountryResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the country",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateCountryRequest $request, Country $country): JsonResponse
    {
        $dto = CountryDTO::fromRequest($request);
        $updated = $this->service->update($country->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $country->refresh();

        return StatusHelper::successResponse(new CountryResource($country), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/countries/{country}",
     *     operationId="deleteCountry",
     *     summary="Delete a country",
     *     tags={"Countries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="country",
     *         in="path",
     *         required=true,
     *         description="Country identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Country deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Country $country): JsonResponse
    {
        $deleted = $this->service->destroy($country->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
