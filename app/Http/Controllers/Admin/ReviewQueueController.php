<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\AuditLogger;

/**
 * Bandejas de revision editorial (6.10): pendientes, asignados,
 * observados, aprobados, publicados y por vencer; asignacion de
 * revisores y observaciones de campo.
 */
final class ReviewQueueController extends AdminController
{
    public const TABS = [
        'pendientes' => 'Pendientes',
        'asignados' => 'Mis asignaciones',
        'observados' => 'Observados',
        'aprobados' => 'Aprobados',
        'publicados' => 'Publicados',
        'por-vencer' => 'Por vencer',
    ];

    public function index(Request $request): Response
    {
        Authorization::authorize('items.review');
        $tab = $request->str('bandeja', 'pendientes');
        if (!array_key_exists($tab, self::TABS)) {
            $tab = 'pendientes';
        }
        $userId = (int) Auth::id();
        $instId = TenantContext::institutionId();

        $joins = '';
        $where = ['i.institution_id = ?', 'i.deleted_at IS NULL'];
        $params = [$instId];
        switch ($tab) {
            case 'pendientes':
                $where[] = "i.status IN ('SUBMITTED','UNDER_REVIEW')";
                break;
            case 'asignados':
                $joins .= ' JOIN review_assignments ra ON ra.item_id = i.id AND ra.status = ? AND ra.reviewer_id = ?';
                $params = ['assigned', $userId, $instId];
                break;
            case 'observados':
                $where[] = "i.status = 'CHANGES_REQUESTED'";
                break;
            case 'aprobados':
                $where[] = "i.status = 'APPROVED'";
                break;
            case 'publicados':
                $where[] = "i.status = 'PUBLISHED'";
                break;
            case 'por-vencer':
                $joins .= " JOIN review_assignments ra ON ra.item_id = i.id AND ra.status = 'assigned'
                            AND ra.due_at IS NOT NULL AND ra.due_at <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)";
                break;
        }
        $whereSql = implode(' AND ', $where);

        $rows = Database::select(
            "SELECT i.id, i.public_id, i.status, i.submitted_at, i.updated_at, i.published_at,
                    v.title, c.name AS collection_name, cm.name AS community_name,
                    u.full_name AS creator_name
             FROM items i{$joins}
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN collections c ON c.id = i.collection_id
             LEFT JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE {$whereSql}
             ORDER BY i.submitted_at IS NULL, i.submitted_at ASC, i.updated_at DESC
             LIMIT 200",
            $params
        );

        // Asignaciones activas de los recursos listados.
        $assignments = [];
        if ($rows !== []) {
            $ids = implode(',', array_map('intval', array_column($rows, 'id')));
            $assignments = Database::select(
                "SELECT ra.item_id, ra.due_at, ra.id AS assignment_id, ra.reviewer_id, u.full_name AS reviewer_name
                 FROM review_assignments ra JOIN users u ON u.id = ra.reviewer_id
                 WHERE ra.status = 'assigned' AND ra.item_id IN ({$ids}) AND ra.institution_id = ?
                 ORDER BY ra.due_at IS NULL, ra.due_at",
                [$instId]
            );
        }
        $byItem = [];
        foreach ($assignments as $a) {
            $byItem[(int) $a['item_id']] = $a;
        }
        foreach ($rows as $i => $row) {
            $rows[$i]['assignment'] = $byItem[(int) $row['id']] ?? null;
        }

