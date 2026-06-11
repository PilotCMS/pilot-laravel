<?php

namespace Pilot\Laravel;

use Illuminate\Support\ServiceProvider;
use Pilot\Laravel\Support\PilotManager;

class PilotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once __DIR__.'/helpers.php';

        $this->mergeConfigFrom(__DIR__.'/../config/pilot.php', 'pilot');

        $this->app->singleton(PilotManager::class, fn ($app): PilotManager => new PilotManager($app));
        $this->app->alias(PilotManager::class, 'pilot');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pilot');

        if (config('pilot.preview.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        $this->publishes([
            __DIR__.'/../config/pilot.php' => config_path('pilot.php'),
        ], 'pilot-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/pilot'),
        ], 'pilot-views');
    }
}
