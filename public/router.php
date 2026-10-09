<?php

declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP (php -S 127.0.0.1:8080 -t public public/router.php).
 * Sirve archivos estáticos reales; el resto lo enruta a index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    // Bloquea acceso a archivos sensibles dentro de public/.
    $blocked = ['index.php', 'router.php', '.htaccess', '.env'];
    if (in_array(basename($file), $blocked, true) || str_starts_with(basename($file), '.')) {
        http_response_code(404);
        exit('Not found');
    }
    return false; // El servidor embebido entrega el archivo estático.
}

require __DIR__ . '/index.php';
