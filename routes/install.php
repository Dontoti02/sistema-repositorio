<?php

declare(strict_types=1);

use App\Http\Controllers\Install\InstallerController;

/** @var App\Core\Router $router */

$router->get('/instalar', [InstallerController::class, 'show']);
$router->post('/instalar/configurar', [InstallerController::class, 'configure'], ['csrf']);
$router->post('/instalar/finalizar', [InstallerController::class, 'finish'], ['csrf']);
