<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoomTypeNameRequest;
use App\Http\Requests\UpdateRoomTypeNameRequest;
use App\Http\Resources\RoomTypeNameResource;
use App\Models\RoomTypeName;
use App\Services\RoomTypeNameService;
use Illuminate\Http\Request;

class RoomTypeNameController extends Controller
{
    public function __construct(public RoomTypeNameService $service)
    {
    }

    public function index()
    {
        $roomTypeNames = $this->service->index();
        $roomTypeNames = RoomTypeNameResource::collection($roomTypeNames);
        return ApiResponseHelper::successResponse($roomTypeNames, 'fetched', 200);
    }

    public function store(CreateRoomTypeNameRequest $request)
    {
        $roomTypeName = $this->service->store($request->validated());
        return ApiResponseHelper::successResponse(new RoomTypeNameResource($roomTypeName), 'created', 200);
    }

    public function show(RoomTypeName $room_type_name)
    {
        return ApiResponseHelper::successResponse(RoomTypeNameResource::collection($room_type_name), 'fetched', 200);
    }

    public function update(UpdateRoomTypeNameRequest $request, RoomTypeName $room_type_name)
    {
        $updated = $this->service->update($room_type_name->id, $request->validated());
        if (!$updated) {
            return ApiResponseHelper::errorResponse('update failed', 422);
        }
        $room_type_name->refresh();
        return ApiResponseHelper::successResponse(new RoomTypeNameResource($room_type_name), 'updated', 200);
    }

    public function destroy(RoomTypeName $room_type_name)
    {
        $deleted = $this->service->destroy($room_type_name->id);

        return $deleted
            ? ApiResponseHelper::successResponse(null, 'deleted', 200)
            : ApiResponseHelper::errorResponse('delete failed', 422);
    }
}
