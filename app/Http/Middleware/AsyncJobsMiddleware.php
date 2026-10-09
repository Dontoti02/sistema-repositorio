<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Env;
use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Protege rutas de trabajos asíncronos (cola de trabajos).
 * Solo acepta llamadas internas con el token definido en APP_ASYNC_TOKEN
 * (cabecera X-Async-Token o token Bearer). Denegación por defecto.
 */
final class AsyncJobsMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) (Env::get('APP_ASYNC_TOKEN', ''));
        if ($expected === '') {
            throw new HttpException(403, 'Trabajos asíncronos no configurados (APP_ASYNC_TOKEN).');
        }

        $provided = $request->header('X-Async-Token') ?? $request->bearerToken() ?? '';
        if (!hash_equals($expected, $provided)) {
            throw new HttpException(403, 'Token de trabajo asíncrono inválido.');
        }

        return $next($request);
    }
}
