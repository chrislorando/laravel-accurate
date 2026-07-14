<?php

namespace ChrisLorando\LaravelAccurate\OAuth;

use GuzzleHttp\Client;

class OAuthClient
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('accurate.base_url'),
            'timeout' => 30,
        ]);
    }

    public function getAuthorizationUrl(?string $state = null): string
    {
        $state ??= str()->random(40);

        return config('accurate.base_url') . '/oauth/authorize?' . http_build_query([
            'client_id' => config('accurate.client_id'),
            'redirect_uri' => config('accurate.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', config('accurate.scopes')),
            'state' => $state,
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = $this->client->post('/oauth/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    config('accurate.client_id') . ':' . config('accurate.client_secret')
                ),
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => route('accurate.callback'),
            ],
        ]);

        $data = json_decode(
            $response->getBody()->getContents(),
            true
        );

        if (!isset($data['access_token'])) {
            throw new \Exception('Failed to get access token');
        }

        return $data;
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = $this->client->post('/oauth/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    config('accurate.client_id') . ':' . config('accurate.client_secret')
                ),
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        $data = json_decode(
            $response->getBody()->getContents(),
            true
        );

        if (!isset($data['access_token'])) {
            throw new \Exception('Failed to refresh access token');
        }

        return $data;
    }
}