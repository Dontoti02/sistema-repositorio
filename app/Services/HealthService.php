<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\TenantContext;
use App\Support\Migrator;

/**
 * Salud del sistema (6.19): requisitos, almacenamiento, cola, correo,
 * integridad y mantenimiento. Nunca revela secretos de .env.
 */
final class HealthService
{
    /** @return array{status:string, checks:array<string,array{status:string,detail:string}>} */
    public function report(): array
    {
        $checks = [];

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks['php_version'] = [
            'status' => $phpOk ? 'ok' : 'error',
            'detail' => 'PHP ' . PHP_VERSION . ($phpOk ? '' : ' (requiere >= 8.2)'),
        ];

        $missing = [];
        foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'json', 'session'] as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        $checks['extensiones'] = [
            'status' => $missing === [] ? 'ok' : 'error',
            'detail' => $missing === [] ? 'pdo_mysql, mbstring, fileinfo, openssl, json, session' : 'Faltan: ' . implode(', ', $missing),
        ];

        try {
            Database::ping();
            $tables = (int) Database::selectOne('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE()')['n'];
            $migrator = new Migrator();
            $checks['base_datos'] = [
                'status' => 'ok',
                'detail' => "Conectado | {$tables} tablas | esquema " . $migrator->version(),
            ];
        } catch (\Throwable $e) {
            $checks['base_datos'] = ['status' => 'error', 'detail' => 'Sin conexión: ' . $e->getMessage()];
        }

        $storageOk = is_writable(storage_path('private')) && is_writable(storage_path('logs'));
        $checks['almacenamiento'] = [
            'status' => $storageOk ? 'ok' : 'error',
            'detail' => $storageOk ? 'Directorios privado y logs escribibles' : 'storage/private o storage/logs sin permisos de escritura',
        ];

        $free = (int) (@disk_free_space(storage_path()) ?: 0);
        $checks['espacio_disco'] = [
            'status' => $free > 512 * 1024 * 1024 ? 'ok' : ($free > 100 * 1024 * 1024 ? 'warning' : 'error'),
            'detail' => $this->human($free) . ' libres en la unidad de almacenamiento',
        ];

        $pendingJobs = (int) Database::selectOne(
            "SELECT COUNT(*) AS n FROM jobs WHERE failed_at IS NULL AND available_at <= UTC_TIMESTAMP()"
        )['n'];
        $failedJobs = (int) Database::selectOne('SELECT COUNT(*) AS n FROM jobs WHERE failed_at IS NOT NULL')['n'];
        $checks['cola_trabajos'] = [
            'status' => $failedJobs === 0 ? 'ok' : 'warning',
            'detail' => "{$pendingJobs} pendiente(s) | {$failedJobs} fallido(s)",
        ];

        $pendingMail = (int) Database::selectOne("SELECT COUNT(*) AS n FROM email_outbox WHERE status = 'pending'")['n'];
        $failedMail = (int) Database::selectOne("SELECT COUNT(*) AS n FROM email_outbox WHERE status = 'failed'")['n'];
        $mailDriver = (string) (Config::file('mail')['driver'] ?? 'log');
        $checks['correo'] = [
            'status' => $failedMail === 0 ? 'ok' : 'warning',
            'detail' => "driver={$mailDriver} | {$pendingMail} pendiente(s) | {$failedMail} fallido(s)",
        ];

        $lastRun = Database::selectOne(
            "SELECT task_name, status, finished_at FROM scheduled_task_runs ORDER BY id DESC LIMIT 1"
        );
        $checks['tareas_programadas'] = [
            'status' => $lastRun === null ? 'warning' : ($lastRun['status'] === 'ok' ? 'ok' : 'warning'),
            'detail' => $lastRun === null
                ? 'Sin ejecuciones registradas (ejecute: php bin/console scheduled:run)'
                : $lastRun['task_name'] . ' → ' . $lastRun['status'] . ' el ' . $lastRun['finished_at'],
        ];

        $integrity = (new IntegrityService())->summary();
        $checks['integridad_archivos'] = [
            'status' => ($integrity['mismatch'] + $integrity['missing']) === 0 ? 'ok' : 'error',
            'detail' => "{$integrity['total']} verificado(s) | {$integrity['mismatch']} alterado(s) | {$integrity['missing']} faltante(s)"
                . ($integrity['last'] !== null ? ' | último control: ' . $integrity['last'] : ' | sin controles'),
        ];

        $envOk = is_file(base_path('.env'));
        $checks['entorno'] = [
            'status' => $envOk ? 'ok' : 'warning',
            'detail' => $envOk ? '.env presente (el contenido no se muestra nunca)' : 'Falta .env (revisar .env.example)',
        ];

        $quota = (int) (TenantContext::institution()['storage_quota_bytes'] ?? 0);
        $used = (int) Database::selectOne(
            'SELECT COALESCE(SUM(file_size), 0) AS n FROM files WHERE institution_id = ? AND deleted_at IS NULL',
            [TenantContext::institutionId()]
        )['n'];
        $quotaPct = $quota > 0 ? (int) floor($used * 100 / $quota) : 0;
        $checks['cuota_almacenamiento'] = [
            'status' => $quota === 0 ? 'ok' : ($quotaPct >= 95 ? 'error' : ($quotaPct >= 80 ? 'warning' : 'ok')),
            'detail' => $quota === 0
                ? $this->human($used) . ' usados (sin cuota configurada)'
                : $this->human($used) . ' de ' . $this->human($quota) . " ({$quotaPct}%)",
        ];

        $statuses = array_column($checks, 'status');
        $status = in_array('error', $statuses, true) ? 'error' : (in_array('warning', $statuses, true) ? 'warning' : 'ok');
        return ['status' => $status, 'checks' => $checks];
    }

    private function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return number_format($n, $i === 0 ? 0 : 1, ',', '.') . ' ' . $units[$i];
    }
}
