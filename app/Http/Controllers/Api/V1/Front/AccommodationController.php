<?php

namespace App\Http\Controllers\Api\V1\Front;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\AccommodationListRequest;
use App\Http\Resources\AccommodationResource;
use App\Services\AccommodationService;
use Illuminate\Http\Request;

class AccommodationController extends Controller
{
    public function __construct(public AccommodationService $accommodationService)
    {
    }

    public function list(AccommodationListRequest $request)
    {
        $hotels = $this->accommodationService->getList($request->validated());
        $hotels = AccommodationResource::collection($hotels);
        return ApiResponseHelper::successResponse($hotels, 'Accommodation List Fetched Successfully');
    }
}
