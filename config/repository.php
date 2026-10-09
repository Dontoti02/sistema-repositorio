<?php

declare(strict_types=1);

return [
    'oai_enabled' => (bool) env('OAI_ENABLED', true),
    'oai_repository_identifier' => env('OAI_REPOSITORY_IDENTIFIER', ''),
    'oai_admin_email' => env('OAI_ADMIN_EMAIL', ''),
    'open_api_enabled' => (bool) env('OPEN_API_ENABLED', true),
    'page_size_public' => 12,
    'page_size_admin' => 25,
    'page_size_max' => 100,
    'queue_driver' => env('QUEUE_DRIVER', 'database'),
    'backup_encryption_key' => env('BACKUP_ENCRYPTION_KEY', ''),
];
