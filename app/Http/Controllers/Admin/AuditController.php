<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Authorization;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

/**
 * Bitacora y auditoria (6.18): consulta restringida con filtros,
 * paginacion y exportacion. Nunca expone secretos ni tokens.
 */
final class AuditController extends AdminController
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        Authorization::authorize('audit.view');
        $filters = [
            'q' => $request->str('q'),
            'actor' => $request->int('actor', 0),
            'entidad' => $request->str('entidad'),
            'desde' => $request->str('desde'),
            'hasta' => $request->str('hasta'),
            'pagina' => max(1, $request->int('pagina', 1)),
        ];

        $where = ['a.institution_id = ?'];
        $params = [TenantContext::institutionId()];
        if ($filters['q'] !== '') {
            $where[] = '(a.action LIKE ? OR a.after_summary LIKE ? OR a.before_summary LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($filters['actor'] > 0) {
            $where[] = 'a.actor_id = ?';
            $params[] = $filters['actor'];
        }
        if ($filters['entidad'] !== '') {
            $where[] = 'a.entity_type = ?';
            $params[] = $filters['entidad'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['desde'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['desde'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['hasta'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['hasta'] . ' 23:59:59';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::selectOne(
            "SELECT COUNT(*) AS total FROM audit_logs a WHERE {$whereSql}",
            $params
        )['total'];
        $offset = ($filters['pagina'] - 1) * self::PER_PAGE;
        $rows = Database::select(
            "SELECT a.id, a.action, a.entity_type, a.entity_id, a.before_summary, a.after_summary,
                    a.ip_masked, a.created_at, u.full_name AS actor_name, u.email AS actor_email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_id
             WHERE {$whereSql}
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );

        $actors = Database::select(
            'SELECT DISTINCT u.id, u.full_name FROM audit_logs a JOIN users u ON u.id = a.actor_id
             WHERE a.institution_id = ? ORDER BY u.full_name LIMIT 200',
            [TenantContext::institutionId()]
        );
        $entityTypes = Database::select(
            'SELECT DISTINCT entity_type FROM audit_logs WHERE institution_id = ? AND entity_type <> "" ORDER BY entity_type',
            [TenantContext::institutionId()]
        );

        return $this->view('admin/audit/index', [
            'pageTitle' => 'Bitácora y auditoría',
            'rows' => $rows,
            'total' => $total,
            'page' => $filters['pagina'],
            'filters' => $filters,
            'actors' => $actors,
            'entityTypes' => array_column($entityTypes, 'entity_type'),
        ]);
    }

    /** GET /admin/auditoria/exportar — CSV de la consulta filtrada. */
    public function export(Request $request): Response
    {
        Authorization::authorize('audit.view');
        $filters = [
            'q' => $request->str('q'),
            'actor' => $request->int('actor', 0),
            'entidad' => $request->str('entidad'),
            'desde' => $request->str('desde'),
            'hasta' => $request->str('hasta'),
        ];
        $where = ['a.institution_id = ?'];
        $params = [TenantContext::institutionId()];
        if ($filters['q'] !== '') {
            $where[] = '(a.action LIKE ? OR a.after_summary LIKE ? OR a.before_summary LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($filters['actor'] > 0) {
            $where[] = 'a.actor_id = ?';
            $params[] = $filters['actor'];
        }
        if ($filters['entidad'] !== '') {
            $where[] = 'a.entity_type = ?';
            $params[] = $filters['entidad'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['desde'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['desde'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['hasta'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['hasta'] . ' 23:59:59';
        }
        $rows = Database::select(
            'SELECT a.created_at, u.full_name AS actor_name, a.action, a.entity_type, a.entity_id,
                    a.before_summary, a.after_summary, a.ip_masked
             FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY a.created_at DESC, a.id DESC LIMIT 20000',
            $params
        );

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Fecha (UTC)', 'Usuario', 'Acción', 'Entidad', 'ID', 'Detalle previo', 'Detalle nuevo', 'IP enmascarada'], ';');
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['created_at'],
                $row['actor_name'] ?? 'Sistema',
                $row['action'],
                $row['entity_type'],
                $row['entity_id'] ?? '',
                $row['before_summary'] ?? '',
                $row['after_summary'] ?? '',
                $row['ip_masked'] ?? '',
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="auditoria_' . gmdate('Ymd_His') . '.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
