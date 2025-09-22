<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Accommodation;
use App\Services\AccommodationService;
use App\Helpers\StatusHelper;
use App\Http\Resources\AccommodationResource;
use App\DTOs\AccommodationDTO;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Accommodations",
 *     description="Endpoints for managing accommodations."
 * )
 */
class AccommodationController
{
    public function __construct(public AccommodationService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodations",
     *     operationId="listAccommodations",
     *     summary="List accommodations",
     *     tags={"Accommodations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationCollectionResponse")
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

        return StatusHelper::successResponse(AccommodationResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/accommodations",
     *     operationId="createAccommodation",
     *     summary="Create an accommodation",
     *     tags={"Accommodations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Accommodation created",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationResourceResponse")
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
    public function store(StoreAccommodationRequest $request)
    {
        $dto = AccommodationDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new AccommodationResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     operationId="showAccommodation",
     *     summary="Retrieve a single accommodation",
     *     tags={"Accommodations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         description="Accommodation identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        $accommodation = $this->service->loadRelations($accommodation);

        return StatusHelper::successResponse(new AccommodationResource($accommodation), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     operationId="updateAccommodation",
     *     summary="Update an accommodation",
     *     tags={"Accommodations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         description="Accommodation identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accommodation updated",
     *         @OA\JsonContent(ref="#/components/schemas/AccommodationResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the accommodation",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation)
    {
        $dto = AccommodationDTO::fromRequest($request);
        $updated = $this->service->update($accommodation->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $accommodation->refresh();
        $accommodation = $this->service->loadRelations($accommodation);

        return StatusHelper::successResponse(new AccommodationResource($accommodation), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/accommodations/{accommodation}",
     *     operationId="deleteAccommodation",
     *     summary="Delete an accommodation",
     *     tags={"Accommodations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         description="Accommodation identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Accommodation deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Accommodation not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Accommodation $accommodation)
    {
        $deleted = $this->service->destroy($accommodation->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
