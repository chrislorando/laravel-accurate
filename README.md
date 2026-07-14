# Laravel Accurate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrislorando/laravel-accurate.svg?style=flat-square)](https://packagist.org/packages/chrislorando/laravel-accurate)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/chrislorando/laravel-accurate/run-tests.yml?branch=main&label=tests)](https://github.com/chrislorando/laravel-accurate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/chrislorando/laravel-accurate/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/chrislorando/laravel-accurate/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/chrislorando/laravel-accurate.svg?style=flat-square)](https://packagist.org/packages/chrislorando/laravel-accurate)

Laravel package for integrating with the Accurate Online accounting API. Provides OAuth 2.0 authentication, database management, and a fluent API client.

> 📘 Refer to the [Accurate Online API Documentation](https://accurate.id/api-integration/?a=WVGE671G) for full API reference.

## Installation

You can install the package via composer:

```bash
composer require chrislorando/laravel-accurate
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="accurate-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="accurate-config"
```

This is the contents of the published config file:

```php
return [
    'client_id' => env('ACCURATE_CLIENT_ID'),
    'client_secret' => env('ACCURATE_CLIENT_SECRET'),
    'redirect_uri' => env('ACCURATE_REDIRECT_URI'),
    'base_url' => env('ACCURATE_BASE_URL', 'https://account.accurate.id'),
    'timeout' => 30,
    'verify_ssl' => true,
    'routes' => [
        'enabled' => true,
    ],
    'scopes' => ['item_view', 'invoice_view', 'customer_view'],
];
```

## Usage

### OAuth Connect Flow

Add the following environment variables to your `.env`:

```env
ACCURATE_BASE_URL=https://account.accurate.id
ACCURATE_CLIENT_ID=your-client-id
ACCURATE_CLIENT_SECRET=your-client-secret
ACCURATE_REDIRECT_URI=https://your-app.test/accurate/callback

```

Then visit `/accurate/connect` to start the OAuth flow.

### Using the Facade

```php
use ChrisLorando\LaravelAccurate\Facades\Accurate;

// Get the authorization URL
$url = Accurate::authorizationUrl();

// Get database list for a connection
$databases = Accurate::connection('default')->databases();

// Open a specific database
$db = Accurate::connection('default')->openDatabase('123456');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Chris Manuel Lorando](https://github.com/chrislorando)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
