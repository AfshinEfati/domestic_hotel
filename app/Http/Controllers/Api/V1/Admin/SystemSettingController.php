<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\SystemSettingDTO;
use App\Helpers\StatusHelper;
use App\Http\Requests\StoreSystemSettingRequest;
use App\Http\Requests\UpdateSystemSettingRequest;
use App\Http\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use App\Services\Contracts\SystemSettingServiceInterface;
use Illuminate\Http\JsonResponse;

class SystemSettingController
{
    public function __construct(public SystemSettingServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return StatusHelper::successResponse(
            SystemSettingResource::collection($this->service->index())
        );
    }

    public function store(StoreSystemSettingRequest $request): JsonResponse
    {
        $setting = $this->service->store(SystemSettingDTO::fromRequest($request));

        return StatusHelper::successResponse(
            new SystemSettingResource($setting),
            'created',
            201
        );
    }

    public function show(SystemSetting $systemSetting): JsonResponse
    {
        return StatusHelper::successResponse(new SystemSettingResource($systemSetting));
    }

    public function update(UpdateSystemSettingRequest $request, SystemSetting $systemSetting): JsonResponse
    {
        $updated = $this->service->update(
            $systemSetting->id,
            SystemSettingDTO::fromRequest($request)
        );

        if (!$updated) {
            return StatusHelper::errorResponse('update failed', 422);
        }

        $systemSetting->refresh();

        return StatusHelper::successResponse(
            new SystemSettingResource($systemSetting),
            'updated'
        );
    }

    public function destroy(SystemSetting $systemSetting): JsonResponse
    {
        $deleted = $this->service->destroy($systemSetting->id);

        return $deleted
            ? StatusHelper::successResponse(null, 'deleted')
            : StatusHelper::errorResponse('delete failed', 422);
    }
}
