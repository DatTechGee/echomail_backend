<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://10.0.2.2:8000',
        'https://scholarsnudge.com',
        'https://www.scholarsnudge.com',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/192\.168\..*:8000$/',
        '/^http:\/\/192\.168\..*:3000$/',
        '/^https:\/\/.*\.vercel\.app$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
