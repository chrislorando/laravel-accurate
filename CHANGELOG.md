# Changelog

All notable changes to `laravel-accurate` will be documented in this file.

## v0.7.0 - 2026-07-20

### Added

- **`OtherPaymentResource`** — dedicated resource for `/api/other-payment` endpoint. `Accurate::otherPayments()` convenience method.
- **`OtherDepositResource`** — dedicated resource for `/api/other-deposit` endpoint. `Accurate::otherDeposits()` convenience method.
- **`BankTransferResource`** — dedicated resource for `/api/bank-transfer` endpoint. `Accurate::bankTransfers()` convenience method.

## v0.6.0 - 2026-07-18

### Added

- **8 new dedicated resource classes** — `BranchResource`, `CurrencyResource`, `DepartmentResource`, `EmployeeResource`, `ExpenseResource`, `FobResource`, `PaymentTermResource`, `TaxResource`. Each wired in `LaravelAccurate.php` with a convenience method (`branches()`, `currencies()`, `departments()`, `employees()`, `expenses()`, `fobs()`, `paymentTerms()`, `taxes()`).
- **`CurrencyResource` extra endpoints** — `exchangeRate(currencyCode, ?transDate)` and `fiscalRate(currencyCode, ?transDate)` for `/api/currency/exchange-rate.do` and `/api/currency/fiscal-rate.do`.

### Changed

- **Test strategy** — only resources with custom logic beyond base CRUD get dedicated test files. Redundant per-resource CRUD tests removed.
- **Test cleanup** — deleted `ItemCategoryResourceTest`, `UnitResourceTest`, `WarehouseResourceTest` (no custom logic). Added `CurrencyResourceTest` (extra endpoints). `ItemResourceTest` retained (7 custom methods).

## v0.5.1 - 2026-07-17

### Changed

- **Session handling moved from model to `LaravelAccurate`** — `currentDatabase()` and `switchDatabase()` are now instance methods on `LaravelAccurate` instead of static methods on the `AccurateDatabase` Eloquent model. The model no longer depends on HTTP session, making it usable in CLI/queue contexts without side effects.

## v0.4.1 - 2026-07-16

### Added

- **`CONTRIBUTING.md`** — development setup, project structure, and step-by-step guide for adding new dedicated resources.

### Changed

- **README** — added Contributing section with link to `CONTRIBUTING.md`.
- **CONTRIBUTING.md** — removed empty `Data/` directory from project structure.

## v0.4.0 - 2026-07-16

### Added

- **`UnitResource`** — dedicated class for `/api/unit` endpoint. `Accurate::units()` facade shortcut with full CRUD + bulk-save + query builder support.
- **`Accurate::put()`** test coverage — raw PUT method test added to `LaravelAccurateTest`.

### Changed

- **Test split** — `ResourceTest.php` split into `ItemResourceTest`, `ItemCategoryResourceTest`, `UnitResourceTest` (one per resource). Generic resource fallback tests remain in `LaravelAccurateTest`.
- **README** — added `units()` and `itemCategories()` to typed resources examples, added `put()` to raw endpoints, updated dedicated classes note.

## v0.3.3 - 2026-07-16

### Changed

- **README docs** — added sample OAuth routes (`accurate/connect`, `accurate/callback`, `accurate/databases`). Expanded `on()` examples with `get()`, `post()`, `resource()`, and query builder.

## v0.3.2 - 2026-07-16

### Fixed

- **CI: `ApiClientTest` mock key mismatch** — `openDatabase()` mock returned `session_id` but code reads `session`. Fixed mock return + added missing `accessibleUntil` key.

## v0.3.0 - 2026-07-16

### Added

- **`on()` method** — resolve a previously-opened database from the local `accurate_databases` table with zero HTTP overhead. Ideal for background jobs, CLI commands, and fast multi-DB switching within a single request. Accepts `database_id` or `alias`.
- **Generic `resource()` fallback** — `Accurate::resource('customer')`, `Accurate::resource('sales-invoice')`, etc. now work for _any_ resource name without needing a dedicated class. Returns an anonymous `Resource` instance with full CRUD + query builder support.
- **Facade shortcuts** — `Accurate::items()` (`ItemResource`) and `Accurate::itemCategories()` (`ItemCategoryResource`) for typed access.
- **Session auto-resolution** — `ensureConnection()` and `ensureDatabase()` automatically resolve connection + database from the session when no explicit `connection()` call is made. `Accurate::items()->list()` now works directly after `openDatabase()` without repeating the chain.
- **Test coverage** — 19 new tests in `LaravelAccurateTest.php` covering `on()` (by id/alias, headers, session isolation, not-found), `resource()` (dedicated + generic fallback, list/detail/save/delete/query), facade shortcuts, raw endpoint methods, and `ensureConnection()` auto-resolution.

### Changed

