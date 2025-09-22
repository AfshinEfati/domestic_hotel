<?php

namespace App\Providers;

use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Eloquent\AccommodationRepository;
use App\Services\AccommodationService;
use App\Services\Contracts\AccommodationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AccommodationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccommodationRepositoryInterface::class, AccommodationRepository::class);
        $this->app->bind(AccommodationServiceInterface::class, AccommodationService::class);
    }

    public function boot(): void
    {
        //
    }
}
