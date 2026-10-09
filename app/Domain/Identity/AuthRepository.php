<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database;

/**
 * Acceso a datos de autenticación y permisos.
 */
final class AuthRepository
{
    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [mb_strtolower($email)]
        );
    }

    /**
     * Usuario + membresía + roles + permisos en el instituto activo.
     *
     * @return array<string,mixed>|null
     */
    public function findWithMembership(int $userId, int $institutionId): ?array
    {
        $user = Database::selectOne(
            'SELECT id, uuid, full_name, email, status, must_change_password, email_verified_at, last_login_at
             FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$userId]
        );
        if ($user === null) {
            return null;
        }
        $membership = Database::selectOne(
            'SELECT id, status, is_platform_admin FROM user_institution_memberships
             WHERE user_id = ? AND institution_id = ? LIMIT 1',
            [$userId, $institutionId]
        );
        // Sin membresía activa → sin acceso a este instituto (denegación por defecto).
        if ($membership === null || $membership['status'] !== 'active') {
            return null;
        }
        $user['membership'] = $membership;
        $roles = Database::select(
            'SELECT r.slug, r.name FROM membership_roles mr
             JOIN roles r ON r.id = mr.role_id
             WHERE mr.membership_id = ? ORDER BY r.sort_order',
            [(int) $membership['id']]
        );
        $user['roles'] = $roles;
        $user['role_names'] = array_column($roles, 'name');
        $user['role_slugs'] = array_column($roles, 'slug');
        return $user;
    }

    /**
     * Permisos efectivos del usuario en el instituto (roles de su membresía).
     *
     * @return array<int,string>
     */
    public function permissionsFor(int $userId, int $institutionId): array
    {
        $rows = Database::select(
            'SELECT DISTINCT p.permission_key
             FROM user_institution_memberships m
             JOIN membership_roles mr ON mr.membership_id = m.id
             JOIN role_permissions rp ON rp.role_id = mr.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE m.user_id = ? AND m.institution_id = ? AND m.status = ?',
            [$userId, $institutionId, 'active']
        );
        return array_column($rows, 'permission_key');
    }

    /**
     * Permisos de administrador de plataforma (solo modo multi).
     *
     * @return array<int,string>
     */
    public function globalPermissions(int $userId): array
    {
        if ((string) config('app.tenancy_mode', 'single') !== 'multi') {
            return [];
        }
        $rows = Database::select(
            'SELECT DISTINCT p.permission_key
             FROM user_institution_memberships m
             JOIN membership_roles mr ON mr.membership_id = m.id
             JOIN role_permissions rp ON rp.role_id = mr.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE m.user_id = ? AND m.is_platform_admin = 1',
            [$userId]
        );
        return array_column($rows, 'permission_key');
    }

    public function recordSuccessfulLogin(int $userId): void
    {
        Database::statement(
            'UPDATE users SET failed_attempts = 0, locked_until = NULL,
             last_login_at = UTC_TIMESTAMP() WHERE id = ?',
            [$userId]
        );
    }

    public function recordFailedLogin(int $userId): void
    {
        Database::statement(
            'UPDATE users SET failed_attempts = failed_attempts + 1,
             locked_until = CASE WHEN failed_attempts + 1 >= 5
                THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE) ELSE locked_until END
             WHERE id = ?',
            [$userId]
        );
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        Database::statement('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $userId]);
    }

    public function invalidateSessions(int $userId): void
    {
        Database::statement(
            'UPDATE user_sessions SET revoked_at = UTC_TIMESTAMP()
             WHERE user_id = ? AND revoked_at IS NULL',
            [$userId]
        );
    }
}
