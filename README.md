# Laravel Healthcheck

This package provides a simple healthcheck endpoint for your Laravel application.

![Import Action](https://raw.githubusercontent.com/alfan06/laravel-healthcheck/main/docs/healthcheck.png)

## Installation

You can install the package via composer:

```bash
composer require alfan06/laravel-healthcheck
```

Publish the config file:

```bash
php artisan vendor:publish --provider="Alfan06\LaravelHealthcheck\HealthcheckServiceProvider"
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

];
```