        $counts = Database::selectOne(
            "SELECT
                SUM(i.status IN ('SUBMITTED','UNDER_REVIEW')) AS pendientes,
                SUM(i.status = 'CHANGES_REQUESTED') AS observados,
                SUM(i.status = 'APPROVED') AS aprobados,
                SUM(i.status = 'PUBLISHED') AS publicados
             FROM items i WHERE i.institution_id = ? AND i.deleted_at IS NULL",
            [$instId]
        );
        $assignedToMe = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM review_assignments ra JOIN items i ON i.id = ra.item_id AND i.deleted_at IS NULL
             WHERE ra.status = 'assigned' AND ra.reviewer_id = ? AND ra.institution_id = ?",
            [$userId, $instId]
        )['n'];
        $dueSoon = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM review_assignments ra JOIN items i ON i.id = ra.item_id AND i.deleted_at IS NULL
             WHERE ra.status = 'assigned' AND ra.institution_id = ?
               AND ra.due_at IS NOT NULL AND ra.due_at <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)",
            [$instId]
        )['n'];

        $reviewers = [];
        if (Authorization::can('items.review')) {
            $reviewers = Database::select(
                "SELECT DISTINCT u.id, u.full_name
                 FROM users u
                 JOIN user_institution_memberships m ON m.user_id = u.id AND m.institution_id = ? AND m.status = 'active'
                 JOIN membership_roles mr ON mr.membership_id = m.id
                 JOIN role_permissions rp ON rp.role_id = mr.role_id
                 JOIN permissions p ON p.id = rp.permission_id AND p.permission_key = 'items.review'
                 WHERE u.status = 1 AND u.deleted_at IS NULL
                 ORDER BY u.full_name",
                [$instId]
            );
        }

        return $this->view('admin/revision/index', [
            'pageTitle' => 'Revisión',
            'tab' => $tab,
            'rows' => $rows,
            'reviewers' => $reviewers,
            'counts' => [
                'pendientes' => (int) ($counts['pendientes'] ?? 0),
                'mis-asignaciones' => $assignedToMe,
                'observados' => (int) ($counts['observados'] ?? 0),
                'aprobados' => (int) ($counts['aprobados'] ?? 0),
                'publicados' => (int) ($counts['publicados'] ?? 0),
                'por-vencer' => $dueSoon,
            ],
        ]);
    }

    /** POST /admin/revision/asignar */
    public function assign(Request $request): Response
    {
        Authorization::authorize('items.review');
        $itemId = $request->int('item_id', 0);
        $reviewerId = $request->int('reviewer_id', 0);
        $dueAt = $request->str('due_at');

        $item = Database::selectOne(
            'SELECT id, status FROM items WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
            [$itemId, TenantContext::institutionId()]
        );
        if ($item === null) {
            throw new HttpException(404, 'Recurso no encontrado.');
        }
        if ($reviewerId <= 0) {
            throw new HttpException(422, 'Seleccione un revisor.', ['reviewer_id' => 'El revisor es obligatorio.']);
        }
        $reviewer = Database::selectOne(
            'SELECT id, full_name FROM users WHERE id = ? AND status = 1 AND deleted_at IS NULL',
            [$reviewerId]
        );
        if ($reviewer === null) {
            throw new HttpException(422, 'Revisor inválido.', ['reviewer_id' => 'Seleccione un revisor activo.']);
        }
        if ($dueAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueAt)) {
            throw new HttpException(422, 'Fecha de vencimiento inválida.', ['due_at' => 'Use el formato AAAA-MM-DD.']);
        }

        $existing = Database::selectOne(
            "SELECT id FROM review_assignments WHERE item_id = ? AND status = 'assigned' AND institution_id = ? LIMIT 1",
            [$itemId, TenantContext::institutionId()]
        );
        if ($existing !== null) {
            Database::statement(
                'UPDATE review_assignments SET reviewer_id = ?, assigned_by = ?, due_at = ? WHERE id = ?',
                [$reviewerId, Auth::id(), $dueAt !== '' ? $dueAt : null, (int) $existing['id']]
            );
        } else {
            Database::statement(
                "INSERT INTO review_assignments (institution_id, item_id, reviewer_id, assigned_by, stage, status, due_at, created_at)
                 VALUES (?, ?, ?, ?, 'review', 'assigned', ?, UTC_TIMESTAMP())",
                [TenantContext::institutionId(), $itemId, $reviewerId, Auth::id(), $dueAt !== '' ? $dueAt : null]
            );
        }
        AuditLogger::log('review.assign', 'items', $itemId, null, 'Revisor: ' . $reviewer['full_name'] . ($dueAt !== '' ? ' | vence: ' . $dueAt : ''));

        try {
            (new \App\Services\NotificationService())->notify(
                $reviewerId,
                'item.assigned',
                'Se le asignó la revisión del recurso #' . $itemId . ($dueAt !== '' ? ' con vencimiento ' . $dueAt : '') . '.',
                '/admin/recursos/' . $itemId
            );
        } catch (\Throwable) {
        }

        $this->flash('success', 'Recurso asignado a ' . $reviewer['full_name'] . '.');
        return $this->redirect('/admin/revision?bandeja=' . urlencode($request->str('desde_tab', 'asignados')));
    }

    /** POST /admin/revision/{id}/observaciones — observacion de campo / comentario interno. */
    public function comment(Request $request): Response
    {
        Authorization::authorize('items.review');
        $itemId = (int) $request->route('id');
        $item = Database::selectOne(
            'SELECT id, status FROM items WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
            [$itemId, TenantContext::institutionId()]
        );
        if ($item === null) {
            throw new HttpException(404, 'Recurso no encontrado.');
        }
        $text = trim($request->str('comment'));
        if ($text === '') {
            throw new HttpException(422, 'La observación no puede estar vacía.', ['comment' => 'Escriba una observación.']);
        }
        if (mb_strlen($text) > 4000) {
            throw new HttpException(422, 'Observación demasiado larga.', ['comment' => 'Máximo 4000 caracteres.']);
        }
        $visibility = $request->str('visibility', 'author') === 'internal' ? 'internal' : 'author';

        Database::statement(
            'INSERT INTO review_comments (item_id, field_key, comment, visibility, created_by, created_at)
             VALUES (?, NULL, ?, ?, ?, UTC_TIMESTAMP())',
            [$itemId, mb_substr($text, 0, 4000), $visibility, Auth::id()]
        );
        AuditLogger::log('review.comment', 'items', $itemId, null, '[' . $visibility . '] ' . mb_substr($text, 0, 200));

        if ($visibility === 'author') {
            try {
                $owner = Database::selectOne('SELECT created_by FROM items WHERE id = ?', [$itemId]);
                if ($owner !== null && (int) $owner['created_by'] > 0) {
                    (new \App\Services\NotificationService())->notify(
                        (int) $owner['created_by'],
                        'item.commented',
                        'Nueva observación en el recurso #' . $itemId . ': ' . mb_substr($text, 0, 140),
                        '/admin/recursos/' . $itemId
                    );
                }
            } catch (\Throwable) {
            }
        }

        $this->flash('success', 'Observación registrada.');
        return $this->redirect('/admin/recursos/' . $itemId);
    }
}
