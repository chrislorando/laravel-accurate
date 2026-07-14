<?php

namespace ChrisLorando\LaravelAccurate\Facades;

use ChrisLorando\LaravelAccurate\LaravelAccurate;
use Illuminate\Support\Facades\Facade;

/**
 * @see LaravelAccurate
 */
class Accurate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelAccurate::class;
    }
}
