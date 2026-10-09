<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Limitador de tasa por sesión e IP.
 * Formato de parámetro: "maxintentos,minutos"  →  rate:10,1
 */
final class RateLimit implements Middleware
{
    private int $maxAttempts;

    private int $decayMinutes;

    public function __construct(?string $param = null)
    {
        $parts = explode(',', (string) $param);
        $this->maxAttempts = max(1, (int) ($parts[0] ?? 5));
        $this->decayMinutes = max(1, (int) ($parts[1] ?? 1));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'rl_' . hash('xxh128', $request->path() . '|' . $request->ipMasked());
        $bucket = $request->session($key);
        $now = time();

        if (is_array($bucket) && ($bucket['expires_at'] ?? 0) > $now) {
            if ((int) $bucket['hits'] >= $this->maxAttempts) {
                $retryAfter = (int) $bucket['expires_at'] - $now;
                if ($request->wantsJson()) {
                    throw new HttpException(429, 'Demasiadas solicitudes. Intente en ' . $retryAfter . ' s.');
                }
                throw new HttpException(429, 'Demasiados intentos. Espere ' . max(1, (int) ceil($retryAfter / 60)) . ' minuto(s) y vuelva a intentar.');
            }
            $bucket['hits'] = (int) $bucket['hits'] + 1;
        } else {
            $bucket = ['hits' => 1, 'expires_at' => $now + ($this->decayMinutes * 60)];
        }

        Session::put($key, $bucket);

        return $next($request);
    }
}
