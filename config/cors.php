<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:8031',
        'http://localhost:8080',
        'http://localhost:5173',
        'http://127.0.0.1:8031',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
    ]),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    // O front le o nome do arquivo baixado (PDF do resultado) neste cabecalho
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 0,
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
