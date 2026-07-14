<?php

namespace ChrisLorando\LaravelAccurate\OAuth;

use Illuminate\Http\Request;
use ChrisLorando\LaravelAccurate\Models\AccurateConnection;

class CallbackController
{
    public function __construct(protected OAuthClient $oauth) {}

    public function __invoke(Request $request)
    {
        $code = $request->code;

        if (!$code) {
            throw new \Exception('Authorization code missing');
        }

        $token = $this->oauth->getAccessToken($code);

        AccurateConnection::updateOrCreate(
            [
                'name' => 'default',
            ],
            [
                'client_id' => config('accurate.client_id'),
                'client_secret' => config('accurate.client_secret'),
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'expires_at' => now()->addSeconds(
                    $token['expires_in'] ?? 3600
                ),

                'scopes' => explode(' ', $token['scope'] ?? ''),

                'accurate_user_id' => $token['user']['id'] ?? null,
                'accurate_user_name' => $token['user']['name'] ?? null,
                'accurate_user_nickname' => $token['user']['nickname'] ?? null,
                'accurate_user_email' => $token['user']['email'] ?? null,
                'accurate_user_mobile' => $token['user']['mobile'] ?? null,
            ]
        );

        return redirect('/');
    }
}