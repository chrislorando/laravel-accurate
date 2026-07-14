<?php

use ChrisLorando\LaravelAccurate\Facades\Accurate;

it('can generate oauth url', function () {
    expect(
        Accurate::oauth()->getAuthorizationUrl()
    )->toContain('/oauth/authorize');
});