- `openDatabase()` now returns the full Accurate response array (including `accessibleUntil`, `host`, `session`, etc.) instead of a filtered subset. The raw response is accessible via `$accurate->toArray()`.
- `AccountClient::openDatabase()` passes through the full `$data` from Accurate instead of filtering to `host` + `session_id`.
- README comprehensively rewritten with 4 distinct calling styles, step-by-step workflow, and a dedicated Query Builder reference section.

### Fixed

- Mock keys in `ResourceTest.php` (`session_id` → `session`) to match the updated `openDatabase()` response format.
- Replaced obsolete "throws for unknown resource" test with "resolves generic resource" test.

## v0.2.4 - 2026-07-15

### Added

- **Session-based active database tracking** — `AccurateDatabase::current()` reads the active database from session (`accurate_active_database_id`), falling back to `is_default`. `AccurateDatabase::switchTo()` persists the active database to session.
- **Facade proxy methods** — `Accurate::currentDatabase()` and `Accurate::switchDatabase()` for ergonomic access without needing to know the model class.
- `openDatabase()` now automatically calls `switchTo()` to persist the opened database to session.

## v0.2.3 - 2026-07-15

### Removed

- **`client_id` & `client_secret`** from `accurate_connections` table. OAuth credentials are now read exclusively from config/env (`ACCURATE_CLIENT_ID`, `ACCURATE_CLIENT_SECRET`), not duplicated in the database.

## v0.2.2 - 2026-07-15

### Fixed

- **PHPStan CI failures:**
    - `ApiClient::for()` no longer recreates `GuzzleClient` internally, removing the deprecated `Client::getConfig()` call (slated for removal in `guzzlehttp/guzzle:8.0`). Connection-specific options (`base_uri`, `timeout`, `verify`, `headers`) are now resolved per-request inside `ApiClient::request()`.
    - `LaravelAccurate::items()` / `itemCategories()` no longer delegate to `resource()` (which returns the parent `Resource` type). They now instantiate `ItemResource` / `ItemCategoryResource` directly, satisfying the declared return type variance.

## v0.2.1 - 2026-07-15

### Added

- `ItemCategoryResource` — `item-category` resource with 5 endpoints (list, detail, save, delete, bulk-save)
- Facade shortcut `Accurate::itemCategories()` → `ItemCategoryResource`
- Sample routes for item-category CRUD in test-app (`routes/web.php`)
- Test coverage for `ItemCategoryResource` (7 test cases)

### Fixed

- **`Resource::bulkSave()`** — payload format bug. Guzzle was encoding `[['name'=>'X']]` as `0[name]=X`, but Accurate requires `data[0][name]=X`. Now sends a JSON body `{ "data": [...] }` via new `ApiClient::postJson()`. Affected ALL resources using `bulkSave()`.
- **`ApiClient::delete()`** — was sending `id` in form_params body, which Accurate ignores. Now sends `id` in the query string (same as `get()`). Affected ALL resources using `delete()`.

### Changed

- Reorganized Resource classes into `src/Http/Resources/` subfolder (namespace `ChrisLorando\LaravelAccurate\Http\Resources`)
- `LaravelAccurate::resource()` accepts `item-category` in addition to `item`

## v0.2.0 - 2026-07-15

### Added

- Resource engine — abstract base `Resource` class with `list`, `detail`, `save`, `delete`, `bulkSave`, `query`
- `ItemResource` with 12 endpoints (4 CRUD + 8 item-specific):
    - `bulkSave()` — generic bulk-save inherited from `Resource`
    - `getNearestCost(itemNo, ?transDate)` → `item/get-nearest-cost.do`
    - `getStock(no, ?warehouseName)` → `item/get-stock.do`
    - `listStock(array)` → `item/list-stock.do`
    - `searchByItemOrSn(keywords, ?params)` → `item/search-by-item-or-sn.do`
    - `searchByNoUpc(keywords)` → `item/search-by-no-upc.do`
    - `stockMutationHistory(array)` → `item/stock-mutation-history.do`
    - `vendorPrice(array)` → `item/vendor-price.do`
- QueryBuilder — fluent chain: `select()`, `where()`, `orderBy()`, `limit()`, `page()`, `get()`, `first()`, `paginate()`
- Shorthand operator mapping (`>` → `GREATER_THAN`, `like` → `CONTAIN`, `between` → `BETWEEN`, etc.)

### Changed

- Updated README with full Resource API, Query Builder, and operator mapping examples

## v0.1.3 - 2026-07-15

### Changed

- Added callback route setup documentation
- Removed unused routes config
- Added scope API reference in README

## v0.1.2 - 2026-07-15

### Fixed

- Renamed `Oauth` to `OAuth` for case-sensitive CI compatibility
- Fixed tests for case-sensitive filesystems
- Fixed Pint auto-commit workflow

## v0.1.1 - 2026-07-15

### Changed

- Finalized README documentation
- Added model `@property` PHPDoc hints
- Fixed PHPStan errors

## v0.1.0 - 2026-07-15

### Added

- Initial release
- OAuth authentication flow
- Database connection setup
