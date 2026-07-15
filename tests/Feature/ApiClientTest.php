<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Exceptions\AccurateApiException;
use ChrisLorando\LaravelAccurate\Facades\Accurate;
use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    // TokenManager mock — skip actual token refresh
    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('ensureValid')->andReturnNull();
    $this->app->instance(TokenManager::class, $tokenManager);

    // Create a test connection in DB
    $this->connection = AccurateConnection::create([
        'name' => 'default',
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'token_type' => 'Bearer',
        'expires_at' => now()->addHour(),
        'scopes' => ['item_view'],
    ]);

    // Create a test database session in DB
    $this->database = AccurateDatabase::create([
        'connection_id' => $this->connection->id,
        'database_id' => '123456',
        'alias' => 'Test Company',
        'company_name' => 'Test Company',
        'host' => 'https://zeus.accurate.id/accurate',
        'session_id' => 'test-session-id',
        'session_expires_at' => now()->addHours(2),
    ]);

    $this->tokenManager = $tokenManager;
});

// ─── Direct ApiClient Tests ──────────────────────────────────────────

it('can fetch item/list.do via ApiClient directly', function () {
    $api = makeApiClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'itemNo' => 'ITEM-001', 'name' => 'Test Item A', 'itemType' => 'INVENTORY', 'unitPrice' => 15000],
                ['id' => 2, 'itemNo' => 'ITEM-002', 'name' => 'Test Item B', 'itemType' => 'SERVICE', 'unitPrice' => 50000],
            ],
            'sp' => ['page' => 1, 'pageSize' => 20, 'total' => 2],
        ])),
    ]);

    $result = $api
        ->for($this->connection, $this->database)
        ->get('item/list.do');

    expect($result)
        ->toBeArray()
        ->toHaveKey('s', true)
        ->toHaveKey('d')
        ->toHaveKey('sp');

    expect($result['d'])->toBeArray()->toHaveCount(2);
    expect($result['d'][0])->toHaveKey('itemNo', 'ITEM-001');
    expect($result['sp'])->toMatchArray([
        'page' => 1,
        'pageSize' => 20,
        'total' => 2,
    ]);
});

it('sends correct auth headers', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ], $container);

    $api->for($this->connection, $this->database)
        ->get('item/list.do');

    expect($container)->toHaveCount(1);
    $request = $container[0]['request'];

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())->toEndWith('/item/list.do');
    expect($request->getHeaderLine('Authorization'))->toBe('Bearer test-access-token');
    expect($request->getHeaderLine('X-Session-ID'))->toBe('test-session-id');
});

it('sends query parameters in GET requests', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ], $container);

    $api->for($this->connection, $this->database)
        ->get('item/list.do', [
            'sp.page' => 1,
            'sp.pageSize' => 10,
            'sp.sort' => 'name|asc',
            'filter.itemType.op' => 'EQUAL',
            'filter.itemType.val' => 'INVENTORY',
        ]);

    $request = $container[0]['request'];
    $queryString = $request->getUri()->getQuery();

    expect($queryString)->toContain('sp.page=1');
    expect($queryString)->toContain('sp.pageSize=10');
    expect($queryString)->toContain('sp.sort=name%7Casc');
    expect($queryString)->toContain('filter.itemType.op=EQUAL');
    expect($queryString)->toContain('filter.itemType.val=INVENTORY');
});

it('can post to item/save.do', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 99, 'itemNo' => 'ITEM-NEW', 'name' => 'New Item'],
        ])),
    ]);

    $result = $api->for($this->connection, $this->database)
        ->post('item/save.do', [
            'itemType' => 'INVENTORY',
            'name' => 'New Item',
            'unit1Name' => 'Pcs',
        ]);

    expect($result)
        ->toBeArray()
        ->toHaveKey('s', true)
        ->and($result['d'])
        ->toHaveKey('itemNo', 'ITEM-NEW');
});

it('throws AccurateApiException on 422 client error', function () {
    $api = makeApiClient([
        new Response(422, [], json_encode([
            's' => false,
            'error' => ['message' => 'Item with this number already exists'],
        ])),
    ]);

    $api->for($this->connection, $this->database)
        ->post('item/save.do', ['itemType' => 'INVENTORY', 'name' => 'Duplicate']);
})->throws(AccurateApiException::class, 'Item with this number already exists');

it('throws AccurateApiException on 500 server error', function () {
    $api = makeApiClient([
        new Response(500, [], json_encode(['s' => false, 'error' => 'Internal Server Error'])),
    ]);

    $api->for($this->connection, $this->database)
        ->post('item/save.do', []);
})->throws(AccurateApiException::class, 'Accurate API server error');

// ─── Full Chain Test (Facade) ──────────────────────────────────────────

it('can fetch item/list.do through the full Accurate facade chain', function () {
    // Mock AccountClient::openDatabase to avoid real HTTP call
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')
        ->andReturn([
            'host' => 'https://zeus.accurate.id',
            'session_id' => 'test-session-id',
        ]);
    $this->app->instance(AccountClient::class, $accountClient);

    // Mock ApiClient with a successful item/list response
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'itemNo' => 'ITEM-001', 'name' => 'Test Item'],
            ],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->get('item/list.do');

    expect($result)
        ->toBeArray()
        ->toHaveKey('s', true)
        ->and($result['d'][0])
        ->toHaveKey('itemNo', 'ITEM-001');
});
