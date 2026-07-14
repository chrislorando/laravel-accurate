<?php

use ChrisLorando\LaravelAccurate\Facades\Accurate;

beforeEach(function () {
    config()->set('accurate.client_id', 'test-client-id');
    config()->set('accurate.client_secret', 'test-client-secret');
    config()->set('accurate.redirect_uri', 'http://localhost/callback');
    config()->set('accurate.base_url', 'https://account.accurate.id');
    config()->set('accurate.scopes', ['item_view']);
});

it('can generate oauth url', function () {
    expect(
        Accurate::oauth()->getAuthorizationUrl()
    )->toContain('/oauth/authorize');
});
