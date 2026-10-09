<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiController;

// API REST v1 — solo lectura pública (7.5): rate limit de 60 solicitudes/minuto.
/** @var App\Core\Router $router */

$router->get('/api/v1', [ApiController::class, 'index'], ['rate:60,1']);
$router->get('/api/v1/documentacion', [ApiController::class, 'docs'], ['rate:60,1']);
$router->get('/api/v1/recursos', [ApiController::class, 'items'], ['rate:60,1']);
$router->get('/api/v1/recursos/{id}', [ApiController::class, 'item'], ['rate:60,1']);
$router->get('/api/v1/comunidades', [ApiController::class, 'communities'], ['rate:60,1']);
$router->get('/api/v1/comunidades/{slug}', [ApiController::class, 'community'], ['rate:60,1']);
$router->get('/api/v1/colecciones/{slug}', [ApiController::class, 'collection'], ['rate:60,1']);
$router->get('/api/v1/colecciones/{slug}/recursos', [ApiController::class, 'collectionItems'], ['rate:60,1']);
