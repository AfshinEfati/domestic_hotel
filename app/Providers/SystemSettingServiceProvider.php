<?php

namespace App\Providers;

use App\Repositories\Contracts\SystemSettingRepositoryInterface;
use App\Repositories\Eloquent\SystemSettingRepository;
use App\Services\Contracts\SystemSettingServiceInterface;
use App\Services\SystemSettingService;
use Illuminate\Support\ServiceProvider;

class SystemSettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);
        $this->app->bind(SystemSettingServiceInterface::class, SystemSettingService::class);
    }
}
