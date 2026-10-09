<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Database;
use App\Core\HttpException;

/**
 * Motor de autorización de archivos: resuelve registro + archivo + tiempo +
 * rol + permiso. Se invoca en CADA solicitud de descarga/visualización.
 */
final class AccessControlService
{
    /**
     * ¿Puede ver los metadatos del registro?
     *
     * @param array<string,mixed> $item
     */
    public function canViewMetadata(array $item): bool
    {
        if ($item['status'] === 'PUBLISHED') {
            return true;
        }
        if (!Auth::check()) {
            return false;
        }
        if (Authorization::can('items.read_all')) {
            return Authorization::withinInstitution((int) $item['institution_id']);
        }
        return (int) $item['created_by'] === (int) Auth::id() && Authorization::can('items.read_own');
    }

    /**
     * ¿Puede descargar/ver el archivo? Devuelve motivo de denegación o null.
     *
     * @param array<string,mixed> $item
     * @param array<string,mixed> $file
     * @return string|null null = permitido; cadena = motivo de denegación
     */
    public function denyReason(array $item, array $file): ?string
    {
        $instOk = Authorization::withinInstitution((int) $item['institution_id']);
        if (!$instOk) {
            // Sin filtrar información: mismo mensaje que inexistente.
            return 'no_encontrado';
        }

        $published = $item['status'] === 'PUBLISHED';
        $isOwner = Auth::check() && (int) $item['created_by'] === Auth::id();

        // 1) Estado editorial: nada de borradores/en revisión para el público.
        if (!$published) {
            if (Auth::check() && (Authorization::can('items.read_all') || ($isOwner && Authorization::can('items.read_own')))) {
                return null;
            }
            if (Auth::check() && $this->isAssignedReviewer((int) $item['id'])) {
                return null;
            }
            return 'no_encontrado';
        }

        // 2) Staff con permiso de descarga restringida.
        if (Auth::check() && Authorization::can('files.download_restricted') && Authorization::can('items.read_all')) {
            return null;
        }
        if (Auth::check() && $isOwner && Authorization::can('items.read_own')) {
            return null;
        }

        // 3) Embargo vigente: denegado incluso con URL/ID conocidos.
        if ($this->embargoActive($item)) {
            return 'embargado';
        }

        // 4) Nivel de acceso del registro.
        return match ($item['access_level']) {
            'open' => null,
            'metadata_only' => 'solo_metadatos',
            'embargoed' => $this->embargoActive($item) ? 'embargado' : null,
            'restricted' => $this->hasExplicitGrant((int) $item['id']) ? null : 'restringido',
            'private' => 'no_encontrado',
            default => 'restringido',
        };
    }

    /**
     * Lanza la excepción HTTP apropiada si el acceso está denegado.
     *
     * @param array<string,mixed> $item
     * @param array<string,mixed> $file
     */
    public function authorizeDownload(array $item, array $file): void
    {
        $reason = $this->denyReason($item, $file);
        if ($reason === null) {
            return;
        }
        // Auditoría de intentos sobre archivos no públicos.
        if ($reason !== 'no_encontrado') {
            $this->auditDenied($item, $file, $reason);
        }
        throw match ($reason) {
            'embargado' => new HttpException(403, 'Este recurso está bajo embargo hasta ' . format_date_es((string) ($item['embargo_until'] ?? ''), 'd \d\e F \d\e Y \a\l\l\as H:i') . ' (hora del Perú).'),
            'solo_metadatos' => new HttpException(403, 'Este recurso publica solo sus metadatos; el archivo no está disponible para descarga.'),
            'restringido' => new HttpException(403, 'El archivo tiene acceso restringido. Contacte con el responsable del repositorio.'),
            default => new HttpException(404, 'El archivo solicitado no existe.'),
        };
    }

    /**
     * ¿Hay embargo activo para el registro (calculado en servidor, UTC)?
     *
     * @param array<string,mixed> $item
     */
    public function embargoActive(array $item): bool
    {
        if (empty($item['embargo_until'])) {
            // Consulta explícita de embargos activos (puede existir sin reflejarse en items).
            $row = Database::selectOne(
                "SELECT embargo_until FROM embargoes
                 WHERE item_id = ? AND status = 'active' AND embargo_until > UTC_TIMESTAMP()
                 ORDER BY embargo_until DESC LIMIT 1",
                [(int) $item['id']]
            );
            return $row !== null;
        }
        return strtotime((string) $item['embargo_until']) > time();
    }

    /** ¿Tiene el usuario una concesión explícita vigente? */
    private function hasExplicitGrant(int $itemId): bool
    {
        if (!Auth::check()) {
            return false;
        }
        $userId = Auth::id();
        $roles = Auth::roles();
        $roleIds = Database::select(
            'SELECT r.id FROM roles r
             JOIN membership_roles mr ON mr.role_id = r.id
             JOIN user_institution_memberships m ON m.id = mr.membership_id
             WHERE m.user_id = ? AND m.institution_id = ?',
            [$userId, \App\Core\TenantContext::institutionId()]
        );
        $conds = ['(subject_type = ? AND subject_id = ?)'];
        $params = ['user', $userId];
        if ($roleIds !== []) {
            $conds[] = '(subject_type = ? AND subject_id IN (' . implode(',', array_fill(0, count($roleIds), '?')) . '))';
            $params[] = 'role';
            foreach ($roleIds as $r) {
                $params[] = (int) $r['id'];
            }
        }
        $sql = 'SELECT COUNT(*) AS total FROM access_grants
                WHERE item_id = ? AND ' . implode(' OR ', $conds) . '
                  AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())';
        array_unshift($params, $itemId);
        $row = Database::selectOne($sql, $params);
        return (int) ($row['total'] ?? 0) > 0;
    }

    private function isAssignedReviewer(int $itemId): bool
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) AS total FROM review_assignments
             WHERE item_id = ? AND reviewer_id = ? AND status IN ('assigned','in_progress')",
            [$itemId, Auth::id() ?? 0]
        );
        return (int) ($row['total'] ?? 0) > 0;
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string,mixed> $file
     */
    private function auditDenied(array $item, array $file, string $reason): void
    {
        try {
            Database::statement(
                'INSERT INTO audit_logs (institution_id, actor_id, action, entity_type, entity_id, before_summary, ip_masked, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    (int) $item['institution_id'],
                    Auth::id(),
                    'files.access_denied',
                    'file',
                    (int) $file['id'],
                    json_encode(['motivo' => $reason], JSON_UNESCAPED_UNICODE),
                    substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 40),
                ]
            );
        } catch (\Throwable) {
            // No interrumpe la respuesta al usuario.
        }
    }
}
