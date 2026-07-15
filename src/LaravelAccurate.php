<?php

namespace ChrisLorando\LaravelAccurate;

use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
use ChrisLorando\LaravelAccurate\Http\Resources\Resource;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use ChrisLorando\LaravelAccurate\OAuth\OAuthClient;

class LaravelAccurate
{
    protected ?AccurateConnection $connection = null;

    protected ?AccurateDatabase $database = null;

    public function __construct(
        protected OAuthClient $oauth,
        protected AccountClient $account,
        protected ApiClient $api,
    ) {}

    public function authorizationUrl(?string $state = null): string
    {
        return $this->oauth->getAuthorizationUrl($state);
    }

    public function connection(string $name): self
    {
        $this->connection = AccurateConnection::where('name', $name)
            ->firstOrFail();

        return $this;
    }

    public function databases(): array
    {
        $this->ensureConnection();

        return $this->account->databaseList($this->connection);
    }

    public function openDatabase(string $databaseId, ?string $alias = null): self
    {
        $this->ensureConnection();

        $result = $this->account->openDatabase(
            $this->connection,
            $databaseId
        );

        // The Accurate host URL does not include the API path prefix.
        // All business API endpoints are under /accurate/api/.
        $host = rtrim($result['host'], '/').'/accurate/';

        $this->database = AccurateDatabase::updateOrCreate(
            [
                'connection_id' => $this->connection->id,
                'database_id' => $databaseId,
            ],
            [
                'alias' => $alias ?? $databaseId,
                'company_name' => $alias ?? $databaseId,
                'host' => $host,
                'session_id' => $result['session_id'],
                'session_expires_at' => now()->addHours(2),
            ]
        );

        return $this;
    }

    protected function ensureConnection(): void
    {
        if (! $this->connection) {
            throw new \RuntimeException(
                'No Accurate connection selected. Call connection() first.'
            );
        }
    }

    protected function ensureDatabase(): void
    {
        $this->ensureConnection();

        if (! $this->database) {
            throw new \RuntimeException(
                'No Accurate database opened. Call openDatabase() first.'
            );
        }
    }

    protected function client(): ApiClient
    {
        $this->ensureDatabase();

        return $this->api->for($this->connection, $this->database);
    }

    /**
     * Get a typed resource for the given Accurate API resource name.
     *
     * @throws \InvalidArgumentException
     */
    public function resource(string $name): Resource
    {
        $api = $this->client();

        return match ($name) {
            'item' => new ItemResource($api),
            'item-category' => new ItemCategoryResource($api),
            default => throw new \InvalidArgumentException(
                "Unknown Accurate resource: [{$name}]."
            ),
        };
    }

    /**
     * Convenience shortcut for ->resource('item').
     */
    public function items(): ItemResource
    {
        return $this->resource('item');
    }

    /**
     * Convenience shortcut for ->resource('item-category').
     */
    public function itemCategories(): ItemCategoryResource
    {
        return $this->resource('item-category');
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->client()->get($endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->client()->post($endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->client()->put($endpoint, $data);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->client()->delete($endpoint, $data);
    }
}
