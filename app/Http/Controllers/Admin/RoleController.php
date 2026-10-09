<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Domain\Identity\UserAdminRepository;
use App\Services\AuditLogger;

/**
 * Roles y permisos (6.4): asignacion de permisos por rol dentro del instituto.
 */
final class RoleController extends AdminController
{
    private UserAdminRepository $repo;

    public function __construct()
    {
        $this->repo = new UserAdminRepository(TenantContext::institutionId());
    }

    public function index(Request $request): Response
    {
        $this->authorize('roles.manage');
        $roles = $this->repo->allRoles();
        $counts = [];
        foreach ($this->repo->roleUserCounts() as $row) {
            $counts[(int) $row['role_id']] = (int) $row['total'];
        }
        return $this->view('admin/roles/index', [
            'pageTitle' => 'Roles y permisos',
            'roles' => $roles,
            'counts' => $counts,
        ]);
    }

    public function edit(Request $request): Response
    {
        $this->authorize('roles.manage');
        $roleId = (int) $request->route('id');
        $role = $this->role($roleId);
        if ($role === null) {
            throw new HttpException(404, 'Rol no encontrado.');
        }
        $permissions = Database::select('SELECT id, permission_key, label, grp FROM permissions ORDER BY grp, label');
        $assigned = array_map(
            static fn (array $r): int => (int) $r['permission_id'],
            Database::select('SELECT permission_id FROM role_permissions WHERE role_id = ?', [$roleId])
        );
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['grp']][] = $perm + ['assigned' => in_array((int) $perm['id'], $assigned, true)];
        }
        return $this->view('admin/roles/edit', [
            'pageTitle' => 'Permisos: ' . $role['name'],
            'role' => $role,
            'grouped' => $grouped,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('roles.manage');
        $roleId = (int) $request->route('id');
        $role = $this->role($roleId);
        if ($role === null) {
            throw new HttpException(404, 'Rol no encontrado.');
        }

        $selected = array_map('intval', array_filter($request->array('permissions', []), 'is_scalar'));
        $validIds = array_map(
            static fn (array $r): int => (int) $r['id'],
            Database::select('SELECT id FROM permissions')
        );
        $selected = array_values(array_intersect($selected, $validIds));

        $before = Database::select('SELECT permission_id FROM role_permissions WHERE role_id = ?', [$roleId]);
        Database::transaction(function () use ($roleId, $selected): void {
            Database::statement('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
            foreach ($selected as $permId) {
                Database::statement(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                    [$roleId, $permId]
                );
            }
        });
        AuditLogger::log(
            'roles.update_permissions',
            'roles',
            $roleId,
            'permisos: ' . count($before),
            'permisos: ' . count($selected)
        );

        $this->flash('success', 'Permisos del rol actualizados.');
        return $this->redirect('/admin/roles/' . $roleId . '/permisos');
    }

    /** @return array<string,mixed>|null */
    private function role(int $id): ?array
    {
        return Database::selectOne(
            'SELECT id, slug, name, description, is_system FROM roles WHERE institution_id = ? AND id = ? LIMIT 1',
            [TenantContext::institutionId(), $id]
        );
    }
}
