<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\Resources\SalesInvoiceResource;
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
        'scopes' => ['sales_invoice_view'],
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

// ─── SalesInvoiceResource extra endpoints ────────────────────────────

it('can create a down payment', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => ['id' => 1, 'number' => 'DP-001'],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesInvoiceResource($api);
    $result = $resource->createDownPayment([
        'customerNo' => 'CUST-001',
        'dpAmount' => 500000,
    ]);

    expect($result['d'])->toHaveKey('number', 'DP-001');

    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/sales-invoice/create-down-payment.do');
    expect($container[0]['request']->getMethod())->toBe('POST');

    $body = json_decode($container[0]['request']->getBody()->getContents(), true);
    expect($body)->toHaveKey('customerNo', 'CUST-001')
        ->and($body)->toHaveKey('dpAmount', 500000);
});

it('can get invoice detail by customer', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'number' => 'INV-001', 'customerNo' => 'CUST-001'],
            ],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesInvoiceResource($api);
    $result = $resource->detailInvoice('CUST-001');

    expect($result['s'])->toBeTrue();
    expect($container[0]['request']->getUri()->getPath())->toEndWith('/api/sales-invoice/detail-invoice.do');
    expect($container[0]['request']->getUri()->getQuery())->toContain('customerNo=CUST-001');
    expect($container[0]['request']->getMethod())->toBe('GET');
});

it('can get invoice detail with optional filters', function () {
    $container = [];
    $api = makeApiClient([
        new Response(200, [], json_encode([
            's' => true,
            'd' => [
                ['id' => 1, 'number' => 'INV-001', 'customerNo' => 'CUST-001'],
            ],
        ])),
    ], $container)->for($this->connection, $this->database);

    $resource = new SalesInvoiceResource($api);
    $resource->detailInvoice('CUST-001', [
        'fromDate' => '01/07/2026',
        'toDate' => '31/07/2026',
    ]);

    $query = $container[0]['request']->getUri()->getQuery();
    expect($query)->toContain('customerNo=CUST-001');
    expect($query)->toContain('fromDate=01%2F07%2F2026');
    expect($query)->toContain('toDate=31%2F07%2F2026');
});
