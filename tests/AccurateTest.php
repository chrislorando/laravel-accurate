<?php

use ChrisLorando\LaravelAccurate\Facades\Accurate;

it('can access accurate facade', function () {
    expect(Accurate::test())
        ->toBe('Laravel Accurate is working');
});
