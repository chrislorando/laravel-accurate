<?php

namespace ChrisLorando\LaravelAccurate\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChrisLorando\LaravelAccurate\LaravelAccurate
 */
class Accurate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
         return \ChrisLorando\LaravelAccurate\LaravelAccurate::class;
    }
}
