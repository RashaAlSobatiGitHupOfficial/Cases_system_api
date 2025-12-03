<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration (Laravel 12)
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'login',
        'logout',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => [
        '*'
    ],

    'allowed_origins' => [
        '*'   // Allow Vue (5173) + any external front-end
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        '*'
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
