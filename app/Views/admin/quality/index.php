<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<int,array<string,mixed>> $report */
/** @var array{alta:int,media:int,baja:int} $totals */
/** @var int $withIssues */
/** @var int $totalItems */
/** @var string $statusFilter */
/** @var string $severityFilter */

function quality_badge(int $score): string
{
    if ($score >= 80) {
        return 'badge-published';
    }
    if ($score >= 50) {
        return 'badge-withdrawn';
    }
    return 'badge-rejected';
}

function sev_badge(string $sev): string
{
    if ($sev === 'alta') {
        return 'badge-rejected';
    }
    if ($sev === 'media') {
        return 'badge-withdrawn';
    }
    return 'badge-status';
}
?>
<div class="flex-between">
    <div>
        <h1>Calidad de recursos</h1>
        <p class="muted">Validadores automáticos de metadatos con severidad y propuesta de corrección.</p>
    </div>
    <form method="post" action="<?= e(url('/admin/calidad/recalcular')) ?>">
        <?= Csrf::field() ?>
        <button class="btn btn-primary" type="submit">Recalcular puntuaciones</button>
    </form>
</div>

<div class="grid grid-4 mt-3">
    <div class="stat card">
        <span class="stat-value" style="color:#b02a37"><?= (int) $totals['alta'] ?></span>
        <span class="stat-label">Hallazgos altos</span>
    </div>
    <div class="stat card">
        <span class="stat-value" style="color:#c9922b"><?= (int) $totals['media'] ?></span>
        <span class="stat-label">Hallazgos medios</span>
    </div>
    <div class="stat card">
        <span class="stat-value"><?= (int) $totals['baja'] ?></span>
        <span class="stat-label">Hallazgos bajos</span>
    </div>
    <div class="stat card">
        <span class="stat-value"><?= $withIssues ?>/<?= $totalItems ?></span>
        <span class="stat-label">Recursos con hallazgos</span>
    </div>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/calidad')) ?>" class="flex">
        <label class="visually-hidden" for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'CHANGES_REQUESTED', 'APPROVED', 'PUBLISHED', 'REJECTED', 'WITHDRAWN', 'ARCHIVED'] as $s): ?>
                <option value="<?= e($s) ?>"<?= $statusFilter === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="visually-hidden" for="severidad">Severidad</label>
        <select id="severidad" name="severidad">
            <option value="">Toda severidad</option>
            <option value="alta"<?= $severityFilter === 'alta' ? ' selected' : '' ?>>Alta</option>
            <option value="media"<?= $severityFilter === 'media' ? ' selected' : '' ?>>Media</option>
            <option value="baja"<?= $severityFilter === 'baja' ? ' selected' : '' ?>>Baja</option>
        </select>
        <button class="btn" type="submit">Filtrar</button>
        <?php if ($statusFilter !== '' || $severityFilter !== ''): ?>
            <a class="btn btn-secondary" href="<?= e(url('/admin/calidad')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($report === []): ?>
    <div class="empty-state">
        <h3>Sin recursos para analizar</h3>
        <p>No hay recursos activos que coincidan con el filtro.</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead>
            <tr><th style="width:32%">Recurso</th><th>Estado</th><th>Puntaje</th><th>Hallazgos</th></tr>
            </thead>
            <tbody>
            <?php foreach ($report as $row): ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/admin/recursos/' . $row['item_id'])) ?>"><strong><?= e($row['title']) ?></strong></a><br>
                        <span class="small muted"><?= e($row['public_id']) ?></span>
                    </td>
                    <td><span class="badge badge-status"><?= e($row['status']) ?></span></td>
                    <td>
                        <span class="badge <?= quality_badge((int) $row['score']) ?>"><?= (int) $row['score'] ?>/100</span>
                        <?php if ((int) $row['score'] !== (int) $row['stored_score']): ?>
                            <br><span class="small muted">guardado: <?= (int) $row['stored_score'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['findings'] === []): ?>
                            <span class="badge badge-published">Sin hallazgos</span>
                        <?php else: ?>
                            <ul class="mt-1" style="margin:0;padding-left:1.1rem">
                                <?php foreach ($row['findings'] as $f): ?>
                                    <li style="margin-bottom:.45rem">
                                        <span class="badge <?= sev_badge((string) $f['sev']) ?>"><?= e($f['sev']) ?></span>
                                        <?= e($f['msg']) ?>
                                        <br><span class="small muted">Propuesta: <?= e($f['fix']) ?> (<?= e($f['code']) ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
