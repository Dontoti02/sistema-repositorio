<?php

declare(strict_types=1);

namespace App\Core;

use App\Domain\Identity\AuthRepository;

/**
 * Autenticación basada en sesión.
 * Carga el usuario en cada request y verifica que esté activo.
 */
final class Auth
{
    private const SESSION_KEY = 'auth.user_id';

    private static ?array $user = null;

    private static bool $loaded = false;

    /** @var array<int,string>|null */
    private static ?array $permissions = null;

    public static function attempt(string $email, string $password, ?int $institutionId = null): bool
    {
        $repo = new AuthRepository();
        $user = $repo->findByEmail(trim($email));
        if ($user === null) {
            // Comprobación temporal para no revelar existencia de cuenta.
            password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidu');
            return false;
        }
        if ((int) $user['status'] !== 1) {
            return false;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            $repo->recordFailedLogin((int) $user['id']);
            return false;
        }
        // Verificar membresía activa si se exige instituto.
        if ($institutionId !== null && !$repo->hasActiveMembership((int) $user['id'], $institutionId)) {
            return false;
        }
        // Rehash si el algoritmo cambió.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $repo->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        self::loginUser((int) $user['id']);
        $repo->recordSuccessfulLogin((int) $user['id']);
        return true;
    }

    public static function loginUser(int $userId): void
    {
        Session::regenerate(true);
        Session::put(self::SESSION_KEY, $userId);
        Csrf::refresh();
        self::$user = null;
        self::$permissions = null;
        self::$loaded = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $id = Session::get(self::SESSION_KEY);
        if (!is_numeric($id)) {
            self::$user = null;
            return null;
        }
        $repo = new AuthRepository();
        $user = $repo->findWithMembership((int) $id, TenantContext::institutionId());
        if ($user === null || (int) $user['status'] !== 1) {
            // Usuario suspendido o eliminado: cierra sesión inmediatamente.
            Session::forget(self::SESSION_KEY);
            self::$user = null;
            return null;
        }
        self::$user = $user;
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u !== null ? (int) $u['id'] : null;
    }

    /** @return array<int,string> */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $u = self::user();
        if ($u === null) {
            self::$permissions = [];
            return [];
        }
        $repo = new AuthRepository();
        $perms = $repo->permissionsFor((int) $u['id'], TenantContext::institutionId());
        $global = $repo->globalPermissions((int) $u['id']);
        self::$permissions = array_values(array_unique(array_merge($perms, $global)));
        return self::$permissions;
    }

    /** @return array<int,string> */
    public static function roles(): array
    {
        $u = self::user();
        if ($u === null || !isset($u['roles']) || !is_array($u['roles'])) {
            return [];
        }
        return $u['roles'];
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            (new AuthRepository())->invalidateSessions($id);
        }
        Session::destroy();
        self::$user = null;
        self::$permissions = null;
        self::$loaded = false;
    }

    /** Reinicia caché (tras cambios de rol/permisos). */
    public static function flush(): void
    {
        self::$user = null;
        self::$permissions = null;
        self::$loaded = false;
    }
}
