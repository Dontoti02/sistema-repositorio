<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Domain\Identity\UserAdminRepository;

/**
 * Alta de usuarios desde CLI o procesos internos (mismas reglas
 * que el panel: correo único, roles por slug de la institución).
 */
final class UserService
{
    /**
     * @return array<string,mixed> usuario creado
     */
    public function create(
        string $fullName,
        string $email,
        string $password,
        string $roleSlug,
        int $institutionId,
        bool $force = false
    ): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException(422, 'Correo inválido.');
        }
        if (strlen($password) < 10) {
            throw new HttpException(422, 'La contraseña debe tener al menos 10 caracteres.');
        }
        $repo = new UserAdminRepository($institutionId);
        if ($repo->emailExists($email)) {
            if (!$force) {
                throw new HttpException(422, "Ya existe un usuario con el correo {$email}.");
            }
            $existing = Database::selectOne('SELECT id, full_name FROM users WHERE email = ?', [$email]);
            return ['id' => (int) $existing['id'], 'email' => $email, 'reused' => true];
        }

        $role = Database::selectOne(
            'SELECT id FROM roles WHERE institution_id = ? AND slug = ? LIMIT 1',
            [$institutionId, $roleSlug]
        );
        if ($role === null) {
            throw new HttpException(422, "El rol «{$roleSlug}» no existe en esta institución.");
        }

        $userId = $repo->create(
            trim($fullName),
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            [(int) $role['id']]
        );
        AuditLogger::log('users.create', 'users', $userId, null, $fullName . ' <' . $email . '> rol: ' . $roleSlug);

        return ['id' => $userId, 'email' => $email, 'reused' => false];
    }
}
