<?php

namespace App\Providers;

use App\Support\Queue\ResilientCallQueuedHandler;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CallQueuedHandler::class, ResilientCallQueuedHandler::class);

        // Named connection only; never replace the application's default database.
        // Config values, rather than env() here, remain compatible with config:cache.
        config(['database.connections.shared_ssp' => config('grs.shared_db')]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
