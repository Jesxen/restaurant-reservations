<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:4200')],

    // Allow this project's Vercel preview deployments. The trailing scope segment
    // (`-jesxen-s-projects`) is unique to the account, so other Vercel users can't
    // craft a matching origin. The stable production domain is set via FRONTEND_URL.
    'allowed_origins_patterns' => ['#^https://restaurant-reservations-[a-z0-9-]+-jesxen-s-projects\.vercel\.app$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
