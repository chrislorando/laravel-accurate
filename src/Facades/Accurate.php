<?php

namespace ChrisLorando\LaravelAccurate\Facades;

use ChrisLorando\LaravelAccurate\LaravelAccurate;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string authorizationUrl(?string $state = null)
 * @method static self connection(string $name)
 * @method static array databases()
 * @method static self openDatabase(string $databaseId, ?string $alias = null)
 * @method static self on(string $databaseId)
 * @method static \ChrisLorando\LaravelAccurate\Models\AccurateDatabase|null currentDatabase()
 * @method static void switchDatabase(\ChrisLorando\LaravelAccurate\Models\AccurateDatabase $database)
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\Resource resource(string $name)
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\ItemResource items()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource itemCategories()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\UnitResource units()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\WarehouseResource warehouses()
 * @method static array get(string $endpoint, array $params = [])
 *
 * @see LaravelAccurate
 */
class Accurate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelAccurate::class;
    }
}
