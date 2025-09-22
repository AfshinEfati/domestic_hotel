<?php

namespace App\Providers;

use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use App\Repositories\Contracts\ProviderCityMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;
use App\Repositories\Contracts\RatePlanRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarSnapshotRepositoryInterface;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;
use App\Repositories\Contracts\StateRepositoryInterface;
use App\Repositories\Eloquent\AccommodationProviderMapRepository;
use App\Repositories\Eloquent\CityRepository;
use App\Repositories\Eloquent\CountryRepository;
use App\Repositories\Eloquent\FacilityGroupRepository;
use App\Repositories\Eloquent\FacilityRepository;
use App\Repositories\Eloquent\ProviderCityMapRepository;
use App\Repositories\Eloquent\ProviderRepository;
use App\Repositories\Eloquent\RatePlanProviderMapRepository;
use App\Repositories\Eloquent\RatePlanRepository;
use App\Repositories\Eloquent\RoomCalendarRepository;
use App\Repositories\Eloquent\RoomCalendarSnapshotRepository;
use App\Repositories\Eloquent\RoomTypeProviderMapRepository;
use App\Repositories\Eloquent\RoomTypeRepository;
use App\Repositories\Eloquent\StateRepository;
use App\Services\AccommodationProviderMapService;
use App\Services\CityService;
use App\Services\Contracts\AccommodationProviderMapServiceInterface;
use App\Services\Contracts\CityServiceInterface;
use App\Services\Contracts\CountryServiceInterface;
use App\Services\Contracts\FacilityGroupServiceInterface;
use App\Services\Contracts\FacilityServiceInterface;
use App\Services\Contracts\ProviderCityMapServiceInterface;
use App\Services\Contracts\ProviderServiceInterface;
use App\Services\Contracts\RatePlanProviderMapServiceInterface;
use App\Services\Contracts\RatePlanServiceInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;
use App\Services\Contracts\RoomCalendarSnapshotServiceInterface;
use App\Services\Contracts\RoomTypeProviderMapServiceInterface;
use App\Services\Contracts\RoomTypeServiceInterface;
use App\Services\Contracts\StateServiceInterface;
use App\Services\CountryService;
use App\Services\FacilityGroupService;
use App\Services\FacilityService;
use App\Services\ProviderCityMapService;
use App\Services\ProviderService;
use App\Services\RatePlanProviderMapService;
use App\Services\RatePlanService;
use App\Services\RoomCalendarService;
use App\Services\RoomCalendarSnapshotService;
use App\Services\RoomTypeProviderMapService;
use App\Services\RoomTypeService;
use App\Services\StateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            CityRepositoryInterface::class => CityRepository::class,
            CityServiceInterface::class => CityService::class,
            CountryRepositoryInterface::class => CountryRepository::class,
            CountryServiceInterface::class => CountryService::class,
            StateRepositoryInterface::class => StateRepository::class,
            StateServiceInterface::class => StateService::class,
            ProviderRepositoryInterface::class => ProviderRepository::class,
            ProviderServiceInterface::class => ProviderService::class,
            FacilityGroupRepositoryInterface::class => FacilityGroupRepository::class,
            FacilityGroupServiceInterface::class => FacilityGroupService::class,
            FacilityRepositoryInterface::class => FacilityRepository::class,
            FacilityServiceInterface::class => FacilityService::class,
            RatePlanRepositoryInterface::class => RatePlanRepository::class,
            RatePlanServiceInterface::class => RatePlanService::class,
            RoomTypeRepositoryInterface::class => RoomTypeRepository::class,
            RoomTypeServiceInterface::class => RoomTypeService::class,
            RoomCalendarRepositoryInterface::class => RoomCalendarRepository::class,
            RoomCalendarServiceInterface::class => RoomCalendarService::class,
            RoomCalendarSnapshotRepositoryInterface::class => RoomCalendarSnapshotRepository::class,
            RoomCalendarSnapshotServiceInterface::class => RoomCalendarSnapshotService::class,
            ProviderCityMapRepositoryInterface::class => ProviderCityMapRepository::class,
            ProviderCityMapServiceInterface::class => ProviderCityMapService::class,
            AccommodationProviderMapRepositoryInterface::class => AccommodationProviderMapRepository::class,
            AccommodationProviderMapServiceInterface::class => AccommodationProviderMapService::class,
            RoomTypeProviderMapRepositoryInterface::class => RoomTypeProviderMapRepository::class,
            RoomTypeProviderMapServiceInterface::class => RoomTypeProviderMapService::class,
            RatePlanProviderMapRepositoryInterface::class => RatePlanProviderMapRepository::class,
            RatePlanProviderMapServiceInterface::class => RatePlanProviderMapService::class,
        ];

        foreach ($bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
