# Changelog

All notable changes to `laravel-accurate` will be documented in this file.

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
