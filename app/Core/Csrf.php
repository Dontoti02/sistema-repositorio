<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protección CSRF por token sincronizado (formularios y Ajax).
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }
        return $token;
    }

    /** Campo HTML oculto. */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function refresh(): void
    {
        Session::put(self::KEY, bin2hex(random_bytes(32)));
    }

    /**
     * Valida el token de la petición; lanza 419 si no coincide.
     */
    public static function verify(Request $request): void
    {
        $method = $request->method();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        $token = (string) $request->input('_token', '');
        if ($token === '') {
            $token = (string) ($request->header('x-csrf-token') ?? '');
        }
        $expected = (string) Session::get(self::KEY, '');
        if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
            throw new HttpException(419, 'La sesión expiró o el formulario no es válido. Vuelva a intentarlo.');
        }
    }
}
