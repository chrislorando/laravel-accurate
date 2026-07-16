<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\UnitResource;
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

// ─── UnitResource direct tests ────────────────────────────────────────

it('can list units via UnitResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'unitName' => 'Pcs'],
                ['id' => 2, 'unitName' => 'Box'],
            ],
            'sp' => ['page' => 1, 'pageSize' => 20, 'totalPage' => 1, 'totalCount' => 2],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new UnitResource($api);
    $result = $resource->list(['sp.pageSize' => 20]);

    expect($result)->toHaveKey('s', true)
        ->and($result['d'])->toHaveCount(2)
        ->and($result['d'][0])->toHaveKey('unitName', 'Pcs');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/unit/list.do');
});

it('can get unit detail via UnitResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 1, 'unitName' => 'Pcs'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new UnitResource($api);
    $result = $resource->detail('1');

    expect($result['d'])->toHaveKey('unitName', 'Pcs');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/unit/detail.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=1');
});

it('can save a unit via UnitResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 10, 'unitName' => 'Liter'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new UnitResource($api);
    $result = $resource->save(['unitName' => 'Liter']);

    expect($result['d'])->toHaveKey('id', 10);
    expect($container[0]['request']->getMethod())->toBe('POST');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/unit/save.do');
});

it('can delete a unit via UnitResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => 'Unit deleted successfully',
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new UnitResource($api);
    $result = $resource->delete('42');

    expect($result)->toHaveKey('s', true);
    expect($container[0]['request']->getMethod())->toBe('DELETE');
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/unit/delete.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('id=42');
});

it('can bulk-save units via UnitResource', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 12, 'unitName' => 'Kg'],
                ['id' => 13, 'unitName' => 'Gram'],
            ],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new UnitResource($api);
    $result = $resource->bulkSave([
        ['unitName' => 'Kg'],
        ['unitName' => 'Gram'],
    ]);

    expect($result['d'])->toHaveCount(2);
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/unit/bulk-save.do');

    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body['data'])->toHaveCount(2)
        ->and($body['data'][0])->toHaveKey('unitName', 'Kg');
});
