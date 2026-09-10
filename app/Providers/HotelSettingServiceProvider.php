<?php

namespace App\Providers;

use App\Repositories\Contracts\HotelSettingRepositoryInterface;
use App\Repositories\Eloquent\HotelSettingRepository;
use App\Services\Contracts\HotelSettingServiceInterface;
use App\Services\HotelSettingService;
use Illuminate\Support\ServiceProvider;

class HotelSettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HotelSettingRepositoryInterface::class, HotelSettingRepository::class);
        $this->app->bind(HotelSettingServiceInterface::class, HotelSettingService::class);
    }
}
