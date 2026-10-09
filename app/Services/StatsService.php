<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Estadisticas (6.14): agregados diarios de vistas y descargas
 * con deduplicacion razonable anti-bot (visitor_hash).
 */
final class StatsService
{
    /**
     * Reconstruye aggregate_daily_stats desde los eventos crudos.
     * Operacion idempotente por dia.
     *
     * @return int filas consolidadas
     */
    public function rebuildDailyAggregates(?string $fromDate = null, ?string $toDate = null): int
    {
        $instId = TenantContext::institutionId();
        $from = $fromDate ?? gmdate('Y-m-d', strtotime('-30 days'));
        $to = $toDate ?? gmdate('Y-m-d');

        $counted = 0;
        foreach ([['item_view_events', 'views'], ['download_events', 'downloads']] as [$table, $metric]) {
            $rows = Database::select(
                "SELECT DATE(e.event_at) AS d, e.item_id, i.collection_id,
                        COUNT(DISTINCT COALESCE(e.visitor_hash, CONCAT('u', COALESCE(e.user_id, 0)))) AS uniq,
                        COUNT(*) AS total
                 FROM {$table} e
                 JOIN items i ON i.id = e.item_id
                 WHERE e.institution_id = ? AND e.event_at >= ? AND e.event_at < DATE_ADD(?, INTERVAL 1 DAY)
                 GROUP BY DATE(e.event_at), e.item_id, i.collection_id",
                [$instId, $from . ' 00:00:00', $to]
            );
            foreach ($rows as $row) {
                Database::statement(
                    'INSERT INTO aggregate_daily_stats (institution_id, metric_date, item_id, collection_id, metric, metric_value)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)',
                    [$instId, $row['d'], (int) $row['item_id'], (int) $row['collection_id'], $metric, (int) $row['uniq']]
                );
                $counted++;
                Database::statement(
                    'INSERT INTO aggregate_daily_stats (institution_id, metric_date, item_id, collection_id, metric, metric_value)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)',
                    [$instId, $row['d'], (int) $row['item_id'], (int) $row['collection_id'], $metric . '_raw', (int) $row['total']]
                );
                $counted++;
            }
        }
        return $counted;
    }

    /** @return array{views:int,downloads:int} totales en el rango (deduplicados por visitante) */
    public function totals(string $from, string $to): array
    {
        $instId = TenantContext::institutionId();
        $views = (int) Database::selectOne(
            'SELECT COUNT(DISTINCT COALESCE(visitor_hash, CONCAT("u", COALESCE(user_id, 0)), item_id)) AS n
             FROM item_view_events WHERE institution_id = ? AND event_at >= ? AND event_at < DATE_ADD(?, INTERVAL 1 DAY)',
            [$instId, $from . ' 00:00:00', $to]
        )['n'];
        $downloads = (int) Database::selectOne(
            'SELECT COUNT(*) AS n FROM download_events
             WHERE institution_id = ? AND event_at >= ? AND event_at < DATE_ADD(?, INTERVAL 1 DAY)',
            [$instId, $from . ' 00:00:00', $to]
        )['n'];
        return ['views' => $views, 'downloads' => $downloads];
    }
}
