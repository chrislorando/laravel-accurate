<?php

namespace ChrisLorando\LaravelAccurate;

use ChrisLorando\LaravelAccurate\Http\AccountClient;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use ChrisLorando\LaravelAccurate\OAuth\OAuthClient;

class LaravelAccurate
{
    protected ?AccurateConnection $connection = null;

    public function __construct(
        protected OAuthClient $oauth,
        protected AccountClient $account,
    ) {}

    public function test(): string
    {
        return 'Laravel Accurate is working';
    }

    public function oauth(): OAuthClient
    {
        return $this->oauth;
    }

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
        return $this->account->databaseList(
            $this->connection
        );
    }

    public function openDatabase(
        string $databaseId,
        string $alias
    ): AccurateDatabase {

        $result = $this->account->openDatabase(
            $this->connection,
            $databaseId
        );

        if (! $this->connection) {
            throw new \Exception(
                'Connection not selected'
            );
        }

        $result = $this->account->openDatabase(
            $this->connection,
            $databaseId
        );

        return AccurateDatabase::updateOrCreate(
            [
                'connection_id' => $this->connection->id,
                'database_id' => $databaseId,
            ],
            [
                'alias' => $alias,
                'company_name' => $alias,
                'host' => $result['host'],
                'session_id' => $result['session_id'],
                'session_expires_at' => now()->addHours(2),
            ]
        );
    }
}
