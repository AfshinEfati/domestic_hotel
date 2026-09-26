<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    AccommodationProviderMapRepositoryInterface,
    AccommodationRepositoryInterface,
    AccommodationTypeRepositoryInterface,
    CityRepositoryInterface,
    CountryRepositoryInterface,
    FacilityGroupRepositoryInterface,
    FacilityRepositoryInterface,
    ProviderCityMapRepositoryInterface,
    ProviderRepositoryInterface,
    ProviderRequestRepositoryInterface,
    RatePlanProviderMapRepositoryInterface,
    RatePlanRepositoryInterface,
    RoomCalendarRepositoryInterface,
    RoomCalendarSnapshotRepositoryInterface,
    RoomTypeProviderMapRepositoryInterface,
    RoomTypeRepositoryInterface,
    StateRepositoryInterface,
    SystemLogRepositoryInterface,
    RoomTypeNameRepositoryInterface,
};
use App\Repositories\Eloquent\{
    AccommodationProviderMapRepository,
    AccommodationRepository,
    AccommodationTypeRepository,
    CityRepository,
    CountryRepository,
    FacilityGroupRepository,
    FacilityRepository,
    ProviderCityMapRepository,
    ProviderRepository,
    ProviderRequestRepository,
    RatePlanProviderMapRepository,
    RatePlanRepository,
    RoomCalendarRepository,
    RoomCalendarSnapshotRepository,
    RoomTypeProviderMapRepository,
    RoomTypeRepository,
    StateRepository,
    SystemLogRepository,
    RoomTypeNameRepository,
};
use App\Services\{
    AccommodationProviderMapService,
    AccommodationService,
    AccommodationTypeService,
    CityService,
    CountryService,
    FacilityGroupService,
    FacilityService,
    ProviderCityMapService,
    ProviderService,
    ProviderRequestService,
    RatePlanProviderMapService,
    RatePlanService,
    RoomCalendarService,
    RoomCalendarSnapshotService,
    RoomTypeProviderMapService,
    RoomTypeService,
    StateService,
    RoomTypeNameService,
};
use App\Services\Contracts\{
    AccommodationProviderMapServiceInterface,
    AccommodationServiceInterface,
    AccommodationTypeServiceInterface,
    CityServiceInterface,
    CountryServiceInterface,
    FacilityGroupServiceInterface,
    FacilityServiceInterface,
    ProviderCityMapServiceInterface,
    ProviderServiceInterface,
    ProviderRequestServiceInterface,
    RatePlanProviderMapServiceInterface,
    RatePlanServiceInterface,
    RoomCalendarServiceInterface,
    RoomCalendarSnapshotServiceInterface,
    RoomTypeProviderMapServiceInterface,
    RoomTypeServiceInterface,
    StateServiceInterface,
    RoomTypeNameServiceInterface,
};
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const BINDINGS = [
        // Accommodation domain
        AccommodationRepositoryInterface::class => AccommodationRepository::class,
        AccommodationServiceInterface::class => AccommodationService::class,
        AccommodationTypeRepositoryInterface::class => AccommodationTypeRepository::class,
        AccommodationTypeServiceInterface::class => AccommodationTypeService::class,
        AccommodationProviderMapRepositoryInterface::class => AccommodationProviderMapRepository::class,
        AccommodationProviderMapServiceInterface::class => AccommodationProviderMapService::class,

        // Location domain
        CityRepositoryInterface::class => CityRepository::class,
        CityServiceInterface::class => CityService::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        CountryServiceInterface::class => CountryService::class,
        StateRepositoryInterface::class => StateRepository::class,
        StateServiceInterface::class => StateService::class,

        // Provider domain
        ProviderRepositoryInterface::class => ProviderRepository::class,
        ProviderServiceInterface::class => ProviderService::class,
        ProviderRequestRepositoryInterface::class => ProviderRequestRepository::class,
        ProviderRequestServiceInterface::class => ProviderRequestService::class,
        ProviderCityMapRepositoryInterface::class => ProviderCityMapRepository::class,
        ProviderCityMapServiceInterface::class => ProviderCityMapService::class,

        // Facilities domain
        FacilityGroupRepositoryInterface::class => FacilityGroupRepository::class,
        FacilityGroupServiceInterface::class => FacilityGroupService::class,
        FacilityRepositoryInterface::class => FacilityRepository::class,
        FacilityServiceInterface::class => FacilityService::class,

        // Rate plan domain
        RatePlanRepositoryInterface::class => RatePlanRepository::class,
        RatePlanServiceInterface::class => RatePlanService::class,
        RatePlanProviderMapRepositoryInterface::class => RatePlanProviderMapRepository::class,
        RatePlanProviderMapServiceInterface::class => RatePlanProviderMapService::class,

        // Room domain
        RoomTypeRepositoryInterface::class => RoomTypeRepository::class,
        RoomTypeServiceInterface::class => RoomTypeService::class,
        RoomTypeProviderMapRepositoryInterface::class => RoomTypeProviderMapRepository::class,
        RoomTypeProviderMapServiceInterface::class => RoomTypeProviderMapService::class,
        RoomCalendarRepositoryInterface::class => RoomCalendarRepository::class,
        RoomCalendarServiceInterface::class => RoomCalendarService::class,
        RoomCalendarSnapshotRepositoryInterface::class => RoomCalendarSnapshotRepository::class,
        RoomCalendarSnapshotServiceInterface::class => RoomCalendarSnapshotService::class,
        RoomTypeNameRepositoryInterface::class => RoomTypeNameRepository::class,
        RoomTypeNameServiceInterface::class => RoomTypeNameService::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
