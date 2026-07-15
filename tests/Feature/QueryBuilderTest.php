<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
use ChrisLorando\LaravelAccurate\Http\Resources\QueryBuilder;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
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

/**
 * Helper to create a fresh QueryBuilder instance for testing.
 */
function makeBuilder(): QueryBuilder
{
    $api = makeApiClient([])->for(
        AccurateConnection::first(),
        AccurateDatabase::first()
    );

    return new QueryBuilder(new ItemResource($api));
}

// ─── QueryBuilder::toParams() ────────────────────────────────────────

it('builds params for select', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->select('id', 'name', 'price')
        ->toParams();

    expect($params)->toHaveKey('fields', 'id,name,price');
});

it('builds params for where with two args (exact match)', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->where('name', 'test')
        ->toParams();

    expect($params)->toHaveKey('filter.name.op', 'EQUAL')
        ->and($params)->toHaveKey('filter.name.val', 'test');
});

it('builds params for where with three args (like)', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->where('name', 'like', 'test')
        ->toParams();

    expect($params)->toHaveKey('filter.name.op', 'CONTAIN')
        ->and($params)->toHaveKey('filter.name.val', 'test');
});

it('builds params for where with three args (operator)', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->where('price', '>', 100)
        ->toParams();

    expect($params)->toHaveKey('filter.price.op', 'GREATER_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');
});

it('builds params for multiple where clauses', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->where('name', 'like', 'test')
        ->where('price', '>', 100)
        ->toParams();

    expect($params)->toHaveKey('filter.name.op', 'CONTAIN')
        ->and($params)->toHaveKey('filter.name.val', 'test')
        ->and($params)->toHaveKey('filter.price.op', 'GREATER_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');
});

it('maps all shorthand operators to Accurate operators', function () {
    $builder = makeBuilder();

    // Comparison operators
    $params = $builder->where('price', '>', 100)->toParams();
    expect($params)->toHaveKey('filter.price.op', 'GREATER_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');

    $params = $builder->where('price', '>=', 100)->toParams();
    expect($params)->toHaveKey('filter.price.op', 'GREATER_EQUAL_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');

    $params = $builder->where('price', '<', 100)->toParams();
    expect($params)->toHaveKey('filter.price.op', 'LESS_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');

    $params = $builder->where('price', '<=', 100)->toParams();
    expect($params)->toHaveKey('filter.price.op', 'LESS_EQUAL_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');

    $params = $builder->where('name', '!=', 'test')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'NOT_EQUAL')
        ->and($params)->toHaveKey('filter.name.val', 'test');

    // Like / Contain
    $params = $builder->where('name', 'like', 'test')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'CONTAIN')
        ->and($params)->toHaveKey('filter.name.val', 'test');

    $params = $builder->where('name', 'contain', 'test')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'CONTAIN')
        ->and($params)->toHaveKey('filter.name.val', 'test');

    // Between / Not Between (array value → val[0], val[1])
    $params = $builder->where('price', 'between', [1, 100])->toParams();
    expect($params)->toHaveKey('filter.price.op', 'BETWEEN')
        ->and($params)->toHaveKey('filter.price.val[0]', '1')
        ->and($params)->toHaveKey('filter.price.val[1]', '100');

    $params = $builder->where('price', 'not_between', [1, 100])->toParams();
    expect($params)->toHaveKey('filter.price.op', 'NOT_BETWEEN')
        ->and($params)->toHaveKey('filter.price.val[0]', '1')
        ->and($params)->toHaveKey('filter.price.val[1]', '100');

    // Empty / Not Empty (no val)
    $params = $builder->where('name', 'empty')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'EMPTY')
        ->and($params)->not->toHaveKey('filter.name.val');

    $params = $builder->where('name', 'not_empty')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'NOT_EMPTY')
        ->and($params)->not->toHaveKey('filter.name.val');

    // Accurate-native operators pass through
    $params = $builder->where('name', 'EQUAL', 'test')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'EQUAL')
        ->and($params)->toHaveKey('filter.name.val', 'test');

    $params = $builder->where('name', 'CONTAIN', 'test')->toParams();
    expect($params)->toHaveKey('filter.name.op', 'CONTAIN')
        ->and($params)->toHaveKey('filter.name.val', 'test');

    $params = $builder->where('price', 'GREATER_EQUAL_THAN', 100)->toParams();
    expect($params)->toHaveKey('filter.price.op', 'GREATER_EQUAL_THAN')
        ->and($params)->toHaveKey('filter.price.val', '100');
});

