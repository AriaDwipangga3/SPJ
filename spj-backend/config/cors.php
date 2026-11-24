<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | File ini sebelumnya tidak ada. Tanpa ini, browser akan memblokir
    | request dari frontend (FRONTEND_URL di .env, mis. Vite di
    | localhost:5173) ke API ini karena tidak ada header
    | Access-Control-Allow-Origin pada response.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

'allowed_origins' => array_filter([
    env('FRONTEND_URL', 'http://localhost:5173'),
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://localhost',
    'http://localhost',
    'capacitor://localhost',
]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
