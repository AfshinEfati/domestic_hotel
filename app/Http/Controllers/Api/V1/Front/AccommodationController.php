<?php

namespace App\Http\Controllers\Api\V1\Front;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\AccommodationListRequest;
use App\Http\Requests\Front\AvailabilityRequest;
use App\Http\Requests\Front\AvailableRoomsRequest;
use App\Http\Requests\Front\FacilityGroupsListRequest;
use App\Http\Requests\Front\FacilityListRequest;
use App\Http\Requests\Front\RoomTypeListRequest;
use App\Http\Requests\Front\RuleListRequest;
use App\Http\Requests\Front\RuleTypeListRequest;
use App\Http\Resources\AccommodationResource;
use App\Http\Resources\AvailabilityResource;
use App\Http\Resources\FacilityGroupResource;
use App\Http\Resources\FacilityResource;
use App\Http\Resources\HotelChildPolicyResource;
use App\Http\Resources\RoomTypeNameResource;
use App\Http\Resources\RuleResource;
use App\Services\AccommodationService;
use App\Services\Contracts\RoomCalendarServiceInterface;
use App\Services\FacilityGroupService;
use App\Services\FacilityService;
use App\Services\HotelChildPolicyService;
use App\Services\RoomTypeNameService;
use App\Services\RoomTypeService;
use App\Services\RuleService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AccommodationController extends Controller
{
    public function __construct(
        public AccommodationService    $accommodationService,
        public FacilityGroupService    $facilityGroupService,
        public FacilityService         $facilityService,
        public RoomTypeService         $roomTypeService,
        public RoomTypeNameService     $roomTypeNameService,
        public RuleService             $ruleService,
        public HotelChildPolicyService $childPolicyService
    )
    {
    }

    public function list(AccommodationListRequest $request)
    {
        $hotels = $this->accommodationService->getList($request->validated());
        if ($hotels instanceof LengthAwarePaginator) {
            return ApiResponseHelper::successResponse([
                'items' => AccommodationResource::collection($hotels->items()),
                'pagination' => [
                    'current_page' => $hotels->currentPage(),
                    'per_page' => $hotels->perPage(),
                    'last_page' => $hotels->lastPage(),
                    'total' => $hotels->total(),
                    'has_more' => $hotels->hasMorePages(),
                ],
            ], 'Accommodation List Fetched Successfully');
        }
        $hotels = AccommodationResource::collection($hotels);
        return ApiResponseHelper::successResponse($hotels, 'Accommodation List Fetched Successfully');
    }

    public function getFacilityGroups(FacilityGroupsListRequest $request)
    {
        $facilityGroups = $this->facilityGroupService->getList($request->validated());
        $facilityGroups = FacilityGroupResource::collection($facilityGroups);
        return ApiResponseHelper::successResponse($facilityGroups, 'Facility Groups List Fetched Successfully');
    }

    public function getFacilities(FacilityListRequest $request)
    {
        $facilities = $this->facilityService->getList($request->validated());
        $facilities = FacilityResource::collection($facilities);
        return ApiResponseHelper::successResponse($facilities, 'Facilities List Fetched Successfully');
    }

    public function getRoomType(RoomTypeListRequest $request)
    {
        $roomTypes = $this->roomTypeNameService->getList($request->validated());
        $roomTypes = RoomTypeNameResource::collection($roomTypes);
        return ApiResponseHelper::successResponse($roomTypes, 'Room Types List Fetched Successfully');
    }

    public function getRules(RuleListRequest $request)
    {
        $rules = $this->ruleService->getList($request->validated());
        $rules = RuleResource::collection($rules);
        return ApiResponseHelper::successResponse($rules, 'Rules List Fetched Successfully');
    }

    public function getChildPolicy(RuleListRequest $request)
    {
        $hotelChildPolicy = $this->childPolicyService->getList($request->validated());
        $hotelChildPolicy = HotelChildPolicyResource::collection($hotelChildPolicy);
        return ApiResponseHelper::successResponse($hotelChildPolicy, 'Hotel Child Policy List Fetched Successfully');
    }

    public function getAvailability(AvailabilityRequest $request)
    {
        $availability = $this->accommodationService->getAvailability($request->validated());
        $availability = AvailabilityResource::collection($availability);
        return ApiResponseHelper::successResponse($availability, 'Accommodation Availability Fetched Successfully');
    }

    public function getAvailableRooms(
        AvailableRoomsRequest        $request,
        RoomCalendarServiceInterface $roomCalendarService
    ) {
        $data = $roomCalendarService->getAvailableRoomsByAccommodationId(
            $request->validated()
        );

        return ApiResponseHelper::successResponse(
            $data,
            'Available Rooms Fetched Successfully'
        );
    }

}
