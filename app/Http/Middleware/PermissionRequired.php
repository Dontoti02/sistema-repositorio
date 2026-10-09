<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Authorization;
use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Exige un permiso RBAC (denegación por defecto).
 * Uso en rutas: 'permission:items.publish'.
 */
final class PermissionRequired implements Middleware
{
    public function __construct(private readonly string $permission)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->permission === '') {
            throw new HttpException(403, 'Permiso no especificado en la ruta.');
        }
        Authorization::authorize($this->permission);
        return $next($request);
    }
}
