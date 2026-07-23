<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\SalesOrderResource;
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
        'scopes' => ['sales_order_view'],
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

// ─── SalesOrderResource extra endpoints ───────────────────────────────

it('can manually close a sales order by default', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['status' => 'CLOSED', 'number' => 'SO-001'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesOrderResource($api);
    $result = $resource->manualCloseOrder('SO-001');

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveKey('number', 'SO-001');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/sales-order/manual-close-order.do');
    expect($container[0]['request']->getMethod())->toBe('POST');

    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body)->toHaveKey('number', 'SO-001')
        ->and($body)->toHaveKey('orderClosed', true);
});

it('can manually close a sales order with explicit orderClosed flag', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['status' => 'CLOSED', 'number' => 'SO-002'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesOrderResource($api);
    $resource->manualCloseOrder('SO-002', true);

    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body)->toHaveKey('number', 'SO-002')
        ->and($body)->toHaveKey('orderClosed', true);
});

it('can reopen a sales order by setting orderClosed to false', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['status' => 'OPEN', 'number' => 'SO-003'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesOrderResource($api);
    $resource->manualCloseOrder('SO-003', false);

    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body)->toHaveKey('number', 'SO-003')
        ->and($body)->toHaveKey('orderClosed', false);
});
