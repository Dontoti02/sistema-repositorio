<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

/**
 * Escritorio general (6.1): indicadores operativos reales.
 */
final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('dashboard.view');
        $instId = TenantContext::institutionId();

        $byStatus = Database::select(
            'SELECT status, COUNT(*) AS total FROM items
             WHERE institution_id = ? AND deleted_at IS NULL GROUP BY status',
            [$instId]
        );
        $statusMap = array_fill_keys(
            ['DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'CHANGES_REQUESTED', 'APPROVED', 'REJECTED', 'PUBLISHED', 'WITHDRAWN', 'ARCHIVED'],
            0
        );
        foreach ($byStatus as $row) {
            $statusMap[$row['status']] = (int) $row['total'];
        }

        $pending = $statusMap['SUBMITTED'] + $statusMap['UNDER_REVIEW'] + $statusMap['CHANGES_REQUESTED'];

        $users = Database::selectOne(
            'SELECT COUNT(*) AS total FROM users u
             JOIN user_institution_memberships m ON m.user_id = u.id
             WHERE m.institution_id = ? AND m.status = ? AND u.deleted_at IS NULL',
            [$instId, 'active']
        );
        $structure = Database::selectOne(
            'SELECT (SELECT COUNT(*) FROM communities WHERE institution_id = ? AND deleted_at IS NULL) AS communities,
                    (SELECT COUNT(*) FROM collections WHERE institution_id = ? AND deleted_at IS NULL) AS collections',
            [$instId, $instId]
        );
        $storage = Database::selectOne(
            'SELECT COALESCE(SUM(file_size),0) AS bytes, COUNT(*) AS total FROM files
             WHERE institution_id = ? AND deleted_at IS NULL',
            [$instId]
        );

        $recentItems = Database::select(
            'SELECT i.id, i.public_id, i.status, i.updated_at, v.title
             FROM items i LEFT JOIN item_versions v ON v.id = i.current_version_id
             WHERE i.institution_id = ? AND i.deleted_at IS NULL
             ORDER BY i.updated_at DESC LIMIT 8',
            [$instId]
        );
        $activity = Database::select(
            'SELECT h.from_status, h.to_status, h.created_at, h.reason, v.title, u.full_name
             FROM item_status_history h
             JOIN items i ON i.id = h.item_id
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN users u ON u.id = h.actor_id
             WHERE h.institution_id = ?
             ORDER BY h.created_at DESC LIMIT 8',
            [$instId]
        );

        return $this->view('admin/dashboard', [
            'pageTitle' => 'Escritorio',
            'statusMap' => $statusMap,
            'pending' => $pending,
            'usersTotal' => (int) ($users['total'] ?? 0),
            'communities' => (int) ($structure['communities'] ?? 0),
            'collections' => (int) ($structure['collections'] ?? 0),
            'storageBytes' => (int) ($storage['bytes'] ?? 0),
            'filesTotal' => (int) ($storage['total'] ?? 0),
            'recentItems' => $recentItems,
            'activity' => $activity,
        ]);
    }
}
