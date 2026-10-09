<?php

declare(strict_types=1);

use App\Http\Controllers\Web\ExploreController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ItemController;
use App\Http\Controllers\Web\SearchController;

/** @var App\Core\Router $router */

// ---------- Portal público ----------
$router->get('/', [HomeController::class, 'index']);
$router->get('/acerca-de', [HomeController::class, 'about']);
$router->get('/politicas', [HomeController::class, 'policies']);
$router->get('/contacto', [HomeController::class, 'contact']);
$router->post('/contacto', [HomeController::class, 'contact'], ['csrf']);
$router->get('/preguntas-frecuentes', [HomeController::class, 'faq']);
$router->get('/manual', [HomeController::class, 'manual']);
$router->get('/estadisticas', [HomeController::class, 'stats']);
$router->get('/sitemap.xml', [HomeController::class, 'sitemap']);

// Exploración.
$router->get('/comunidades', [ExploreController::class, 'communities']);
$router->get('/comunidades/{slug}', [ExploreController::class, 'community']);
$router->get('/colecciones/{slug}', [ExploreController::class, 'collection']);
$router->get('/programas/{slug}', [ExploreController::class, 'program']);
$router->get('/autores', [ExploreController::class, 'authors']);
$router->get('/autores/{slug}', [ExploreController::class, 'author']);

// Búsqueda.
$router->get('/buscar', [SearchController::class, 'index']);
$router->get('/busqueda-avanzada', [SearchController::class, 'advanced']);

// Fichas de recursos (identificador estable; el slug no altera la URL).
$router->get('/recursos', [SearchController::class, 'index']);
$router->get('/recursos/{identificador}', [ItemController::class, 'show']);
$router->get('/recursos/{identificador}/cita', [ItemController::class, 'citation']);
$router->get('/recursos/{identificador}/archivos/{fileId}/ver', [ItemController::class, 'fileView']);
$router->get('/recursos/{identificador}/archivos/{fileId}/descargar', [ItemController::class, 'fileDownload']);
// Slug opcional: el identificador estable manda; el slug no cambia la ficha.
$router->get('/recursos/{identificador}/{slug}', [ItemController::class, 'show']);

// Autenticación.
$router->get('/login', [\App\Http\Controllers\Web\AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [\App\Http\Controllers\Web\AuthController::class, 'login'], ['guest', 'csrf', 'rate:10,5']);
$router->post('/logout', [\App\Http\Controllers\Web\AuthController::class, 'logout'], ['csrf']);
$router->get('/recuperar-contrasena', [\App\Http\Controllers\Web\AuthController::class, 'showForgot'], ['guest']);
$router->post('/recuperar-contrasena', [\App\Http\Controllers\Web\AuthController::class, 'forgot'], ['guest', 'csrf', 'rate:5,10']);
$router->get('/recuperar', [\App\Http\Controllers\Web\AuthController::class, 'showReset'], ['guest']);
$router->post('/recuperar', [\App\Http\Controllers\Web\AuthController::class, 'reset'], ['guest', 'csrf', 'rate:5,10']);

// Entrega de temas CSS seguros y activos de marca institucional
$router->get('/assets/temas/{id:\d+}/{version:\d+}.css', static function ($request) {
    $id = (int) $request->param('id');
    $theme = \App\Services\ThemeService::getPublishedTheme($id);
    $css = \App\Services\ThemeService::compileCss($theme['tokens']);
    return new \App\Core\Response(200, [
        'Content-Type'           => 'text/css; charset=UTF-8',
        'Cache-Control'          => 'public, max-age=604800, immutable',
        'X-Content-Type-Options' => 'nosniff',
    ], $css);
});

$router->get('/assets/marca/{id:\d+}/{kind:[a-z_]+}', static function ($request) {
    $id = (int) $request->param('id');
    $kind = (string) $request->param('kind');
    return \App\Services\BrandAssetService::serveAsset($id, $kind);
});

