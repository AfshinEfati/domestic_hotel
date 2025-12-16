<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Contracts\RuleRepositoryInterface::class, \App\Repositories\Eloquent\RuleRepository::class);
        $this->app->bind(\App\Services\Contracts\RuleServiceInterface::class, \App\Services\RuleService::class);
    }

    public function boot(): void
    {
        //
    }
}
