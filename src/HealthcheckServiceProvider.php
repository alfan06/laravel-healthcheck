<?php

namespace Alfan06\LaravelHealthcheck;

use Alfan06\LaravelHealthcheck\Console\Commands\HorizonStatusLog;
use Alfan06\LaravelHealthcheck\Console\Commands\SchedulerLog;
use Illuminate\Support\ServiceProvider;

class HealthcheckServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/healthcheck.php' => config_path('healthcheck.php'),
        ], 'healthcheck-config');

        $this->loadRoutesFrom(__DIR__ . '/../routes/healthcheck.php');

        // Register the command if we are using the application via the console
        $this->commands([
            SchedulerLog::class,
            HorizonStatusLog::class,
        ]);
    }
}
