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

Then register the callback route in your `routes/web.php`:

```php
use ChrisLorando\LaravelAccurate\Http\Controllers\CallbackController;

Route::get('accurate/callback', CallbackController::class)->name('accurate.callback');
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

### Resource API (Item)

Every Accurate resource (item, customer, invoice, etc.) exposes CRUD operations via a consistent resource class.

#### Basic CRUD

```php
$items = Accurate::connection('default')
    ->openDatabase('2759883')
    ->items();

// List all items
$all = $items->list(['sp.pageSize' => 20]);

// Get a single item by ID
$detail = $items->detail('53');

// Create or update an item
$saved = $items->save([
    'name'     => 'Kabel USB-C',
    'itemType' => 'INVENTORY',
    'unit1Name'=> 'Pcs',
]);

// Delete an item by ID
$items->delete('53');

// Bulk save (max 100 items per request)
$items->bulkSave([
    'data' => [
        ['name' => 'Item A', 'itemType' => 'INVENTORY'],
        ['name' => 'Item B', 'itemType' => 'INVENTORY'],
    ],
]);
```

### Query Builder

Fluent query builder with filter, sort, pagination, and shorthand operators:

```php
$results = Accurate::connection('default')
    ->openDatabase('2759883')
    ->items()
    ->query()
    ->select('id', 'no', 'name', 'unit1NameWarehouse')
    ->where('keywords', 'like', 'Kabel')     // CONTAIN search
    ->where('itemType', 'INVENTORY')         // EQUAL (default)
    ->where('unitPrice', '>', 10000)         // GREATER_THAN
    ->orderBy('name', 'asc')
    ->limit(20)
    ->page(1)
    ->get();

// Get single record (or null)
$first = Accurate::connection('default')
    ->openDatabase('2759883')
    ->items()
    ->query()
    ->select('id', 'name')
    ->orderBy('id', 'desc')
    ->first();

// Paginate with metadata (sp.page, sp.pageSize, sp.totalPage, sp.totalData)
$paged = Accurate::connection('default')
    ->openDatabase('2759883')
    ->items()
    ->query()
    ->select('id', 'name')
    ->where('keywords', 'like', 'Kabel')
    ->limit(10)
    ->paginate();

// $paged['data']  → array of items
// $paged['sp']    → pagination metadata (page, pageSize, totalPage, totalData)
```

#### Shorthand Operator Mapping

| Argument                    | Accurate Operator    | Example                                  |
| --------------------------- | -------------------- | ---------------------------------------- |
| 2-arg `where(field, value)` | `EQUAL`              | `where('itemType', 'INVENTORY')`         |
| `>` / `gt`                  | `GREATER_THAN`       | `where('price', '>', 100)`               |
| `>=` / `gte`                | `GREATER_EQUAL_THAN` | `where('price', '>=', 100)`              |
| `<` / `lt`                  | `LESS_THAN`          | `where('price', '<', 100)`               |
| `<=` / `lte`                | `LESS_EQUAL_THAN`    | `where('price', '<=', 100)`              |
| `!=` / `<>`                 | `NOT_EQUAL`          | `where('name', '!=', 'test')`            |
| `like` / `LIKE`             | `CONTAIN`            | `where('keywords', 'like', 'Kabel')`     |
| `between`                   | `BETWEEN`            | `where('price', 'between', [1,100])`     |
| `not_between`               | `NOT_BETWEEN`        | `where('price', 'not_between', [1,100])` |
| `empty`                     | `EMPTY`              | `where('name', 'empty')`                 |
| `not_empty`                 | `NOT_EMPTY`          | `where('name', 'not_empty')`             |
| Accurate-native             | pass-through         | `where('name', 'EQUAL', 'test')`         |

> **Note**: `CONTAIN` operator only works on `filter.keywords` field. Use `where('keywords', 'like', '...')` for partial text search.

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
