<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\HotelSettingDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreHotelSettingRequest;
use App\Http\Requests\UpdateHotelSettingRequest;
use App\Http\Resources\HotelSettingResource;
use App\Models\HotelSetting;
use App\Services\Contracts\HotelSettingServiceInterface;
use Illuminate\Http\JsonResponse;

class HotelSettingController
{
    public function __construct(public HotelSettingServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return StatusHelper::successResponse(
            HotelSettingResource::collection($this->service->index())
        );
    }

    public function store(StoreHotelSettingRequest $request): JsonResponse
    {
        $setting = $this->service->store(HotelSettingDTO::fromRequest($request));

        return StatusHelper::successResponse(
            new HotelSettingResource($setting),
            'created',
            201
        );
    }

    public function show(HotelSetting $hotelSetting): JsonResponse
    {
        return StatusHelper::successResponse(new HotelSettingResource($hotelSetting));
    }

    public function update(UpdateHotelSettingRequest $request, HotelSetting $hotelSetting): JsonResponse
    {
        $updated = $this->service->update(
            $hotelSetting->id,
            HotelSettingDTO::fromRequest($request)
        );

        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $hotelSetting->refresh();

        return StatusHelper::successResponse(
            new HotelSettingResource($hotelSetting),
            'updated'
        );
    }

    public function destroy(HotelSetting $hotelSetting): JsonResponse
    {
        $deleted = $this->service->destroy($hotelSetting->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
