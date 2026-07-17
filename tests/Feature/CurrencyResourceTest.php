<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\CurrencyResource;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('ensureValid')->andReturnNull();
    $this->app->instance(TokenManager::class, $tokenManager);

    $this->connection = AccurateConnection::create([
        'name' => 'default',
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'token_type' => 'Bearer',
        'expires_at' => now()->addHour(),
        'scopes' => ['currency_view'],
    ]);

    $this->database = AccurateDatabase::create([
        'connection_id' => $this->connection->id,
        'database_id' => '123456',
        'alias' => 'Test Company',
        'company_name' => 'Test Company',
        'host' => 'https://zeus.accurate.id/accurate',
        'session_id' => 'test-session-id',
        'session_expires_at' => now()->addHours(2),
    ]);
});

// ─── CurrencyResource extra endpoints ────────────────────────────────

it('can get currency exchange rate', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['rate' => 16250, 'currencyCode' => 'USD', 'transDate' => '2026-07-18'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new CurrencyResource($api);
    $result = $resource->exchangeRate('USD', '2026-07-18');

    expect($result['d'])->toHaveKey('rate', 16250)
        ->and($result['d'])->toHaveKey('currencyCode', 'USD');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/currency/exchange-rate.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('currencyCode=USD');
    expect($container[0]['request']->getUri()->getQuery())->toContain('transDate=2026-07-18');
});

it('can get currency exchange rate without transDate', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['rate' => 16100, 'currencyCode' => 'USD'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new CurrencyResource($api);
    $resource->exchangeRate('USD');

    expect($container[0]['request']->getUri()->getQuery())->toContain('currencyCode=USD');
    expect($container[0]['request']->getUri()->getQuery())->not->toContain('transDate');
});

it('can get currency fiscal rate', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['rate' => 16100.50, 'currencyCode' => 'USD', 'transDate' => '2026-07-18'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new CurrencyResource($api);
    $result = $resource->fiscalRate('USD', '2026-07-18');

    expect($result['d'])->toHaveKey('rate', 16100.50)
        ->and($result['d'])->toHaveKey('currencyCode', 'USD');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/currency/fiscal-rate.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('currencyCode=USD');
    expect($container[0]['request']->getUri()->getQuery())->toContain('transDate=2026-07-18');
});

it('can get currency fiscal rate without transDate', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['rate' => 16100, 'currencyCode' => 'USD'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new CurrencyResource($api);
    $resource->fiscalRate('USD');

    expect($container[0]['request']->getUri()->getQuery())->toContain('currencyCode=USD');
    expect($container[0]['request']->getUri()->getQuery())->not->toContain('transDate');
});
