<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Authorization;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

/**
 * Reportes y estadisticas (6.14): filtros, indicadores, desgloses
 * y exportacion CSV con las mismas condiciones.
 */
final class ReportController extends AdminController
{
    /** @return array{0:string,1:array<int,mixed>} */
    private function filters(Request $request): array
    {
        $instId = TenantContext::institutionId();
        $where = ['i.institution_id = ?', 'i.deleted_at IS NULL'];
        $params = [$instId];

        $desde = $request->str('desde');
        $hasta = $request->str('hasta');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $where[] = 'i.created_at >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $where[] = 'i.created_at <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $estado = $request->str('estado');
        if ($estado !== '') {
            $where[] = 'i.status = ?';
            $params[] = $estado;
        }
        $comunidad = $request->int('comunidad', 0);
        if ($comunidad > 0) {
            $where[] = 'c.community_id = ?';
            $params[] = $comunidad;
        }
        $coleccion = $request->int('coleccion', 0);
        if ($coleccion > 0) {
            $where[] = 'i.collection_id = ?';
            $params[] = $coleccion;
        }
        $tipo = $request->int('tipo', 0);
        if ($tipo > 0) {
            $where[] = 'i.document_type_id = ?';
            $params[] = $tipo;
        }
        $programa = $request->int('programa', 0);
        if ($programa > 0) {
            $where[] = 'i.program_id = ?';
            $params[] = $programa;
        }
        return [implode(' AND ', $where), $params];
    }

    private function eventRange(Request $request): array
    {
        $desde = $request->str('desde');
        $hasta = $request->str('hasta');
        $conds = [];
        $params = [];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $conds[] = 'event_at >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $conds[] = 'event_at <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        return [$conds === [] ? '1=1' : implode(' AND ', $conds), $params];
    }

