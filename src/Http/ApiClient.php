<?php

namespace ChrisLorando\LaravelAccurate\Http;

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Exceptions\AccurateApiException;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\Models\AccurateDatabase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class ApiClient
{
    protected GuzzleClient $http;

    protected AccurateConnection $connection;

    protected AccurateDatabase $database;

    public function __construct(
        protected TokenManager $tokenManager,
        ?GuzzleClient $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient;
    }

    /**
     * Configure the client for a specific connection and database session.
     */
    public function for(AccurateConnection $connection, AccurateDatabase $database): self
    {
        $this->connection = $connection;
        $this->database = $database;

        return $this;
    }

    /**
     * Send a GET request to the Accurate API.
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Send a POST request to the Accurate API.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'form_params' => $data,
        ]);
    }

    /**
     * Send a POST request with a JSON body to the Accurate API.
     * Used for endpoints like bulk-save.do that accept application/json.
     */
    public function postJson(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Send a PUT request to the Accurate API.
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [
            'form_params' => $data,
        ]);
    }

    /**
     * Send a DELETE request to the Accurate API.
     * Accurate's delete.do endpoints read the id from the query string.
     */
    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, [
            'query' => $data,
        ]);
    }

    /**
     * Execute an HTTP request with token and session handling.
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        $this->tokenManager->ensureValid($this->connection);

        $options = array_merge([
            'base_uri' => $this->database->host,
            'timeout' => config('accurate.timeout', 30),
            'verify' => config('accurate.verify_ssl', true),
            'headers' => [],
        ], $options);

        $options['headers'] = array_merge(
            ['Accept' => 'application/json'],
            $options['headers'],
            $this->authHeaders()
        );

        try {
            $response = $this->http->request(
                $method,
                $endpoint,
                $options
            );

            return json_decode(
                $response->getBody()->getContents(),
                true
            ) ?? [];
        } catch (ClientException $e) {
            throw AccurateApiException::fromClientException($e);
        } catch (ServerException $e) {
            throw AccurateApiException::fromServerException($e);
        }
    }

    /**
     * Build authentication headers for the API request.
     */
    protected function authHeaders(): array
    {
        return [
            'Authorization' => sprintf(
                '%s %s',
                $this->connection->token_type,
                $this->connection->access_token
            ),
            'X-Session-ID' => $this->database->session_id,
        ];
    }
}
