<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Tareas programadas (6.15 / 6.19): liberacion de embargos, cola de
 * correo, agregados diarios y avisos. Idempotente y auditable.
 */
final class SchedulerService
{
    /** @return array<string,string> nombre de la tarea => resumen legible */
    public function runAll(): array
    {
        $report = [];
        $report['embargos_liberados'] = $this->record('embargo_release', fn () => $this->releaseEmbargoes());
        $report['avisos_embargo'] = $this->record('embargo_notify', fn () => $this->notifyExpiringEmbargoes());
        $report['correo_enviado'] = $this->record('email_queue', fn () => (new NotificationService())->processQueue(100));
        $report['agregados'] = $this->record('daily_aggregates', fn () => (new StatsService())->rebuildDailyAggregates());
        return $report;
    }

    /** @return string resumen del resultado */
    private function record(string $task, callable $fn): string
    {
        Database::statement(
            "INSERT INTO scheduled_task_runs (task_name, status, started_at) VALUES (?, 'running', UTC_TIMESTAMP())",
            [$task]
        );
        $runId = (int) Database::lastInsertId();
        try {
            $result = $fn();
            $summary = is_scalar($result) || $result === null
                ? (string) $result
                : (string) (json_encode($result, JSON_UNESCAPED_UNICODE) ?: 'ok');
            Database::statement(
                "UPDATE scheduled_task_runs SET status = 'ok', finished_at = UTC_TIMESTAMP(), output = ? WHERE id = ?",
                [mb_substr($summary, 0, 4000), $runId]
            );
            return 'ok — ' . $summary;
        } catch (\Throwable $e) {
            Database::statement(
                "UPDATE scheduled_task_runs SET status = 'failed', finished_at = UTC_TIMESTAMP(), output = ? WHERE id = ?",
                [mb_substr($e->getMessage(), 0, 4000), $runId]
            );
            return 'falló — ' . $e->getMessage();
        }
    }

    /**
     * Libera embargos vencidos (idempotente): el archivo queda accesible
     * y se audita/notifica al depositario.
     *
     * @return int liberaciones
     */
    public function releaseEmbargoes(): int
    {
        $instId = TenantContext::institutionId();
        $released = 0;

        $dueItems = Database::select(
            "SELECT id, created_by, public_id FROM items
             WHERE institution_id = ? AND access_level = 'embargoed'
               AND embargo_until IS NOT NULL AND embargo_until <= UTC_TIMESTAMP() AND deleted_at IS NULL",
            [$instId]
        );
        foreach ($dueItems as $item) {
            Database::transaction(function () use ($item, $instId): void {
                Database::statement(
                    "UPDATE items SET access_level = 'open', updated_at = UTC_TIMESTAMP() WHERE id = ?",
                    [(int) $item['id']]
                );
                Database::statement(
                    'INSERT INTO item_status_history (institution_id, item_id, from_status, to_status, actor_id, reason, created_at)
                     VALUES (?, ?, ?, ?, NULL, ?, UTC_TIMESTAMP())',
                    [$instId, (int) $item['id'], 'embargoed', 'open', 'Embargo vencido: liberacion automatica']
                );
            });
            Database::statement(
                "UPDATE embargoes SET status = 'released', released_at = UTC_TIMESTAMP()
                 WHERE item_id = ? AND status = 'active'",
                [(int) $item['id']]
            );
            AuditLogger::log('embargo.released', 'items', (int) $item['id'], 'embargoed', 'open');
            if ((int) ($item['created_by'] ?? 0) > 0) {
                (new NotificationService())->notify(
                    (int) $item['created_by'],
                    'embargo.released',
                    'El embargo del recurso ' . $item['public_id'] . ' venció: el archivo ya es accesible.',
                    '/admin/recursos/' . $item['id']
                );
            }
            $released++;
        }

        $dueRows = Database::select(
            "SELECT e.id, e.item_id, e.file_id, i.created_by, i.public_id
             FROM embargoes e JOIN items i ON i.id = e.item_id
             WHERE e.institution_id = ? AND e.status = 'active' AND e.embargo_until <= UTC_TIMESTAMP()",
            [$instId]
        );
        foreach ($dueRows as $row) {
            Database::statement(
                "UPDATE embargoes SET status = 'released', released_at = UTC_TIMESTAMP() WHERE id = ? AND status = 'active'",
                [(int) $row['id']]
            );
            Database::statement(
                "UPDATE items SET access_level = 'open', updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND access_level = 'embargoed'",
                [(int) $row['item_id']]
            );
            AuditLogger::log('embargo.released', 'embargoes', (int) $row['id'], 'active', 'released');
            if ((int) ($row['created_by'] ?? 0) > 0) {
                (new NotificationService())->notify(
                    (int) $row['created_by'],
                    'embargo.released',
                    'Se liberó el embargo del recurso ' . $row['public_id'] . '.',
                    '/admin/recursos/' . $row['item_id']
                );
            }
            $released++;
        }
        return $released;
    }

