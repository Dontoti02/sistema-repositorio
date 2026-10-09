<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Contrato de middleware.
 */
interface Middleware
{
    /**
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}
