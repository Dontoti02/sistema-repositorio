<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Database;
use App\Core\HttpException;

/**
 * Ciclo de vida del documento (seccion 8): matriz de transiciones,
 * invariantes y registro de historial con actor y motivo.
 */
final class WorkflowService
{
    /** @var array<string,array<int,string>> estado -> destinos permitidos */
    public const MATRIX = [
        'DRAFT' => ['SUBMITTED'],
        'SUBMITTED' => ['UNDER_REVIEW', 'CHANGES_REQUESTED', 'REJECTED'],
        'UNDER_REVIEW' => ['APPROVED', 'CHANGES_REQUESTED', 'REJECTED'],
        'CHANGES_REQUESTED' => ['SUBMITTED', 'DRAFT'],
        'APPROVED' => ['PUBLISHED', 'UNDER_REVIEW'],
        'REJECTED' => ['SUBMITTED', 'ARCHIVED'],
        'PUBLISHED' => ['WITHDRAWN', 'ARCHIVED', 'UNDER_REVIEW'],
        'WITHDRAWN' => ['PUBLISHED', 'ARCHIVED'],
        'ARCHIVED' => ['WITHDRAWN'],
    ];

    /** @var array<string,string> permiso requerido por estado destino */
    public const PERMISSIONS = [
        'SUBMITTED' => 'items.submit',
        'UNDER_REVIEW' => 'items.review',
        'CHANGES_REQUESTED' => 'items.review',
        'APPROVED' => 'items.approve',
        'REJECTED' => 'items.review',
        'PUBLISHED' => 'items.publish',
        'WITHDRAWN' => 'items.unpublish',
        'ARCHIVED' => 'items.archive',
        'DRAFT' => 'items.edit_all',
    ];

    /** @return array<int,string> */
    public static function allowedFrom(string $from): array
    {
        return self::MATRIX[$from] ?? [];
    }

    /**
     * Valida y ejecuta una transicion. Lanza HttpException ante cualquier violacion.
     */
    public function transition(int $itemId, string $from, string $to, string $reason = ''): void
    {
        if (!isset(self::MATRIX[$from]) || !in_array($to, self::MATRIX[$from], true)) {
            throw new HttpException(422, "Transicion no permitida: {$from} → {$to}.", [
                'estado' => 'La transicion solicitada no esta permitida desde el estado actual.',
            ]);
        }

        $permission = self::PERMISSIONS[$to] ?? 'items.review';
        if (!Authorization::can($permission)) {
            throw new HttpException(403, 'No tiene permiso para esta accion (' . $permission . ').');
        }

        if (in_array($to, ['CHANGES_REQUESTED', 'REJECTED'], true) && trim($reason) === '') {
            throw new HttpException(422, 'Debe indicar el motivo.', ['reason' => 'El motivo es obligatorio para esta accion.']);
        }

        // Invariante: no publicar sin metadatos obligatorios.
        if ($to === 'PUBLISHED') {
            $this->assertPublishable($itemId);
        }

        $reason = trim($reason);
        $reason = $reason === '' ? null : $reason;
        Database::transaction(function () use ($itemId, $from, $to, $reason): void {
            Database::statement(
                "UPDATE items SET status = ?, updated_at = UTC_TIMESTAMP(),
                 submitted_at = CASE WHEN ? = 'SUBMITTED' THEN UTC_TIMESTAMP() ELSE submitted_at END,
                 published_at = CASE WHEN ? = 'PUBLISHED' AND published_at IS NULL THEN UTC_TIMESTAMP() ELSE published_at END,
                 withdrawn_at = CASE WHEN ? = 'WITHDRAWN' THEN UTC_TIMESTAMP() ELSE withdrawn_at END,
                 archived_at = CASE WHEN ? = 'ARCHIVED' THEN UTC_TIMESTAMP() ELSE archived_at END
                 WHERE id = ?",
                [$to, $to, $to, $to, $to, $itemId]
            );
            Database::statement(
                'INSERT INTO item_status_history (institution_id, item_id, from_status, to_status, actor_id, reason, version_number, created_at)
                 SELECT i.institution_id, i.id, ?, ?, ?, ?, v.version_number, UTC_TIMESTAMP()
                 FROM items i LEFT JOIN item_versions v ON v.id = i.current_version_id
                 WHERE i.id = ?',
                [$from, $to, Auth::id(), $reason, $itemId]
            );
            // Coherencia: la version vigente refleja el estado del recurso.
            Database::statement(
                'UPDATE item_versions v JOIN items i ON i.current_version_id = v.id
                 SET v.status = ? WHERE i.id = ?',
                [$to, $itemId]
            );
            // Los archivos de un recurso publicado quedan visibles para descarga directa.
            Database::statement(
                'UPDATE files SET visibility = ? WHERE item_id = ? AND deleted_at IS NULL',
                [$to === 'PUBLISHED' ? 'public' : 'private', $itemId]
            );
            // Cerrar asignaciones de revision al salir del circuito de evaluacion.
            if (!in_array($to, ['SUBMITTED', 'UNDER_REVIEW', 'CHANGES_REQUESTED'], true)) {
                Database::statement(
                    "UPDATE review_assignments SET status = 'completed', completed_at = UTC_TIMESTAMP()
                     WHERE item_id = ? AND status = 'assigned'",
                    [$itemId]
                );
            }
        });

        AuditLogger::log('items.transition', 'items', $itemId, $from, $to . ($reason !== null ? ' | motivo: ' . $reason : ''));

        $this->notify($itemId, $to, $reason);
    }

