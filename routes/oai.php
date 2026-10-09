<?php

declare(strict_types=1);

use App\Http\Controllers\Oai\OaiController;

// Proveedor OAI-PMH 2.0 (7.4): un único punto de entrada con el parámetro verb.
/** @var App\Core\Router $router */

$router->get('/oai', [OaiController::class, 'handle'], ['rate:120,1']);
