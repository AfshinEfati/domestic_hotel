<?php

namespace App\Providers;

use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;
use App\Repositories\Eloquent\AccommodationTypeRepository;
use App\Services\AccommodationTypeService;
use App\Services\Contracts\AccommodationTypeServiceInterface;
use Illuminate\Support\ServiceProvider;

class AccommodationTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccommodationTypeRepositoryInterface::class, AccommodationTypeRepository::class);
        $this->app->bind(AccommodationTypeServiceInterface::class, AccommodationTypeService::class);
    }

    public function boot(): void
    {
        //
    }
}