    public function index(Request $request): Response
    {
        Authorization::authorize('reports.view');
        [$whereSql, $params] = $this->filters($request);
        [$rangeSql, $rangeParams] = $this->eventRange($request);
        $instId = TenantContext::institutionId();

        $summary = Database::selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(i.status = 'PUBLISHED') AS publicados,
                    SUM(i.status IN ('SUBMITTED','UNDER_REVIEW','CHANGES_REQUESTED')) AS pendientes,
                    SUM(i.status = 'REJECTED') AS rechazados
             FROM items i
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql}",
            $params
        );

        $storage = Database::selectOne(
            'SELECT COUNT(*) AS files, COALESCE(SUM(file_size), 0) AS bytes FROM files WHERE institution_id = ? AND deleted_at IS NULL',
            [$instId]
        );
        $downloads = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM download_events WHERE institution_id = ? AND {$rangeSql}",
            array_merge([$instId], $rangeParams)
        )['n'];
        $views = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM item_view_events WHERE institution_id = ? AND {$rangeSql}",
            array_merge([$instId], $rangeParams)
        )['n'];
        $rejections = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM item_status_history
             WHERE institution_id = ? AND to_status = 'REJECTED'
               AND created_at >= COALESCE(?, '1000-01-01') AND created_at <= COALESCE(?, '9999-12-31')",
            array_merge(
                [$instId],
                [
                    preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->str('desde')) ? $request->str('desde') . ' 00:00:00' : null,
                    preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->str('hasta')) ? $request->str('hasta') . ' 23:59:59' : null,
                ]
            )
        )['n'];
        $reviewDays = Database::selectOne(
            "SELECT AVG(DATEDIFF(i.published_at, i.submitted_at)) AS dias
             FROM items i
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql} AND i.published_at IS NOT NULL AND i.submitted_at IS NOT NULL",
            $params
        );

        $byYear = Database::select(
            "SELECT YEAR(v.issued_at) AS anio, COUNT(*) AS total
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql} AND v.issued_at IS NOT NULL
             GROUP BY YEAR(v.issued_at) ORDER BY anio DESC LIMIT 30",
            $params
        );
        $byCollection = Database::select(
            "SELECT cm.name AS comunidad, c.name AS coleccion, COUNT(*) AS total,
                    SUM(i.status = 'PUBLISHED') AS publicados
             FROM items i
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             WHERE {$whereSql}
             GROUP BY cm.name, c.name ORDER BY total DESC LIMIT 15",
            $params
        );
        $byType = Database::select(
            "SELECT COALESCE(dt.name, 'Sin tipo') AS tipo, COUNT(*) AS total
             FROM items i
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql}
             GROUP BY COALESCE(dt.name, 'Sin tipo') ORDER BY total DESC LIMIT 15",
            $params
        );
        $topAuthors = Database::select(
            "SELECT a.full_name, COUNT(DISTINCT i.id) AS total
             FROM item_authors a
             JOIN items i ON i.id = a.item_id
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql} AND i.status = 'PUBLISHED'
             GROUP BY a.full_name ORDER BY total DESC, a.full_name LIMIT 15",
            $params
        );
        $byStatus = Database::select(
            "SELECT i.status, COUNT(*) AS total
             FROM items i
             LEFT JOIN collections c ON c.id = i.collection_id
             WHERE {$whereSql}
             GROUP BY i.status ORDER BY total DESC",
            $params
        );
        $topSearches = Database::select(
            'SELECT term, searches, last_at FROM search_aggregates
             WHERE institution_id = ? ORDER BY searches DESC LIMIT 10',
            [$instId]
        );

        $communities = Database::select(
            'SELECT id, name FROM communities WHERE institution_id = ? AND deleted_at IS NULL ORDER BY name',
            [$instId]
        );
        $collections = Database::select(
            'SELECT c.id, c.name, cm.name AS community_name FROM collections c
             JOIN communities cm ON cm.id = c.community_id
             WHERE c.institution_id = ? AND c.deleted_at IS NULL ORDER BY cm.name, c.name',
            [$instId]
        );
        $types = Database::select(
            'SELECT id, name FROM document_types WHERE institution_id = ? AND is_active = 1 ORDER BY name',
            [$instId]
        );
        $programs = Database::select(
            "SELECT id, name FROM programs WHERE institution_id = ? AND status = 'active' ORDER BY name",
            [$instId]
        );

        return $this->view('admin/reports/index', [
            'pageTitle' => 'Reportes',
            'filters' => [
                'desde' => $request->str('desde'),
                'hasta' => $request->str('hasta'),
                'estado' => $request->str('estado'),
                'comunidad' => $request->int('comunidad', 0),
                'coleccion' => $request->int('coleccion', 0),
                'tipo' => $request->int('tipo', 0),
                'programa' => $request->int('programa', 0),
            ],
            'summary' => $summary,
            'storage' => $storage,
            'downloads' => $downloads,
            'views' => $views,
            'rejections' => $rejections,
            'reviewDays' => $reviewDays['dias'] ?? null,
            'byYear' => $byYear,
            'byCollection' => $byCollection,
            'byType' => $byType,
            'byStatus' => $byStatus,
            'topAuthors' => $topAuthors,
            'topSearches' => $topSearches,
            'communities' => $communities,
            'collections' => $collections,
            'types' => $types,
            'programs' => $programs,
        ]);
    }

    /** GET /admin/reportes/exportar — CSV UTF-8 (BOM para Excel). */
    public function export(Request $request): Response
    {
        Authorization::authorize('reports.view');
        [$whereSql, $params] = $this->filters($request);

        $rows = Database::select(
            "SELECT i.public_id, v.title, i.status, i.access_level, v.issued_at,
                    i.created_at, i.published_at, c.name AS collection_name, cm.name AS community_name,
                    dt.name AS document_type_name, p.name AS program_name,
                    (SELECT COUNT(*) FROM files f WHERE f.item_id = i.id AND f.deleted_at IS NULL) AS files_count,
                    (SELECT COALESCE(SUM(f.file_size), 0) FROM files f WHERE f.item_id = i.id AND f.deleted_at IS NULL) AS bytes
             FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN collections c ON c.id = i.collection_id
             LEFT JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN programs p ON p.id = i.program_id
             WHERE {$whereSql}
             ORDER BY i.created_at DESC",
            $params
        );

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Identificador', 'Titulo', 'Estado', 'Acceso', 'Fecha emision', 'Creado', 'Publicado',
            'Coleccion', 'Comunidad', 'Tipo documental', 'Programa', 'Archivos', 'Bytes',
        ], ';');
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['public_id'],
                $row['title'],
                $row['status'],
                $row['access_level'],
                $row['issued_at'] ?? '',
                $row['created_at'],
                $row['published_at'] ?? '',
                $row['collection_name'] ?? '',
                $row['community_name'] ?? '',
                $row['document_type_name'] ?? '',
                $row['program_name'] ?? '',
                (int) $row['files_count'],
                (int) $row['bytes'],
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_recursos_' . gmdate('Ymd_His') . '.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
