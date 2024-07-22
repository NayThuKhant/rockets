<?php

namespace App\Providers;

use App\Services\RocketService;
use Illuminate\Support\ServiceProvider;

class RocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Rocket Service is just a http proxy service and shouldn't be re instantiated everytime we need it
        $this->app->singleton(RocketService::class, RocketService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
