<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Exige sesión activa. Acepta token Bearer para integraciones
 * (tabla api_tokens, alcance estricto).
 */
final class Authenticate implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }
        $token = $request->bearerToken();
        if ($token !== null) {
            $user = $this->userByToken($token);
            if ($user !== null) {
                Auth::loginUser((int) $user['id']);
                return $next($request);
            }
        }
        if ($request->wantsJson()) {
            throw new HttpException(401, 'Autenticación requerida.');
        }
        // Guarda a dónde volvió tras iniciar sesión y redirige al login.
        \App\Core\Session::put('_flash_redirect', $request->path());
        return Response::redirect('/login');
    }

    /** @return array<string,mixed>|null */
    private function userByToken(string $token): ?array
    {
        try {
            $hash = hash('sha256', $token);
            $row = \App\Core\Database::selectOne(
                'SELECT u.* FROM api_tokens t
                 JOIN users u ON u.id = t.user_id
                 WHERE t.token_hash = ? AND t.revoked_at IS NULL
                   AND (t.expires_at IS NULL OR t.expires_at > UTC_TIMESTAMP())
                   AND u.status = 1
                 LIMIT 1',
                [$hash]
            );
            if ($row !== null) {
                \App\Core\Database::statement(
                    'UPDATE api_tokens SET last_used_at = UTC_TIMESTAMP() WHERE token_hash = ?',
                    [$hash]
                );
            }
            return $row;
        } catch (\Throwable) {
            return null;
        }
    }
}
