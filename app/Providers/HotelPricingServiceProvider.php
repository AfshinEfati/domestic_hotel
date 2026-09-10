<?php

namespace App\Providers;

use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;
use App\Repositories\Eloquent\ProviderPricingRuleRepository;
use App\Services\Contracts\HotelRatePricingServiceInterface;
use App\Services\HotelRatePricingService;
use Illuminate\Support\ServiceProvider;

class HotelPricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderPricingRuleRepositoryInterface::class, ProviderPricingRuleRepository::class);
        $this->app->bind(HotelRatePricingServiceInterface::class, HotelRatePricingService::class);
    }
}
