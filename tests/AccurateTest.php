<?php

use ChrisLorando\LaravelAccurate\Facades\Accurate;
use ChrisLorando\LaravelAccurate\LaravelAccurate;

it('can access accurate facade', function () {
    expect(Accurate::getFacadeRoot())
        ->toBeInstanceOf(LaravelAccurate::class);
});
