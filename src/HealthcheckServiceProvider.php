<?php

namespace Coreproc\LaravelHealthcheck;

use Coreproc\LaravelHealthcheck\Listeners\LogJobFailedListener;
use Coreproc\LaravelHealthcheck\Listeners\LogJobProcessedListener;
use Coreproc\LaravelHealthcheck\Listeners\LogJobProcessingListener;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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

        if (config('healthcheck.jobs.logging', true)) {
            $this->listenToQueueEvents();
        }
    }

    protected function listenToQueueEvents(): void
    {
        // Register your event listeners here
        $this->app['events']->listen(
            JobProcessing::class,
            LogJobProcessingListener::class
        );

        $this->app['events']->listen(
            JobProcessed::class,
            LogJobProcessedListener::class
        );

        $this->app['events']->listen(
            JobFailed::class,
            LogJobFailedListener::class
        );
    }
}
