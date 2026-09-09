<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RoomTypeNameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Contracts\RoomTypeNameRepositoryInterface::class, \App\Repositories\Eloquent\RoomTypeNameRepository::class);
        $this->app->bind(\App\Services\Contracts\RoomTypeNameServiceInterface::class, \App\Services\RoomTypeNameService::class);
    }

    public function boot(): void
    {
        //
    }
}
