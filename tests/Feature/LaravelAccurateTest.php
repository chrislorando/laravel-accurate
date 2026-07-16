<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Facades\Accurate;
use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
use ChrisLorando\LaravelAccurate\Http\Resources\Resource;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        'scopes' => ['item_view'],
    ]);

    $this->database = AccurateDatabase::create([
        'connection_id' => $this->connection->id,
        'database_id' => '123456',
        'alias' => 'Test Company',
        'company_name' => 'Test Company',
        'host' => 'https://zeus.accurate.id/accurate/',
        'session_id' => 'test-session-id',
        'session_expires_at' => now()->addHours(2),
    ]);
});

// ─── on() — database resolution from local table ────────────────────

it('resolves database by database_id with on()', function () {
    // Mock AccountClient — not called, proving no HTTP
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldNotReceive('openDatabase');
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'From on()']],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->on('123456')
        ->items()
        ->list();

    expect($result)->toHaveKey('s', true)
        ->and($result['d'][0])->toHaveKey('name', 'From on()');
});

it('resolves database by alias with on()', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldNotReceive('openDatabase');
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'Alias Match']],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->on('Test Company')  // ← alias, not database_id
        ->items()
        ->list();

    expect($result['d'][0])->toHaveKey('name', 'Alias Match');
});

it('on() throws when database not found', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldNotReceive('openDatabase');
    $this->app->instance(AccountClient::class, $accountClient);

    Accurate::connection('default')->on('non-existent');
})->throws(ModelNotFoundException::class);

it('on() does not switch session', function () {
    // First set session to a known database
    session(['accurate_active_database_id' => 999]);

    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldNotReceive('openDatabase');
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')->on('123456')->items()->list();

    // Session must remain unchanged
    expect(session('accurate_active_database_id'))->toBe(999);
});

it('on() still sends correct auth and session headers', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldNotReceive('openDatabase');
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')
        ->on('123456')
        ->get('item/list.do');

    expect($container)->toHaveCount(1);
    $request = $container[0]['request'];
    expect($request->getHeaderLine('Authorization'))->toBe('Bearer test-access-token');
    expect($request->getHeaderLine('X-Session-ID'))->toBe('test-session-id');
});

// ─── resource() — generic fallback ────────────────────────────────────

it('resource() resolves dedicated classes for item and item-category', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')->openDatabase('123456');

    expect(Accurate::resource('item'))->toBeInstanceOf(ItemResource::class);
    expect(Accurate::resource('item-category'))->toBeInstanceOf(ItemCategoryResource::class);
});

it('resource() returns generic Resource for any other name', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')->openDatabase('123456');

    $resource = Accurate::resource('customer');
    expect($resource)->toBeInstanceOf(Resource::class);
    expect($resource)->not->toBeInstanceOf(ItemResource::class);
    expect($resource)->not->toBeInstanceOf(ItemCategoryResource::class);
});

it('generic resource() list hits correct endpoint', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'PT ABC']],
        ])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('customer')
        ->list(['sp.pageSize' => 10]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'][0])->toHaveKey('name', 'PT ABC');

    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/customer/list.do');
});

it('generic resource() detail hits correct endpoint', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 99, 'number' => 'INV-001'],
        ])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('sales-invoice')
        ->detail('99');

    expect($result['d'])->toHaveKey('number', 'INV-001');
    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/sales-invoice/detail.do');
});

it('generic resource() save hits correct endpoint', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 50, 'name' => 'PT XYZ'],
        ])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('customer')
        ->save(['name' => 'PT XYZ']);

    expect($result['d'])->toHaveKey('id', 50);
    expect($container[0]['request']->getMethod())->toBe('POST');
    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/customer/save.do');
});

it('generic resource() delete hits correct endpoint', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => 'Deleted'])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('customer')
        ->delete('42');

    expect($container[0]['request']->getMethod())->toBe('DELETE');
    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/customer/delete.do');
});

it('generic resource() supports query builder', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'PT ABC', 'email' => 'a@test.com']],
        ])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('customer')
        ->query()
        ->select('id', 'name', 'email')
        ->where('keywords', 'like', 'PT')
        ->limit(10)
        ->get();

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/customer/list.do');
});

// ─── Facade shortcuts ─────────────────────────────────────────────────

it('items() returns ItemResource', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')->openDatabase('123456');

    expect(Accurate::items())->toBeInstanceOf(ItemResource::class);
});

it('itemCategories() returns ItemCategoryResource', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => []])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')->openDatabase('123456');

    expect(Accurate::itemCategories())->toBeInstanceOf(ItemCategoryResource::class);
});

// ─── Raw endpoint methods ─────────────────────────────────────────────

it('Accurate::get() hits correct endpoint via facade', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'Raw Item']],
        ])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->get('api/item/list.do', ['sp.pageSize' => '5']);

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getUri()->getPath())
        ->toEndWith('/api/item/list.do');
});

it('Accurate::post() sends POST request', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => ['id' => 100]])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')
        ->openDatabase('123456')
        ->post('api/item/save.do', ['name' => 'New Item']);

    expect($container[0]['request']->getMethod())->toBe('POST');
});

it('Accurate::delete() sends DELETE request', function () {
    $container = [];
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session' => 'test-session-id',
        'accessibleUntil' => '20/08/2026',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode(['s' => true, 'd' => 'Deleted'])),
    ], $container);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')
        ->openDatabase('123456')
        ->delete('api/item/delete.do', ['id' => '50']);

    expect($container[0]['request']->getMethod())->toBe('DELETE');
});

// ─── ensureConnection() auto-resolve ──────────────────────────────────

it('ensureConnection() auto-resolves from session', function () {
    // Set session to point at our test database
    session(['accurate_active_database_id' => $this->database->id]);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'Auto-resolved']],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    // No connection() call — should auto-resolve from session
    $result = Accurate::items()->list();

    expect($result)->toHaveKey('s', true)
        ->and($result['d'][0])->toHaveKey('name', 'Auto-resolved');
});

it('ensureConnection() throws when no session and no explicit connection', function () {
    // Ensure no session
    session()->forget('accurate_active_database_id');

    // Delete all databases
    AccurateDatabase::query()->delete();

    Accurate::items()->list();
})->throws(RuntimeException::class, 'No Accurate connection selected');
