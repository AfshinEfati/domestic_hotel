<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\HotelChildPolicy;
use App\Helpers\ApiResponseHelper;
use App\Actions\HotelChildPolicy\ListHotelChildPolicyAction;
use App\Actions\HotelChildPolicy\ShowHotelChildPolicyAction;
use App\Actions\HotelChildPolicy\CreateHotelChildPolicyAction;
use App\Actions\HotelChildPolicy\UpdateHotelChildPolicyAction;
use App\Actions\HotelChildPolicy\DeleteHotelChildPolicyAction;
use App\Http\Resources\HotelChildPolicyResource;
use App\DTOs\HotelChildPolicyDTO;
use App\Http\Requests\HotelChildPolicy\StoreHotelChildPolicyRequest;
use App\Http\Requests\HotelChildPolicy\UpdateHotelChildPolicyRequest;

class HotelChildPolicyController
{
    public function __construct(
        public ListHotelChildPolicyAction $listAction,
        public ShowHotelChildPolicyAction $showAction,
        public CreateHotelChildPolicyAction $createAction,
        public UpdateHotelChildPolicyAction $updateAction,
        public DeleteHotelChildPolicyAction $deleteAction,
    ) {

    }

    public function index()
    {
        $data = ($this->listAction)();
        return ApiResponseHelper::successResponse(HotelChildPolicyResource::collection($data), 'success');
    }

    public function store(StoreHotelChildPolicyRequest $request)
    {
        $dto = HotelChildPolicyDTO::fromRequest($request);
        $model = ($this->createAction)($dto);
        return ApiResponseHelper::successResponse(new HotelChildPolicyResource($model), 'created', 201);
    }

    public function show(HotelChildPolicy $hotelChildPolicy): mixed
    {
        $model = ($this->showAction)($hotelChildPolicy->getKey());
        if (!$model) {
            return ApiResponseHelper::errorResponse('not found', 404);
        }
        $model->load(['accommodation']);

        return ApiResponseHelper::successResponse(new HotelChildPolicyResource($model), 'success');
    }

    public function update(UpdateHotelChildPolicyRequest $request, HotelChildPolicy $hotelChildPolicy)
    {
        $dto = HotelChildPolicyDTO::fromRequest($request);
        $model = ($this->updateAction)($hotelChildPolicy->getKey(), $dto);
        if (!$model) {
            return ApiResponseHelper::errorResponse('update failed', 422);
        }
        $model->load(['accommodation']);

        return ApiResponseHelper::successResponse(new HotelChildPolicyResource($model), 'updated');
    }

    public function destroy(HotelChildPolicy $hotelChildPolicy)
    {
        $deleted = ($this->deleteAction)($hotelChildPolicy->getKey());
        return $deleted
            ? ApiResponseHelper::successResponse(null, 'deleted', 200)
            : ApiResponseHelper::errorResponse('delete failed', 422);
    }
}
