<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Verifica CSRF en toda operación con estado salvo API con token Bearer.
 */
final class VerifyCsrfToken implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== null) {
            return $next($request);
        }
        Csrf::verify($request);
        return $next($request);
    }
}
