<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestión de sesiones con parámetros seguros.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        $secure = (bool) config('app.session.secure_cookie', false);
        $sameSite = (string) config('app.session.same_site', 'Lax');
        $lifetime = (int) config('app.session.lifetime_minutes', 120);

        if (!headers_sent()) {
            session_name('REPO_SESSION');
            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $sameSite,
            ]);
        }
        ini_set('session.gc_maxlifetime', (string) ($lifetime * 60));
        session_start();
        self::$started = true;

        // Expiración por inactividad.
        $timeout = $lifetime * 60;
        $now = time();
        if (isset($_SESSION['_last_activity']) && ($now - (int) $_SESSION['_last_activity']) > $timeout) {
            self::destroy();
            session_start();
            $_SESSION['_last_activity'] = $now;
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /** Regenera el ID de sesión (al login y elevación de privilegios). */
    public static function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    /** Mensaje flash: persiste un solo request. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public static function pullFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($flashes) ? $flashes : [];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            session_destroy();
        }
        self::$started = false;
    }
}
