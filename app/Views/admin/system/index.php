<?php

declare(strict_types=1);

/**
 * Sistema (6.19): salud, cola, tareas programadas y respaldos.
 *
 * @var array{status:string,checks:array<string,array{status:string,detail:string}>} $health
 * @var array<int,array<string,mixed>> $runs
 * @var int $jobPending
 * @var int $jobFailed
 * @var array<int,array<string,mixed>> $outbox
 * @var array<int,array<string,mixed>> $backups
 */
use App\Core\Authorization;

$statusLabel = ['ok' => 'Correcto', 'warning' => 'Aviso', 'error' => 'Error'];
$statusBadge = ['ok' => 'badge-published', 'warning' => 'badge-withdrawn', 'error' => 'badge-rejected'];
$tasks = [
    'scheduled' => 'Ejecutar tareas programadas',
    'integrity' => 'Control de integridad de archivos',
    'stats' => 'Reconstruir agregados diarios',
    'queue' => 'Procesar cola de correo',
    'backup' => 'Crear respaldo de base de datos',
];
?>
<div class="flex-between">
    <div>
        <h1>Sistema</h1>
        <p class="muted">Estado operativo, cola de trabajos, tareas programadas y respaldos. No se muestran nunca valores secretos de configuración.</p>
    </div>
    <span class="badge <?= e($statusBadge[$health['status']] ?? 'badge-status') ?>"><?= e($statusLabel[$health['status']] ?? $health['status']) ?></span>
</div>

<div class="card mt-3">
    <h2>Estado de salud</h2>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>Comprobación</th><th>Estado</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($health['checks'] as $name => $check): ?>
                <tr>
                    <td class="small"><?= e(str_replace('_', ' ', $name)) ?></td>
                    <td><span class="badge <?= e($statusBadge[$check['status']] ?? 'badge-status') ?>"><?= e($statusLabel[$check['status']] ?? $check['status']) ?></span></td>
                    <td class="small"><?= e($check['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (Authorization::can('settings.manage')): ?>
<div class="card mt-3">
    <h2>Acciones manuales</h2>
    <p class="small muted">Cada acción se registra en la bitácora de auditoría.</p>
    <div class="flex gap-2 mt-2" style="flex-wrap:wrap">
        <?php foreach ($tasks as $task => $label): ?>
            <form method="post" action="<?= e(url('/admin/sistema/tarea')) ?>">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="task" value="<?= e($task) ?>">
                <button class="btn" type="submit"><?= e($label) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card mt-3">
    <h2>Cola y buzón</h2>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>Cola</th><th>Pendientes</th><th>Fallidos / enviados</th></tr></thead>
            <tbody>
                <tr>
                    <td class="small">Trabajos encolados (jobs)</td>
                    <td class="small"><?= number_format($jobPending, 0, ',', '.') ?></td>
                    <td class="small"><?= number_format($jobFailed, 0, ',', '.') ?> fallido(s)</td>
                </tr>
                <?php
                $byStatus = [];
                foreach ($outbox as $o) {
                    $byStatus[(string) $o['status']] = (int) $o['n'];
                }
                ?>
                <tr>
                    <td class="small">Buzón de correo (outbox)</td>
                    <td class="small"><?= number_format($byStatus['pending'] ?? 0, 0, ',', '.') ?> pendiente(s)</td>
                    <td class="small"><?= number_format($byStatus['failed'] ?? 0, 0, ',', '.') ?> fallido(s) · <?= number_format($byStatus['sent'] ?? 0, 0, ',', '.') ?> enviado(s)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <h2>Últimas ejecuciones programadas</h2>
    <?php if ($runs === []): ?>
        <p class="muted">Sin ejecuciones registradas todavía. Ejecute «Ejecutar tareas programadas» o <code>php bin/console scheduled:run</code>.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Tarea</th><th>Estado</th><th>Inicio (UTC)</th><th>Fin (UTC)</th><th>Salida</th></tr></thead>
                <tbody>
                <?php foreach ($runs as $run): ?>
                    <tr>
                        <td class="small"><code><?= e($run['task_name']) ?></code></td>
                        <td><span class="badge <?= $run['status'] === 'ok' ? 'badge-published' : ($run['status'] === 'failed' ? 'badge-rejected' : 'badge-withdrawn') ?>"><?= e($run['status']) ?></span></td>
                        <td class="small"><?= format_date_es($run['started_at']) ?></td>
                        <td class="small"><?= $run['finished_at'] !== null ? format_date_es($run['finished_at']) : '—' ?></td>
                        <td class="small" style="max-width:380px"><?= e(mb_substr((string) ($run['output'] ?? ''), 0, 200)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <h2>Respaldos recientes</h2>
    <?php if ($backups === []): ?>
        <p class="muted">Sin respaldos. Use «Crear respaldo de base de datos» o <code>php bin/console backups:create</code>.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Archivo</th><th>Tamaño</th><th>SHA-256</th><th>Creado (UTC)</th><th>Verificado</th></tr></thead>
                <tbody>
                <?php foreach ($backups as $b): ?>
                    <tr>
                        <td class="small" style="max-width:320px;word-break:break-all"><?= e((string) $b['file_path']) ?></td>
                        <td class="small"><?= number_format((int) $b['size_bytes'] / 1024, 1, ',', '.') ?> KB</td>
                        <td class="small"><code><?= e(mb_substr((string) $b['sha256'], 0, 16)) ?>…</code></td>
                        <td class="small"><?= format_date_es($b['created_at']) ?></td>
                        <td class="small"><?= $b['verified_at'] !== null ? format_date_es($b['verified_at']) : '<span class="muted">sin verificar</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small muted mt-2">Verificación: <code>php bin/console backups:verify</code>.</p>
    <?php endif; ?>
</div>
