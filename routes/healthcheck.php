<?php

use Coreproc\LaravelHealthcheck\HealthCheckController;

Route::get(config('healthcheck.path', 'healthcheck'), HealthCheckController::class);
