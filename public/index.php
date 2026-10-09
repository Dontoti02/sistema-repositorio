<?php

declare(strict_types=1);

/**
 * Punto de entrada web. Todo pasa por el enrutador.
 */

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Falta vendor/autoload.php. Ejecute: composer dump-autoload';
    exit(1);
}
require $autoload;

use App\Core\Application;

$app = Application::getInstance();
$response = $app->run();
$response->send();
