<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Cabeceras de seguridad globales. Se aplica siempre en Application::dispatch
 * (única línea exterior del pipeline), incluidas respuestas de error.
 */
final class SecurityHeaders implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin');

        if (!isset($response->headers()['Content-Security-Policy'])) {
            $csp = implode('; ', [
                "default-src 'self'",
                "style-src 'self' 'unsafe-inline'",
                "script-src 'self'",
                "img-src 'self' data:",
                "font-src 'self'",
                "object-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);
            $response = $response->withHeader('Content-Security-Policy', $csp);
        }

        if ($request->isSecure()) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
