<?php

namespace ChrisLorando\LaravelAccurate;

use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Http\ApiClient;
use ChrisLorando\LaravelAccurate\Http\Resources\BranchResource;
use ChrisLorando\LaravelAccurate\Http\Resources\CurrencyResource;
use ChrisLorando\LaravelAccurate\Http\Resources\DepartmentResource;
use ChrisLorando\LaravelAccurate\Http\Resources\EmployeeResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ExpenseResource;
use ChrisLorando\LaravelAccurate\Http\Resources\FobResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemCategoryResource;
use ChrisLorando\LaravelAccurate\Http\Resources\ItemResource;
use ChrisLorando\LaravelAccurate\Http\Resources\PaymentTermResource;
use ChrisLorando\LaravelAccurate\Http\Resources\Resource;
use ChrisLorando\LaravelAccurate\Http\Resources\TaxResource;
use ChrisLorando\LaravelAccurate\Http\Resources\UnitResource;
use ChrisLorando\LaravelAccurate\Http\Resources\WarehouseResource;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use ChrisLorando\LaravelAccurate\OAuth\OAuthClient;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class LaravelAccurate implements Arrayable
{
    protected ?AccurateConnection $connection = null;

    protected ?AccurateDatabase $database = null;

    /**
     * The raw response from the last openDatabase() call.
     */
    protected ?array $openedDatabase = null;

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

        $this->openedDatabase = $result;

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
                'session_id' => $result['session'],
                'session_expires_at' => Carbon::createFromFormat('d/m/Y', $result['accessibleUntil']),
            ]
        );

        $this->switchDatabase($this->database);

        return $this;
    }

    /**
     * Resolve an already-opened database from the local DB table.
     * No HTTP call — uses the cached session_id.
     * Ideal for background jobs, CLI commands, or hot-switching
     * between databases within the same request.
     */
    public function on(string $databaseId): self
    {
        $this->ensureConnection();

        $this->database = AccurateDatabase::where('connection_id', $this->connection->id)
            ->where(function ($query) use ($databaseId) {
                $query->where('database_id', $databaseId)
                    ->orWhere('alias', $databaseId);
            })
            ->firstOrFail();

        // Do NOT switch session — on() is transient and doesn't change
        // the active database for subsequent requests.

        return $this;
    }

    protected function ensureConnection(): void
    {
        if ($this->connection) {
            return;
        }

        // Auto-resolve from the session's active database.
        $database = $this->currentDatabase();

        if ($database) {
            $this->connection = $database->connection;
            $this->database = $database;

            return;
        }

        throw new \RuntimeException(
            'No Accurate connection selected. Call connection() first.'
        );
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
            'branch' => new BranchResource($api),
            'currency' => new CurrencyResource($api),
            'department' => new DepartmentResource($api),
            'employee' => new EmployeeResource($api),
            'expense' => new ExpenseResource($api),
            'fob' => new FobResource($api),
            'item' => new ItemResource($api),
            'tax' => new TaxResource($api),
            'item-category' => new ItemCategoryResource($api),
            'payment-term' => new PaymentTermResource($api),
            'unit' => new UnitResource($api),
            'warehouse' => new WarehouseResource($api),
            default => new class($api, $name) extends Resource
            {
                public function __construct(ApiClient $api, string $resourceName)
                {
                    parent::__construct($api);
                    $this->resourceName = $resourceName;
                }
            },
        };
    }

    /**
     * Convenience shortcut for ->resource('item').
     */
    public function items(): ItemResource
    {
        return new ItemResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('item-category').
     */
    public function itemCategories(): ItemCategoryResource
    {
        return new ItemCategoryResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('unit').
     */
    public function units(): UnitResource
    {
        return new UnitResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('branch').
     */
    public function branches(): BranchResource
    {
        return new BranchResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('currency').
     */
    public function currencies(): CurrencyResource
    {
        return new CurrencyResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('department').
     */
    public function departments(): DepartmentResource
    {
        return new DepartmentResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('tax').
     */
    public function taxes(): TaxResource
    {
        return new TaxResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('employee').
     */
    public function employees(): EmployeeResource
    {
        return new EmployeeResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('expense').
     */
    public function expenses(): ExpenseResource
    {
        return new ExpenseResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('fob').
     */
    public function fobs(): FobResource
    {
        return new FobResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('payment-term').
     */
    public function paymentTerms(): PaymentTermResource
    {
        return new PaymentTermResource($this->client());
    }

    /**
     * Convenience shortcut for ->resource('warehouse').
     */
    public function warehouses(): WarehouseResource
    {
        return new WarehouseResource($this->client());
    }

    /**
     * Get the currently active database from session.
     */
    public function currentDatabase(): ?AccurateDatabase
    {
        $id = session('accurate_active_database_id');

        return AccurateDatabase::find($id);
    }

    /**
     * Switch the active database in session.
     */
    public function switchDatabase(AccurateDatabase $database): void
    {
        session(['accurate_active_database_id' => $database->id]);
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

    public function toArray(): array
    {
        return $this->openedDatabase ?? [];
    }
}
