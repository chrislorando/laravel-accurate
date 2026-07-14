<?php

namespace ChrisLorando\LaravelAccurate\Auth;

use ChrisLorando\LaravelAccurate\Models\AccurateConnection;
use ChrisLorando\LaravelAccurate\OAuth\OAuthClient;

class TokenManager
{
    public function __construct(protected OAuthClient $oauth) {}

    public function ensureValid(AccurateConnection $connection, bool $forceRefresh = false): void
    {
        if (! $forceRefresh && ! $connection->isTokenExpired()) {
            return;
        }

        $tokens = $this->oauth->refreshAccessToken(
            $connection->refresh_token
        );

        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token']
                ?? $connection->refresh_token,
            'expires_at' => now()->addSeconds(
                $tokens['expires_in'] ?? 3600
            ),
        ]);

        $connection->refresh();
    }
}
