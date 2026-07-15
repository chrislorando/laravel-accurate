<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accurate OAuth Credentials
    |--------------------------------------------------------------------------
    */

    'client_id' => env('ACCURATE_CLIENT_ID'),

    'client_secret' => env('ACCURATE_CLIENT_SECRET'),

    'redirect_uri' => env('ACCURATE_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'base_url' => env('ACCURATE_BASE_URL', 'https://account.accurate.id'),

    'timeout' => 30,

    'verify_ssl' => true,

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    |
    | @see https://account.accurate.id/developer/api-docs.do
    |
    */

    'scopes' => [
        'item_view',
        'item_save',
        'item_category_view',
        'item_category_save',
    ],

];
