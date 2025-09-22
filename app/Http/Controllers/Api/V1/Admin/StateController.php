<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\StateDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Http\Resources\StateResource;
use App\Models\State;
use App\Services\StateService;
use Illuminate\Http\JsonResponse;

class StateController
{
    public function __construct(public StateService $service) {}

    public function index(): JsonResponse
    {
        $data = $this->service->index();

        return StatusHelper::successResponse(StateResource::collection($data), 'success');
    }

    public function store(StoreStateRequest $request): JsonResponse
    {
        $dto = StateDTO::fromRequest($request);
        $model = $this->service->store($dto);

        return StatusHelper::successResponse(new StateResource($model), 'created', 201);
    }

    public function show(State $state): JsonResponse
    {
        return StatusHelper::successResponse(new StateResource($state), 'success');
    }

    public function update(UpdateStateRequest $request, State $state): JsonResponse
    {
        $dto = StateDTO::fromRequest($request);
        $updated = $this->service->update($state->id, $dto);
        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $state->refresh();

        return StatusHelper::successResponse(new StateResource($state), 'updated');
    }

    public function destroy(State $state): JsonResponse
    {
        $deleted = $this->service->destroy($state->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted', 204)
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
