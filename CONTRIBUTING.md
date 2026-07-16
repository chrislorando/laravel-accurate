# Contributing

Thanks for your interest in `laravel-accurate`! Here's how you can help.

## Development Setup

```bash
git clone git@github.com:chrislorando/laravel-accurate.git
cd laravel-accurate
composer install
cp .env.example .env     # if one exists, otherwise skip
```

The package is developed inside a Laravel app context — your working directory is the package root, and tests run against the embedded Laravel testbench.

## Running Tests

```bash
composer test            # Run Pest test suite
composer test-coverage   # Run with coverage (Xdebug/PCOV required)
```

> **Always run tests before opening a PR.** We aim for 100% passing with meaningful assertions.

## Code Style

We use **Laravel Pint** with the default Laravel preset:

```bash
vendor/bin/pint          # Auto-fix code style
vendor/bin/pint --test   # Dry-run, fail on issues (CI check)
```

CI runs Pint automatically via `fix-php-code-style-issues.yml`. If Pint makes changes, it will push an extra commit — **run Pint locally first** to avoid this.

## Static Analysis

```bash
vendor/bin/phpstan       # PHPStan at level 5
```

## Project Structure

```
src/
├── LaravelAccurate.php            # Main facade class — API methods, resource routing
├── Facades/Accurate.php           # Facade docblock (IDE autocomplete)
├── OAuth/                         # OAuth 2.0 client (authorize + token exchange)
├── Http/
│   ├── ApiClient.php              # Low-level HTTP client (Guzzle wrapper)
│   ├── AccountClient.php          # Account-level endpoints (databases, open)
│   ├── Controllers/               # HTTP controllers (e.g. CallbackController)
│   └── Resources/                 # Dedicated resource classes
│       ├── Resource.php           # Abstract base — list, detail, save, delete, bulkSave, query
│       ├── ItemResource.php       # /api/item
│       ├── ItemCategoryResource.php # /api/item-category
│       └── UnitResource.php       # /api/unit
├── Models/                        # Eloquent models (AccurateConnection, AccurateDatabase)
└── Commands/                      # Artisan commands

tests/
├── Feature/
│   ├── LaravelAccurateTest.php     # Facade, raw endpoints, on(), generic resource
│   ├── ApiClientTest.php           # HTTP client error handling
│   ├── QueryBuilderTest.php        # Fluent query builder
│   ├── OAuthTest.php              # OAuth URL generation
│   ├── ItemResourceTest.php        # ItemResource direct tests
│   ├── ItemCategoryResourceTest.php # ItemCategoryResource direct tests
│   └── UnitResourceTest.php        # UnitResource direct tests
├── TestCase.php                    # Base test case
└── Pest.php                        # Pest configuration + global helpers (makeApiClient)
```

## Adding a New Dedicated Resource

1. **Create the class** in `src/Http/Resources/`:

```php
class SalesInvoiceResource extends Resource
{
    protected string $resourceName = 'sales-invoice';
}
```

2. **Register it** in `src/LaravelAccurate.php`:
    - Add `use` import
    - Add a `'sales-invoice' => ...` case in the `resource()` match
    - Add a `salesInvoices()` convenience shortcut

3. **Update the facade docblock** in `src/Facades/Accurate.php`:

```php
@method static \ChrisLorando\LaravelAccurate\Http\Resources\SalesInvoiceResource salesInvoices()
```

4. **Add tests** — create `tests/Feature/SalesInvoiceResourceTest.php` following the same pattern as the existing ones (list, detail, save, delete, bulk-save).

5. **Update README** — add the new shortcut to the typed resources examples and update the dedicated classes note.

## Pull Request Checklist

- [ ] Tests pass: `composer test`
- [ ] Pint passes: `vendor/bin/pint --test`
- [ ] PHPStan passes: `vendor/bin/phpstan`
- [ ] New feature has tests
- [ ] README and CHANGELOG updated (if applicable)

## Questions?

Open a [discussion](https://github.com/chrislorando/laravel-accurate/discussions) or [issue](https://github.com/chrislorando/laravel-accurate/issues).
