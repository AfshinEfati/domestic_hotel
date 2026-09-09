<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RoomTypeName;
use App\Helpers\ApiResponseHelper;
use App\Actions\RoomTypeName\ListRoomTypeNameAction;
use App\Actions\RoomTypeName\ShowRoomTypeNameAction;
use App\Actions\RoomTypeName\CreateRoomTypeNameAction;
use App\Actions\RoomTypeName\UpdateRoomTypeNameAction;
use App\Actions\RoomTypeName\DeleteRoomTypeNameAction;
use App\Http\Resources\RoomTypeNameResource;
use App\DTOs\RoomTypeNameDTO;
use App\Http\Requests\RoomTypeName\StoreRoomTypeNameRequest;
use App\Http\Requests\RoomTypeName\UpdateRoomTypeNameRequest;

class RoomTypeNameController
{
    public function __construct(
        public ListRoomTypeNameAction $listAction,
        public ShowRoomTypeNameAction $showAction,
        public CreateRoomTypeNameAction $createAction,
        public UpdateRoomTypeNameAction $updateAction,
        public DeleteRoomTypeNameAction $deleteAction,
    ) {

    }

    public function index()
    {
        $data = ($this->listAction)();
        return ApiResponseHelper::successResponse(RoomTypeNameResource::collection($data), 'success');
    }

    public function store(StoreRoomTypeNameRequest $request)
    {
        $dto = RoomTypeNameDTO::fromRequest($request);
        $model = ($this->createAction)($dto);
        return ApiResponseHelper::successResponse(new RoomTypeNameResource($model), 'created', 201);
    }

    public function show(RoomTypeName $roomTypeName): mixed
    {
        $model = ($this->showAction)($roomTypeName->getKey());
        if (!$model) {
            return ApiResponseHelper::errorResponse('not found', 404);
        }
        $model->load(['roomTypes']);

        return ApiResponseHelper::successResponse(new RoomTypeNameResource($model), 'success');
    }

    public function update(UpdateRoomTypeNameRequest $request, RoomTypeName $roomTypeName)
    {
        $dto = RoomTypeNameDTO::fromRequest($request);
        $model = ($this->updateAction)($roomTypeName->getKey(), $dto);
        if (!$model) {
            return ApiResponseHelper::errorResponse('update failed', 422);
        }
        $model->load(['roomTypes']);

        return ApiResponseHelper::successResponse(new RoomTypeNameResource($model), 'updated');
    }

    public function destroy(RoomTypeName $roomTypeName)
    {
        $deleted = ($this->deleteAction)($roomTypeName->getKey());
        return $deleted
            ? ApiResponseHelper::successResponse(null, 'deleted', 200)
            : ApiResponseHelper::errorResponse('delete failed', 422);
    }
}
