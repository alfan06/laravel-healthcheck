<?php

use Alfan06\LaravelHealthcheck\HealthCheckController;

Route::get(config('healthcheck.path', 'healthcheck'), HealthCheckController::class);
