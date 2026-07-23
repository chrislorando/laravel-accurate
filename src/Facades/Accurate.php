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
 * @method \ChrisLorando\LaravelAccurate\Models\AccurateDatabase|null currentDatabase()
 * @method void switchDatabase(\ChrisLorando\LaravelAccurate\Models\AccurateDatabase $database)
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\Resource resource(string $name)
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\ItemResource items()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource itemCategories()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\UnitResource units()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\BankTransferResource bankTransfers()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\BranchResource branches()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\CurrencyResource currencies()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\DepartmentResource departments()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\TaxResource taxes()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\EmployeeResource employees()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\ExpenseResource expenses()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\FobResource fobs()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\OtherDepositResource otherDeposits()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\OtherPaymentResource otherPayments()
 * @method static \ChrisLorando\LaravelAccurate\Http\Resources\SalesQuotationResource salesQuotations()
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
