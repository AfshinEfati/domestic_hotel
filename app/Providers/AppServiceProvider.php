<?php

namespace App\Providers;

use App\Listeners\ProviderHttpAlertListener;
use App\Listeners\ProviderHttpRequestListener;
use App\Support\Queue\ResilientCallQueuedHandler;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CallQueuedHandler::class, ResilientCallQueuedHandler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ResponseReceived::class, [ProviderHttpAlertListener::class, 'response']);
        Event::listen(ConnectionFailed::class, [ProviderHttpAlertListener::class, 'connectionFailed']);
        Event::listen(ResponseReceived::class, [ProviderHttpRequestListener::class, 'response']);
        Event::listen(ConnectionFailed::class, [ProviderHttpRequestListener::class, 'connectionFailed']);
    }
}
