<?php

namespace ChrisLorando\LaravelAccurate\Http;

use ChrisLorando\LaravelAccurate\Auth\TokenManager;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use GuzzleHttp\Client as GuzzleClient;

class AccountClient
{
    protected GuzzleClient $http;

    public function __construct(protected TokenManager $tokenManager)
    {
        $this->http = new GuzzleClient([
            'base_uri' => config(
                'accurate.base_url',
                'https://account.accurate.id'
            ),
            'timeout' => config(
                'accurate.timeout',
                30
            ),
            'verify' => config(
                'accurate.verify_ssl',
                true
            ),
        ]);
    }

    public function databaseList(AccurateConnection $connection): array
    {

        $this->tokenManager->ensureValid(
            $connection
        );

        $response = $this->http->get(
            '/api/db-list.do',
            [
                'headers' => [
                    'Authorization' => sprintf(
                        '%s %s',
                        $connection->token_type,
                        $connection->access_token
                    ),
                ],
            ]
        );

        return json_decode(
            $response->getBody()->getContents(),
            true
        ) ?? [];
    }

    public function openDatabase(AccurateConnection $connection, string $databaseId): array
    {

        $this->tokenManager->ensureValid(
            $connection
        );

        $response = $this->http->post(
            '/api/open-db.do',
            [
                'headers' => [
                    'Authorization' => sprintf(
                        '%s %s',
                        $connection->token_type,
                        $connection->access_token
                    ),
                ],

                'form_params' => [
                    'id' => $databaseId,
                ],
            ]
        );

        $data = json_decode(
            $response->getBody()->getContents(),
            true
        ) ?? [];

        if (! isset($data['session'])) {
            throw new \RuntimeException(
                'Unable to open Accurate database.'
            );
        }

        return $data;
    }
}
