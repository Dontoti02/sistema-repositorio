<?php

declare(strict_types=1);

return [
    'driver' => env('MAIL_DRIVER', 'log'),
    'host' => env('MAIL_HOST', ''),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-responder@example.invalid'),
        'name' => env('MAIL_FROM_NAME', 'Repositorio Institucional'),
    ],
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
];
