<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Requirements

- PHP >= 8.2
- Redis 5.0+ / Memcached 1.4+ / DynamoDB 2012-08-10 / Database (MySQL, PostgreSQL, SQLite, SQL Server)
- Horizon 5.0+ 


## Installation

```bash
composer require laravel/horizon
```


## Database Migrations

```bash
php artisan horizon:install
php artisan migrate
php artisan db:seed
php artisan provider:city grs
php artisan hotel:sync-parto-cities
php artisan hotel:sync-facilities grs
``` 


## Workers
```bash
php artisan horizon
```

## API Documentation

L5 Swagger is configured to generate and serve the OpenAPI description for the admin API.

1. Generate the specification locally with `composer swagger` or `php artisan l5-swagger:generate --ansi`.
2. The JSON document is published to `storage/app/public/api-docs/api-docs.json` (plus a YAML copy when enabled).
3. Serve the UI by running the application and browsing to `/api/documentation`.

> **Note:** The `composer test` script regenerates the specification before executing the test suite to ensure the documentation stays in sync with the latest annotations.

