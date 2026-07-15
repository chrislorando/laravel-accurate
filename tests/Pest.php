<?php

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Tests\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

uses(TestCase::class)->in(__DIR__);

/**
 * Build an ApiClient with a mock Guzzle handler for isolated HTTP testing.
 */
function makeApiClient(array $responses, ?array &$historyContainer = null): ApiClient
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);

    if ($historyContainer !== null) {
        $stack->push(Middleware::history($historyContainer));
    }

    $mockHttp = new GuzzleClient(['handler' => $stack]);

    return new ApiClient(app(TokenManager::class), $mockHttp);
}
