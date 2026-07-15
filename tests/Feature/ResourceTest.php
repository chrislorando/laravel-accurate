<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Facades\Accurate;
use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
use ChrisLorando\LaravelAccurate\Http\Resources\Resource;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('ensureValid')->andReturnNull();
    $this->app->instance(TokenManager::class, $tokenManager);

    $this->connection = AccurateConnection::create([
        'name' => 'default',
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
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
        'host' => 'https://zeus.accurate.id/accurate',
        'session_id' => 'test-session-id',
        'session_expires_at' => now()->addHours(2),
    ]);
});

// ─── ItemResource direct tests ────────────────────────────────────────

it('can list items via ItemResource', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'Item A'],
                ['id' => 2, 'name' => 'Item B'],
            ],
        ])),
    ])->for($this->connection, $this->database);

    $resource = new ItemResource($api);

    $result = $resource->list(['sp.pageSize' => 10]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveCount(2);
});

it('can get item detail via ItemResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 1, 'itemNo' => 'ITEM-001', 'name' => 'Test Item'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $result = $resource->detail('1');

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveKey('itemNo', 'ITEM-001');

    // Verify the correct endpoint was called
    $request = $container[0]['request'];
    expect($request->getUri()->getPath())->toEndWith('/api/item/detail.do');
    expect($request->getUri()->getQuery())->toContain('id=1');
});

it('can save an item via ItemResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 99, 'name' => 'Saved Item'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $result = $resource->save([
        'itemType' => 'INVENTORY',
        'name' => 'Saved Item',
    ]);

    expect($result['d'])->toHaveKey('id', 99);
    expect($container[0]['request']->getMethod())->toBe('POST');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item/save.do');
});

it('can delete an item via ItemResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => 'Item deleted successfully',
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $result = $resource->delete('42');

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getMethod())->toBe('DELETE');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item/delete.do');
});

// ─── LaravelAccurate::items() / ::resource() ───────────────────────────

it('can access ItemResource through the facade helper', function () {
    // Mock AccountClient so openDatabase doesn't make real HTTP call
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session_id' => 'test-session-id',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    // Mock ApiClient
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'Facade Item']],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->items()
        ->list();

    expect($result)->toHaveKey('s', true)
        ->and($result['d'][0])->toHaveKey('name', 'Facade Item');
});

it('throws for unknown resource type', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session_id' => 'test-session-id',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    // Mock ApiClient (will not be called)
    $api = makeApiClient([]);
    $this->app->instance(ApiClient::class, $api);

    Accurate::connection('default')
        ->openDatabase('123456')
        ->resource('unknown');
})->throws(\InvalidArgumentException::class, 'Unknown Accurate resource');

// ─── ItemCategoryResource tests ────────────────────────────────────────

it('can list item categories via ItemCategoryResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'Elektronik', 'parentName' => null, 'defaultCategory' => false],
                ['id' => 2, 'name' => 'Smartphone', 'parentName' => 'Elektronik', 'defaultCategory' => false],
            ],
            'sp' => ['page' => 1, 'pageSize' => 20, 'totalPage' => 1, 'totalCount' => 2],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);

    $result = $resource->list(['sp.pageSize' => 20]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveCount(2)
        ->and($result['d'][0])->toHaveKey('name', 'Elektronik');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item-category/list.do');
});

it('can get item category detail via ItemCategoryResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 1, 'name' => 'Elektronik', 'parentName' => null, 'defaultCategory' => false],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);
    $result = $resource->detail('1');

    expect($result['d'])->toHaveKey('name', 'Elektronik');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item-category/detail.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=1');
});

it('can save an item category via ItemCategoryResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 10, 'name' => 'Elektronik'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);
    $result = $resource->save([
        'name' => 'Elektronik',
        'defaultCategory' => false,
    ]);

    expect($result['d'])->toHaveKey('id', 10);
    expect($container[0]['request']->getMethod())->toBe('POST');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item-category/save.do');
});

it('can create a child item category with parentName', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 11, 'name' => 'Smartphone', 'parentName' => 'Elektronik'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);
    $result = $resource->save([
        'name' => 'Smartphone',
        'parentName' => 'Elektronik',
    ]);

    expect($result['d'])->toHaveKey('parentName', 'Elektronik');

    // POST uses form_params (urlencoded), parse accordingly
    parse_str($container[0]['request']->getBody()->getContents(), $body);
    expect($body)->toHaveKey('name', 'Smartphone')
        ->and($body)->toHaveKey('parentName', 'Elektronik');
});

it('can bulk-save item categories via ItemCategoryResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 12, 'name' => 'Kategori A'],
                ['id' => 13, 'name' => 'Kategori B'],
            ],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);
    $result = $resource->bulkSave([
        ['name' => 'Kategori A'],
        ['name' => 'Kategori B', 'parentName' => 'Kategori A'],
    ]);

    expect($result['d'])->toHaveCount(2);
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item-category/bulk-save.do');

    // Verify JSON body with data[] array format expected by Accurate
    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body['data'])->toHaveCount(2)
        ->and($body['data'][1])->toHaveKey('parentName', 'Kategori A');
});

it('can delete an item category via ItemCategoryResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => 'Item category deleted successfully',
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemCategoryResource($api);
    $result = $resource->delete('42');

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getMethod())->toBe('DELETE');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item-category/delete.do');

    // DELETE sends id via query string (Accurate reads id from URL)
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=42');
});

it('can access ItemCategoryResource through the facade helper', function () {
    $accountClient = Mockery::mock(AccountClient::class);
    $accountClient->shouldReceive('openDatabase')->andReturn([
        'host' => 'https://zeus.accurate.id',
        'session_id' => 'test-session-id',
    ]);
    $this->app->instance(AccountClient::class, $accountClient);

    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [['id' => 1, 'name' => 'Facade Category']],
        ])),
    ]);
    $this->app->instance(ApiClient::class, $api);

    $result = Accurate::connection('default')
        ->openDatabase('123456')
        ->itemCategories()
        ->list();

    expect($result)->toHaveKey('s', true)
        ->and($result['d'][0])->toHaveKey('name', 'Facade Category');
});