    /**
     * Libera un recurso concreto (accion manual desde /admin/institucion).
     * Idempotente: devuelve true si hubo algo que liberar.
     */
    public function releaseItem(int $itemId, string $reason = 'Liberacion manual de embargo'): bool
    {
        $instId = TenantContext::institutionId();
        $item = Database::selectOne(
            "SELECT id, created_by, public_id, access_level FROM items
             WHERE id = ? AND institution_id = ? AND deleted_at IS NULL",
            [$itemId, $instId]
        );
        if ($item === null) {
            return false;
        }
        $active = Database::selectOne(
            "SELECT COUNT(*) AS n FROM embargoes WHERE item_id = ? AND status = 'active'",
            [$itemId]
        );
        $embargoed = $item['access_level'] === 'embargoed';
        if (!$embargoed && (int) $active['n'] === 0) {
            return false;
        }

        if ($embargoed) {
            Database::transaction(function () use ($itemId, $item, $instId, $reason): void {
                Database::statement(
                    "UPDATE items SET access_level = 'open', updated_at = UTC_TIMESTAMP() WHERE id = ?",
                    [$itemId]
                );
                Database::statement(
                    'INSERT INTO item_status_history (institution_id, item_id, from_status, to_status, actor_id, reason, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                    [$instId, $itemId, 'embargoed', 'open', \App\Core\Auth::id(), $reason]
                );
            });
        }
        Database::statement(
            "UPDATE embargoes SET status = 'released', released_at = UTC_TIMESTAMP() WHERE item_id = ? AND status = 'active'",
            [$itemId]
        );
        AuditLogger::log('embargo.released', 'items', $itemId, 'embargoed', 'open | ' . $reason);
        if ((int) ($item['created_by'] ?? 0) > 0) {
            (new NotificationService())->notify(
                (int) $item['created_by'],
                'embargo.released',
                'Se liberó el embargo del recurso ' . $item['public_id'] . ': el archivo ya es accesible.',
                '/admin/recursos/' . $itemId
            );
        }
        return true;
    }

    /** Avisa a los depositarios de embargos que vencen en 3 días. */
    public function notifyExpiringEmbargoes(): int
    {
        $instId = TenantContext::institutionId();
        $rows = Database::select(
            "SELECT id, created_by, public_id, embargo_until FROM items
             WHERE institution_id = ? AND access_level = 'embargoed'
               AND embargo_until IS NOT NULL
               AND embargo_until BETWEEN UTC_TIMESTAMP() AND DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)
               AND deleted_at IS NULL",
            [$instId]
        );
        $n = 0;
        foreach ($rows as $row) {
            if ((int) ($row['created_by'] ?? 0) <= 0) {
                continue;
            }
            $already = Database::selectOne(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND type = 'embargo.expiring'
                   AND message LIKE ? AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
                 LIMIT 1",
                [(int) $row['created_by'], '%' . $row['public_id'] . '%']
            );
            if ($already !== null) {
                continue;
            }
            (new NotificationService())->notify(
                (int) $row['created_by'],
                'embargo.expiring',
                'El embargo del recurso ' . $row['public_id'] . ' vence el ' . substr((string) $row['embargo_until'], 0, 10) . '.',
                '/admin/recursos/' . $row['id']
            );
            $n++;
        }
        return $n;
    }
}
