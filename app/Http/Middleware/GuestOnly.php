<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Para páginas de invitado (login/registro): evita bucles.
 */
final class GuestOnly implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $dest = (string) Session::get('_flash_redirect', '');
            Session::forget('_flash_redirect');
            if ($dest !== '' && !str_starts_with($dest, '/login')) {
                return Response::redirect($dest);
            }
            return Response::redirect('/admin');
        }
        return $next($request);
    }
}
