<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database;

/**
 * Administración de usuarios, membresías y roles por institución.
 * Todas las consultas están aisladas por institution_id.
 */
final class UserAdminRepository
{
    public function __construct(private readonly int $institutionId)
    {
    }

    /**
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function paginate(string $q = '', string $status = '', int $page = 1, int $perPage = 20): array
    {
        $where = ['m.institution_id = ?', 'u.deleted_at IS NULL'];
        $params = [$this->institutionId];
        if ($q !== '') {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (in_array($status, ['active', 'suspended'], true)) {
            $where[] = 'm.status = ?';
            $params[] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) (Database::selectOne(
            "SELECT COUNT(*) AS total FROM users u
             JOIN user_institution_memberships m ON m.user_id = u.id
             WHERE {$whereSql}",
            $params
        )['total'] ?? 0);

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $rows = Database::select(
            "SELECT u.id, u.full_name, u.email, u.status AS user_status, u.last_login_at,
                    m.status AS membership_status, m.id AS membership_id,
                    u.created_at
             FROM users u
             JOIN user_institution_memberships m ON m.user_id = u.id
             WHERE {$whereSql}
             ORDER BY u.full_name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        foreach ($rows as $i => $row) {
            $rows[$i]['roles'] = $this->rolesOfMembership((int) $row['membership_id']);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<string,mixed>|null */
    public function find(int $userId): ?array
    {
        $row = Database::selectOne(
            'SELECT u.id, u.full_name, u.email, u.status, u.must_change_password, u.last_login_at,
                    u.created_at, m.id AS membership_id, m.status AS membership_status
             FROM users u
             JOIN user_institution_memberships m ON m.user_id = u.id AND m.institution_id = ?
             WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1',
            [$this->institutionId, $userId]
        );
        if ($row !== null) {
            $row['role_ids'] = array_map(
                static fn (array $r): int => (int) $r['id'],
                $this->rolesOfMembership((int) $row['membership_id'])
            );
        }
        return $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function allRoles(): array
    {
        return Database::select(
            'SELECT id, slug, name, description, is_system FROM roles
             WHERE institution_id = ? ORDER BY sort_order, name',
            [$this->institutionId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function rolesOfMembership(int $membershipId): array
    {
        return Database::select(
            'SELECT r.id, r.slug, r.name FROM membership_roles mr
             JOIN roles r ON r.id = mr.role_id
             WHERE mr.membership_id = ? ORDER BY r.sort_order, r.name',
            [$membershipId]
        );
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM users WHERE email = ? AND deleted_at IS NULL';
        $params = [mb_strtolower($email)];
        if ($exceptUserId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptUserId;
        }
        return (int) (Database::selectOne($sql, $params)['total'] ?? 0) > 0;
    }

    /**
     * Crea usuario + membresía activa + roles asignados.
     *
     * @param array<int> $roleIds
     */
    public function create(string $fullName, string $email, string $passwordHash, array $roleIds): int
    {
        return Database::transaction(function () use ($fullName, $email, $passwordHash, $roleIds): int {
            $uuid = self::uuid4();
            Database::statement(
                'INSERT INTO users (uuid, full_name, email, password_hash, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [$uuid, $fullName, mb_strtolower($email), $passwordHash]
            );
            $userId = (int) Database::lastInsertId();
            Database::statement(
                'INSERT INTO user_institution_memberships (institution_id, user_id, status, created_at, updated_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [$this->institutionId, $userId, 'active']
            );
            $membershipId = (int) Database::lastInsertId();
            $this->syncRoles($membershipId, $roleIds);
            return $userId;
        });
    }

    /**
     * Actualiza datos básicos del usuario.
     */
    public function update(int $userId, string $fullName, string $email, bool $mustChangePassword): void
    {
        Database::statement(
            'UPDATE users SET full_name = ?, email = ?, must_change_password = ?,
             updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$fullName, mb_strtolower($email), $mustChangePassword ? 1 : 0, $userId]
        );
    }

    /** @param array<int> $roleIds */
    public function syncRoles(int $membershipId, array $roleIds): void
    {
        $valid = array_map(
            static fn (array $r): int => (int) $r['id'],
            array_filter(
                $this->allRoles(),
                static fn (array $r): bool => in_array((int) $r['id'], $roleIds, true)
            )
        );
        Database::statement('DELETE FROM membership_roles WHERE membership_id = ?', [$membershipId]);
        foreach ($valid as $roleId) {
            Database::statement(
                'INSERT INTO membership_roles (membership_id, role_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())',
                [$membershipId, $roleId]
            );
        }
    }

    public function setMembershipStatus(int $membershipId, string $status): void
    {
        Database::statement(
            'UPDATE user_institution_memberships SET status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$status, $membershipId]
        );
    }

    public function setPassword(int $userId, string $passwordHash, bool $mustChange = true): void
    {
        Database::statement(
            'UPDATE users SET password_hash = ?, must_change_password = ?,
             failed_attempts = 0, locked_until = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$passwordHash, $mustChange ? 1 : 0, $userId]
        );
        (new AuthRepository())->invalidateSessions($userId);
    }

    /** @return array{role_id:int, role_name:string, total:int}[] */
    public function roleUserCounts(): array
    {
        return Database::select(
            'SELECT r.id AS role_id, r.name AS role_name,
                    COUNT(CASE WHEN m.status = ? THEN mr.id END) AS total
             FROM roles r
             LEFT JOIN membership_roles mr ON mr.role_id = r.id
             LEFT JOIN user_institution_memberships m ON m.id = mr.membership_id
             WHERE r.institution_id = ?
             GROUP BY r.id, r.name ORDER BY r.sort_order',
            ['active', $this->institutionId]
        );
    }

    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
