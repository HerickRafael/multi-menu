<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Multi Menu'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'session_name' => env('SESSION_NAME', 'mm_session'),
    'novidades_days' => (int) env('APP_NEWS_DAYS', 0),
];
