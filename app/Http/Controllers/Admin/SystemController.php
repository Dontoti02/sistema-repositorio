<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\BackupService;
use App\Services\HealthService;
use App\Services\IntegrityService;
use App\Services\NotificationService;
use App\Services\SchedulerService;
use App\Services\StatsService;

/**
 * Sistema (6.19): estado de salud, cola de trabajos y correo,
 * últimas ejecuciones programadas, respaldos y acciones manuales.
 */
final class SystemController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('settings.manage');
        $health = (new HealthService())->report();

        $runs = Database::select(
            'SELECT task_name, status, started_at, finished_at, output FROM scheduled_task_runs ORDER BY id DESC LIMIT 10'
        );
        $jobs = Database::select(
            "SELECT COUNT(*) AS n, failed_at IS NOT NULL AS failed FROM jobs GROUP BY failed_at IS NOT NULL"
        );
        $jobPending = 0;
        $jobFailed = 0;
        foreach ($jobs as $j) {
            if ((int) $j['failed'] === 1) {
                $jobFailed = (int) $j['n'];
            } else {
                $jobPending = (int) $j['n'];
            }
        }
        $outbox = Database::select(
            "SELECT status, COUNT(*) AS n FROM email_outbox GROUP BY status"
        );
        $backups = Database::select(
            'SELECT file_path, size_bytes, sha256, created_at, verified_at FROM backups ORDER BY id DESC LIMIT 5'
        );

        return $this->view('admin/system/index', [
            'pageTitle' => 'Sistema',
            'health' => $health,
            'runs' => $runs,
            'jobPending' => $jobPending,
            'jobFailed' => $jobFailed,
            'outbox' => $outbox,
            'backups' => $backups,
        ]);
    }

    /** POST /admin/sistema/tarea — ejecuta una tarea manual (allowlist). */
    public function runTask(Request $request): Response
    {
        $this->authorize('settings.manage');
        $task = $request->str('task');
        $tasks = ['scheduled' => 'Tareas programadas', 'integrity' => 'Control de integridad', 'stats' => 'Agregados diarios', 'queue' => 'Cola de trabajos', 'backup' => 'Respaldo de base de datos'];
        if (!isset($tasks[$task])) {
            throw new HttpException(422, 'Tarea no válida.');
        }

        $result = match ($task) {
            'scheduled' => (new SchedulerService())->runAll(),
            'integrity' => (new IntegrityService())->checkAll(),
            'stats' => (new StatsService())->rebuildDailyAggregates(),
            'queue' => ['enviados' => (new NotificationService())->processQueue(100)],
            'backup' => (new BackupService())->create(),
        };

        \App\Services\AuditLogger::log('system.task', 'settings', null, null, $tasks[$task]);
        $this->flash('success', $tasks[$task] . ': ejecutado correctamente.');
        return $this->redirect('/admin/sistema');
    }
}