it('builds params for orderBy', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder->orderBy('name')->toParams();
    expect($params)->toHaveKey('sp.sort', 'name|asc');

    $params = $builder->orderBy('price', 'desc')->toParams();
    expect($params)->toHaveKey('sp.sort', 'price|desc');
});

it('builds params for limit', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder->limit(10)->toParams();

    expect($params)->toHaveKey('sp.pageSize', '10');
});

it('builds params for page', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder->page(3)->toParams();

    expect($params)->toHaveKey('sp.page', '3');
});

it('builds combined params', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $params = $builder
        ->select('id', 'itemNo', 'name')
        ->where('itemType', 'INVENTORY')
        ->orderBy('name', 'asc')
        ->limit(10)
        ->page(2)
        ->toParams();

    expect($params)->toEqual([
        'fields' => 'id,itemNo,name',
        'filter.itemType.op' => 'EQUAL',
        'filter.itemType.val' => 'INVENTORY',
        'sp.sort' => 'name|asc',
        'sp.pageSize' => '10',
        'sp.page' => '2',
    ]);
});

it('returns empty params when nothing is set', function () {
    $api = makeApiClient([])->for($this->connection, $this->database);
    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    expect($builder->toParams())->toBeEmpty();
});

// ─── QueryBuilder::get() ─────────────────────────────────────────────

it('sends query params through the API', function () {
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
    $builder = new QueryBuilder($resource);

    $result = $builder
        ->select('id', 'name')
        ->where('name', 'like', 'test')
        ->orderBy('name')
        ->limit(10)
        ->page(1)
        ->get();

    expect($result['d'])->toHaveCount(2);

    $uri = $container[0]['request']->getUri();
    expect($uri->getPath())->toEndWith('/api/item/list.do');

    $queryString = $uri->getQuery();
    expect($queryString)->toContain('fields=id%2Cname')
        ->and($queryString)->toContain('filter.name.op=CONTAIN')
        ->and($queryString)->toContain('filter.name.val=test')
        ->and($queryString)->toContain('sp.sort=name%7Casc')
        ->and($queryString)->toContain('sp.pageSize=10')
        ->and($queryString)->toContain('sp.page=1');
});

// ─── QueryBuilder::first() ───────────────────────────────────────────

it('returns the first item', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'First Item'],
            ],
        ])),
    ])->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $result = $builder
        ->where('itemType', 'INVENTORY')
        ->first();

    expect($result)->toHaveKey('id', 1)
        ->and($result)->toHaveKey('name', 'First Item');
});

it('returns null when first() has no results', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [],
        ])),
    ])->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $result = $builder->first();

    expect($result)->toBeNull();
});

// ─── QueryBuilder::paginate() ───────────────────────────────────────

it('paginates with metadata', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'Item A'],
                ['id' => 2, 'name' => 'Item B'],
            ],
            'sp' => [
                'page' => 1,
                'pageSize' => 10,
                'total' => 42,
            ],
        ])),
    ])->for($this->connection, $this->database);

    $resource = new ItemResource($api);
    $builder = new QueryBuilder($resource);

    $result = $builder->limit(10)->page(1)->paginate();

    expect($result)->toHaveKey('data')
        ->and($result)->toHaveKey('sp')
        ->and($result['data'])->toHaveCount(2)
        ->and($result['sp'])->toHaveKey('total', 42)
        ->and($result['sp'])->toHaveKey('page', 1)
        ->and($result['sp'])->toHaveKey('pageSize', 10);
});

// ─── Resource::query() fluent ────────────────────────────────────────

it('can access query builder via resource method', function () {
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'name' => 'Test'],
            ],
        ])),
    ])->for($this->connection, $this->database);

    $resource = new ItemResource($api);

    $result = $resource->query()
        ->select('id', 'name')
        ->where('id', '>', 0)
        ->get();

    expect($result['d'])->toHaveCount(1);
});
