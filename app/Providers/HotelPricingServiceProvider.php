<?php

namespace App\Providers;

use App\Repositories\Contracts\HotelSettingRepositoryInterface;
use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;
use App\Repositories\Eloquent\HotelSettingRepository;
use App\Repositories\Eloquent\ProviderPricingRuleRepository;
use App\Services\AvailabilityRateDecoratorService;
use App\Services\Contracts\AvailabilityRateDecoratorServiceInterface;
use App\Services\Contracts\HotelRatePricingServiceInterface;
use App\Services\Contracts\HotelSettingServiceInterface;
use App\Services\Contracts\ProviderPricingRuleServiceInterface;
use App\Services\HotelRatePricingService;
use App\Services\HotelSettingService;
use App\Services\ProviderPricingRuleService;
use Illuminate\Support\ServiceProvider;

class HotelPricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HotelSettingRepositoryInterface::class, HotelSettingRepository::class);
        $this->app->bind(HotelSettingServiceInterface::class, HotelSettingService::class);
        $this->app->bind(ProviderPricingRuleRepositoryInterface::class, ProviderPricingRuleRepository::class);
        $this->app->bind(ProviderPricingRuleServiceInterface::class, ProviderPricingRuleService::class);
        $this->app->bind(HotelRatePricingServiceInterface::class, HotelRatePricingService::class);
        $this->app->bind(AvailabilityRateDecoratorServiceInterface::class, AvailabilityRateDecoratorService::class);
    }
}