    /** Avisos internos segun el estado destino (6.15). */
    private function notify(int $itemId, string $to, ?string $reason): void
    {
        try {
            $service = new NotificationService();
            $item = Database::selectOne(
                'SELECT i.id, i.created_by, i.public_id, v.title FROM items i
                 LEFT JOIN item_versions v ON v.id = i.current_version_id WHERE i.id = ?',
                [$itemId]
            );
            if ($item === null) {
                return;
            }
            $owner = (int) ($item['created_by'] ?? 0);
            $ref = 'Recurso ' . $item['public_id'] . ' — ' . mb_substr((string) ($item['title'] ?? ''), 0, 80);
            $link = '/admin/recursos/' . $itemId;
            $motivo = $reason !== null && $reason !== '' ? ' Motivo: ' . $reason : '';

            switch ($to) {
                case 'SUBMITTED':
                    $service->notifyPermission('items.review', 'item.submitted', $ref . ' fue enviado a revisión.', $link);
                    break;
                case 'CHANGES_REQUESTED':
                    if ($owner > 0) {
                        $service->notify($owner, 'item.changes_requested', $ref . ': se solicitaron cambios.' . $motivo, $link);
                    }
                    break;
                case 'APPROVED':
                    if ($owner > 0) {
                        $service->notify($owner, 'item.approved', $ref . ' fue aprobado.' . $motivo, $link);
                    }
                    break;
                case 'REJECTED':
                    if ($owner > 0) {
                        $service->notify($owner, 'item.rejected', $ref . ' fue rechazado.' . $motivo, $link);
                    }
                    break;
                case 'PUBLISHED':
                    if ($owner > 0) {
                        $service->notify($owner, 'item.published', $ref . ' ya está publicado en el repositorio.', $link);
                    }
                    break;
            }
        } catch (\Throwable) {
            // Las notificaciones jamás bloquean el flujo editorial.
        }
    }

    /**
     * Invariante 8.3: todo publicado debe tener titulo y coherencia de acceso.
     */
    private function assertPublishable(int $itemId): void
    {
        $row = Database::selectOne(
            "SELECT v.title, i.access_level,
                    (SELECT COUNT(*) FROM files f WHERE f.item_id = i.id AND f.deleted_at IS NULL) AS files_count
             FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             WHERE i.id = ?",
            [$itemId]
        );
        if ($row === null) {
            throw new HttpException(404, 'Recurso no encontrado.');
        }
        if (trim((string) ($row['title'] ?? '')) === '') {
            throw new HttpException(422, 'El recurso no tiene titulo.', ['title' => 'Defina un titulo antes de publicar.']);
        }
        if ((int) $row['files_count'] === 0 && $row['access_level'] !== 'metadata_only') {
            throw new HttpException(422, 'El recurso no tiene archivos.', [
                'files' => 'Suba al menos un archivo o defina el nivel de acceso «solo metadatos».',
            ]);
        }
    }
}
