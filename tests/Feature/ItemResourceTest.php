<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
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
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'Item A'],
                ['id' => 2, 'name' => 'Item B'],
            ],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $result = $resource->list(['sp.pageSize' => 10]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveCount(2);

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/item/list.do');
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
