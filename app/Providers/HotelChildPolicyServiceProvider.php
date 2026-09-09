<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HotelChildPolicyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Contracts\HotelChildPolicyRepositoryInterface::class, \App\Repositories\Eloquent\HotelChildPolicyRepository::class);
        $this->app->bind(\App\Services\Contracts\HotelChildPolicyServiceInterface::class, \App\Services\HotelChildPolicyService::class);
    }

    public function boot(): void
    {
        //
    }
}
