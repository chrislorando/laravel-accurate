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

    'routes' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    'scopes' => [
        'item_view',
        'item_save',
        'sales_invoice_view',
    ],

];
