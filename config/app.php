<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Repositorio Institucional'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'America/Lima'),
    'locale' => env('APP_LOCALE', 'es_PE'),
    'key' => env('APP_KEY', ''),
    'installation_mode' => env('INSTALLATION_MODE', 'single'),
    'tenancy_mode' => env('TENANCY_MODE', 'single'),
    'session' => [
        'secure_cookie' => (bool) env('SESSION_SECURE_COOKIE', false),
        'same_site' => env('SESSION_SAME_SITE', 'Lax'),
        'lifetime_minutes' => (int) env('SESSION_LIFETIME_MINUTES', 120),
    ],
];
