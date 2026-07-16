# Laravel Accurate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrislorando/laravel-accurate.svg?style=flat-square)](https://packagist.org/packages/chrislorando/laravel-accurate)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/chrislorando/laravel-accurate/run-tests.yml?branch=main&label=tests)](https://github.com/chrislorando/laravel-accurate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/chrislorando/laravel-accurate/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/chrislorando/laravel-accurate/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/chrislorando/laravel-accurate.svg?style=flat-square)](https://packagist.org/packages/chrislorando/laravel-accurate)

Laravel package for integrating with the Accurate Online accounting API. Provides OAuth 2.0 authentication, database management, and a fluent API client.

> 📘 Refer to the [Accurate Online API Documentation](https://accurate.id/api-integration/?a=WVGE671G) for full API reference.
>
> ⚠️ This is an **unofficial** community package. Not affiliated with [PT Cipta Piranti Sejahtera](https://cpssoft.com/) / Accurate Online.

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
    'scopes' => ['item_view', 'item_category_view', 'unit_view'],
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

Then register the routes in your `routes/web.php`:

```php
use ChrisLorando\LaravelAccurate\Facades\Accurate;
use ChrisLorando\LaravelAccurate\Http\Controllers\CallbackController;

// Start OAuth flow
Route::get('accurate/connect', function () {
    return redirect(Accurate::authorizationUrl());
})->name('accurate.connect');

// OAuth callback — handles token exchange
Route::get('accurate/callback', CallbackController::class)->name('accurate.callback');

// See available company databases (after OAuth is set up)
Route::get('/accurate/databases', function () {
    return Accurate::connection('default')->databases();
});
```

Then visit `/accurate/connect` to start the OAuth flow.

### Using the Facade

```php
use ChrisLorando\LaravelAccurate\Facades\Accurate;

// Get the authorization URL
$url = Accurate::authorizationUrl();

// ── Step 1: Check available databases ──

$dbs = Accurate::connection('default')->databases();

// $dbs → ['d' => [['id' => '1234567', 'name' => 'PT ABC', ...], ...]]
// Pick the database ID you want to work with.

// ── Step 2: Open the database (hits Accurate API) ──

Accurate::connection('default')->openDatabase('1234567');

// ── Step 3: Call API — session auto-resolves connection & database ──

// Raw endpoints (any Accurate API endpoint)
$items    = Accurate::get('api/item/list.do', ['fields' => 'id,no,name', 'sp.pageSize' => '20']);
$detail   = Accurate::get('api/item/detail.do', ['id' => '53']);
Accurate::post('api/item/save.do', ['name' => 'Kabel USB-C', 'itemType' => 'INVENTORY']);
Accurate::put('api/item/save.do', ['id' => '53', 'name' => 'Updated Item']);
Accurate::delete('api/item/delete.do', ['id' => '53']);

// Typed resources (when you have a dedicated Resource class)
Accurate::items()->list(['sp.pageSize' => 20]);
Accurate::items()->detail('53');
Accurate::items()->save(['name' => 'Item Baru', 'itemType' => 'INVENTORY']);
Accurate::items()->delete('53');

Accurate::itemCategories()->list();
Accurate::itemCategories()->save(['name' => 'Elektronik']);

Accurate::units()->list();
Accurate::units()->save(['unitName' => 'Pcs']);

// Generic resource by name (no class needed)
Accurate::resource('customer')->list();
Accurate::resource('sales-invoice')->detail('123');
```

### Background Jobs & Multi-DB

The HTTP session is unavailable in queue jobs, CLI commands, or when you need
to switch databases without a costly API call. Use `on()` to resolve a
previously-opened database **from the local DB table** — no HTTP overhead.

```php
// Job / command / multi-DB — resolves session from the accurate_databases table
Accurate::connection('default')
    ->on('1234567')           // ← no API call, uses cached session_id
    ->items()->list();

// Switch to another DB mid-request, also zero HTTP calls
$itemsA = Accurate::connection('default')->on('1234567')->items()->list();
$itemsB = Accurate::connection('default')->on('99999')->items()->list();

// Works with ALL calling styles:
Accurate::connection('default')->on('1234567')->get('api/item/list.do', ['sp.pageSize' => '20']);
Accurate::connection('default')->on('1234567')->post('api/item/save.do', ['name' => 'Kabel USB-C', 'itemType' => 'INVENTORY']);
Accurate::connection('default')->on('1234567')->resource('customer')->list();
Accurate::connection('default')->on('1234567')->items()->query()
    ->select('id', 'no', 'name')
    ->where('keywords', 'like', 'Kabel')
    ->get();
```

| Method               | API call? | Use case                                |
| -------------------- | --------- | --------------------------------------- |
| `openDatabase('id')` | ✅ Yes    | First-time setup, refresh session       |
| `on('id')`           | ❌ No     | Background jobs, CLI, fast DB switching |

> 💡 **Calling styles summary:**
>
> - **Bare**: `Accurate::get()` / `Accurate::items()` — session auto-resolves connection + database.
> - **Explicit connection**: `Accurate::connection('default')->items()` — pick a specific connection.
> - **Open database**: `connection('x')->openDatabase('y')` — set/switch the active database for the session.
> - **On (no API)**: `connection('x')->on('y')` — resolve DB from the local table, zero HTTP.
>
> ⚠️ The Facade is a singleton — one active database at a time per request.

> ℹ️ **Dedicated classes** exist for `item` (`items()`), `item-category` (`itemCategories()`), and `unit` (`units()`).
> For any other resource use `Accurate::resource('customer')` or raw endpoints — both shown above.
> More dedicated resources will be added over time.

### Query Builder

Fluent query builder with filter, sort, pagination, and shorthand operators.
Available on every resource via `->query()`:

```php
// Dedicated resource
$results = Accurate::items()->query()
    ->select('id', 'no', 'name', 'unit1NameWarehouse')
    ->where('keywords', 'like', 'Kabel')
    ->where('itemType', 'INVENTORY')
    ->where('unitPrice', '>', 10000)
    ->orderBy('name', 'asc')
    ->limit(20)
    ->page(1)
    ->get();

// Generic resource — same fluent API
$customers = Accurate::resource('customer')->query()
    ->select('id', 'name', 'email')
    ->where('keywords', 'like', 'PT')
    ->limit(10)
    ->get();

// Get single record (or null)
$first = Accurate::items()->query()
    ->select('id', 'name')
    ->orderBy('id', 'desc')
    ->first();

// Paginate with metadata (sp.page, sp.pageSize, sp.totalPage, sp.totalData)
$paged = Accurate::items()->query()
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

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on development setup, coding standards, and how to add new resource classes.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Chris Manuel Lorando](https://github.com/chrislorando)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
