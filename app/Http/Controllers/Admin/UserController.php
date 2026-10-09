<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Domain\Identity\AuthRepository;
use App\Domain\Identity\UserAdminRepository;
use App\Services\AuditLogger;

/**
 * Usuarios del instituto (6.3): alta, edicion, roles y suspension.
 */
final class UserController extends AdminController
{
    private UserAdminRepository $repo;

    public function __construct()
    {
        $this->repo = new UserAdminRepository(TenantContext::institutionId());
    }

    public function index(Request $request): Response
    {
        $this->authorize('users.view');
        $q = $request->str('q');
        $status = $request->str('estado');
        $page = max(1, $request->int('pagina', 1));
        $result = $this->repo->paginate($q, $status, $page, 20);

        return $this->view('admin/users/index', [
            'pageTitle' => 'Usuarios',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('users.create');
        return $this->view('admin/users/form', [
            'pageTitle' => 'Nuevo usuario',
            'user' => null,
            'roles' => $this->repo->allRoles(),
            'selectedRoleIds' => [],
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('users.create');
        $data = $this->validate($request, [
            'full_name' => 'required|max:190',
            'email' => 'required|email|max:190',
            'password' => 'required|min:10|max:100|confirmed',
        ], ['full_name' => 'Nombre completo', 'email' => 'Correo', 'password' => 'Contrasena']);

        if ($this->repo->emailExists((string) $data['email'])) {
            throw new HttpException(422, 'Ya existe un usuario con ese correo.', ['email' => 'El correo ya esta registrado.']);
        }

        $roleIds = $this->roleIdsFrom($request);
        $userId = $this->repo->create(
            (string) $data['full_name'],
            (string) $data['email'],
            password_hash((string) $data['password'], PASSWORD_DEFAULT),
            $roleIds
        );
        AuditLogger::log('users.create', 'users', $userId, null, $data['full_name'] . ' <' . $data['email'] . '>');

        $this->flash('success', 'Usuario creado correctamente.');
        return $this->redirect('/admin/usuarios/' . $userId . '/editar');
    }

    public function edit(Request $request): Response
    {
        $this->authorize('users.update');
        $user = $this->repo->find((int) $request->route('id'));
        if ($user === null) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }
        return $this->view('admin/users/form', [
            'pageTitle' => 'Editar usuario',
            'user' => $user,
            'roles' => $this->repo->allRoles(),
            'selectedRoleIds' => $user['role_ids'],
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('users.update');
        $user = $this->repo->find((int) $request->route('id'));
        if ($user === null) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }
        $data = $this->validate($request, [
            'full_name' => 'required|max:190',
            'email' => 'required|email|max:190',
        ], ['full_name' => 'Nombre completo', 'email' => 'Correo']);

        if ($this->repo->emailExists((string) $data['email'], (int) $user['id'])) {
            throw new HttpException(422, 'Correo no disponible.', ['email' => 'El correo ya esta registrado.']);
        }

        $roleIds = $this->roleIdsFrom($request);
        if ((int) $user['id'] === (int) Auth::id() && $roleIds === []) {
            throw new HttpException(422, 'No puede quitarse todos sus roles.', ['roles' => 'Debe conservar al menos un rol.']);
        }

        $before = $user['full_name'] . ' | roles: ' . implode(',', $user['role_ids']);
        $this->repo->update((int) $user['id'], (string) $data['full_name'], (string) $data['email'], $request->input('must_change_password') !== null);
        $this->repo->syncRoles((int) $user['membership_id'], $roleIds);
        AuditLogger::log('users.update', 'users', (int) $user['id'], $before, $data['full_name'] . ' | roles: ' . implode(',', $roleIds));

        $this->flash('success', 'Usuario actualizado.');
        return $this->redirect('/admin/usuarios');
    }

    public function toggle(Request $request): Response
    {
        $this->authorize('users.disable');
        $user = $this->repo->find((int) $request->route('id'));
        if ($user === null) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }
        if ((int) $user['id'] === (int) Auth::id()) {
            throw new HttpException(422, 'No puede suspender su propia cuenta.');
        }
        $newStatus = $user['membership_status'] === 'active' ? 'suspended' : 'active';
        $this->repo->setMembershipStatus((int) $user['membership_id'], $newStatus);
        if ($newStatus === 'suspended') {
            (new AuthRepository())->invalidateSessions((int) $user['id']);
        }
        AuditLogger::log('users.status', 'users', (int) $user['id'], $user['membership_status'], $newStatus);

        $this->flash('success', $newStatus === 'active' ? 'Usuario reactivado.' : 'Usuario suspendido.');
        return $this->redirect('/admin/usuarios');
    }

    public function resetPassword(Request $request): Response
    {
        $this->authorize('users.update');
        $user = $this->repo->find((int) $request->route('id'));
        if ($user === null) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }
        $generated = self::randomPassword();
        $this->repo->setPassword((int) $user['id'], password_hash($generated, PASSWORD_DEFAULT), true);
        AuditLogger::log('users.reset_password', 'users', (int) $user['id'], null, 'Contrasena restablecida (cambio obligatorio)');

        $this->flash('success', 'Contrasena restablecida. Asigna esta contraseña temporal al usuario (se muestra una sola vez): ' . $generated);
        return $this->redirect('/admin/usuarios/' . $user['id'] . '/editar');
    }

    /** @return array<int> */
    private function roleIdsFrom(Request $request): array
    {
        $raw = $request->array('roles', []);
        return array_values(array_unique(array_map('intval', array_filter($raw, 'is_scalar'))));
    }

    private static function randomPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 14; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
