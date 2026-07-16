<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\WarehouseResource;
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
        'scopes' => ['warehouse_view'],
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

// ─── WarehouseResource direct tests ──────────────────────────────────

it('can list warehouses via WarehouseResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'warehouseName' => 'Gudang Utama'],
                ['id' => 2, 'warehouseName' => 'Gudang Cabang'],
            ],
            'sp' => ['page' => 1, 'pageSize' => 20, 'totalPage' => 1, 'totalCount' => 2],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new WarehouseResource($api);
    $result = $resource->list(['sp.pageSize' => 20]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveCount(2)
        ->and($result['d'][0])->toHaveKey('warehouseName', 'Gudang Utama');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/warehouse/list.do');
});

it('can get warehouse detail via WarehouseResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 1, 'warehouseName' => 'Gudang Utama'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new WarehouseResource($api);
    $result = $resource->detail('1');

    expect($result['d'])->toHaveKey('warehouseName', 'Gudang Utama');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/warehouse/detail.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=1');
});

it('can save a warehouse via WarehouseResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 10, 'warehouseName' => 'Gudang Baru'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new WarehouseResource($api);
    $result = $resource->save(['warehouseName' => 'Gudang Baru']);

    expect($result['d'])->toHaveKey('id', 10);
    expect($container[0]['request']->getMethod())->toBe('POST');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/warehouse/save.do');
});

it('can delete a warehouse via WarehouseResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => 'Warehouse deleted successfully',
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new WarehouseResource($api);
    $result = $resource->delete('42');

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getMethod())->toBe('DELETE');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/warehouse/delete.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=42');
});
