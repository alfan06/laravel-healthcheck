# Laravel Healthcheck

This package provides a simple healthcheck endpoint for your Laravel application.

![Import Action](https://raw.githubusercontent.com/coreproc/laravel-healthcheck/master/docs/healthcheck.png)

## Installation

You can install the package via composer:

```bash
composer require coreproc/laravel-healthcheck
```

Publish the config file:

```bash
php artisan vendor:publish --provider="Coreproc\LaravelHealthcheck\HealthcheckServiceProvider"
```

## Usage

Once installed, you can access the healthcheck endpoint at `/healthcheck`.

You can configure the path in the `config/healthcheck.php` file along with specifying which services you want to check.

```php
// config/healthcheck.php
return [

    'path' => 'healthcheck',

    'database' => true,

    'redis' => true,

    'horizon' => true,

    'scheduler' => false,

];
```

### Cron Job / Scheduler

If you want to check if the scheduler is running, you should add the following to your scheduler in
`app/Console/Kernel.php`:

```php
$schedule->call(function () {
    Cache::put('scheduler_last_run', now());
})->everyMinute();
```

This will update the cache key `scheduler_last_run` every minute. The healthcheck will check if this key has been
updated in the last 2 minutes.

### Logging for Cloudwatch

The healthcheck will log the status of the checks if there is an unavailable service. The logs will contain the
status of the service and the message through the `context` of the log.

Example log in JSON:

```json
{
    "origin": "app.web",
    "message": "Scheduler is not running.",
    "context": {
        "is_scheduler_running": false,
        "last_run": "2024-03-25 05:26:01",
        "include_in_metrics": false
    },
    "level": 400,
    "level_name": "ERROR",
    "channel": "local",
    "extra": {}
}
```

The following are the possible context of the the healthcheck logs:

- `is_database_connected` - Boolean if the database is connected.
- `is_redis_connected` - Boolean if the redis is connected.
- `is_horizon_running` - Boolean if the horizon is running.
- `is_scheduler_running` - Boolean if the scheduler is running.
