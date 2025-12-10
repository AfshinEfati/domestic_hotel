<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProviderDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Providers",
 *     description="Manage provider integrations."
 * )
 */
class ProviderController
{
    public function __construct(public ProviderService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/providers",
     *     operationId="listProviders",
     *     summary="List providers",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderCollectionResponse")
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

        return StatusHelper::successResponse(ProviderResource::collection($data), 'success');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/providers",
     *     operationId="createProvider",
     *     summary="Create a provider",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProviderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Provider created",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderResourceResponse")
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
    public function store(StoreProviderRequest $request): JsonResponse
    {
        $dto = ProviderDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new ProviderResource($model), 'created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/providers/{provider}",
     *     operationId="showProvider",
     *     summary="Retrieve a provider",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Provider identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Provider not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Provider $provider): JsonResponse
    {
        $provider = $this->service->loadRelations($provider);

        return StatusHelper::successResponse(new ProviderResource($provider), 'success');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/providers/{provider}",
     *     operationId="updateProvider",
     *     summary="Update a provider",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Provider identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProviderUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Provider updated",
     *         @OA\JsonContent(ref="#/components/schemas/ProviderResourceResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Provider not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unable to update the provider",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateProviderRequest $request, Provider $provider): JsonResponse
    {
        $dto = ProviderDTO::fromRequest($request);
        $updated = $this->service->update($provider->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $provider->refresh();
        $provider = $this->service->loadRelations($provider);

        return StatusHelper::successResponse(new ProviderResource($provider), 'updated');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/providers/{provider}",
     *     operationId="deleteProvider",
     *     summary="Delete a provider",
     *     tags={"Providers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Provider identifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Provider deleted",
     *         @OA\JsonContent(ref="#/components/schemas/EmptySuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Provider not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Provider $provider): JsonResponse
    {
        $deleted = $this->service->destroy($provider->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
