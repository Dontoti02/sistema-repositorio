<?php

declare(strict_types=1);

$base = dirname(__DIR__);

return [
    'driver' => env('STORAGE_DRIVER', 'local_private'),
    'private_path' => env('STORAGE_PRIVATE_PATH') ?: $base . '/storage/private',
    'public_path' => $base . '/public',
    'upload_max_mb' => (int) env('UPLOAD_MAX_MB', 50),
    'upload_max_files' => (int) env('UPLOAD_MAX_FILES', 10),
    'allowed_types' => array_filter(array_map('trim', explode(',', (string) env('UPLOAD_ALLOWED_TYPES', 'pdf,docx,xlsx,pptx,csv,jpg,jpeg,png,zip')))),
    'antivirus_enabled' => (bool) env('ANTIVIRUS_ENABLED', false),
    'ocr_enabled' => (bool) env('OCR_ENABLED', false),
];
