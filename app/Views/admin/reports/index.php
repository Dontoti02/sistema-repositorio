<?php

declare(strict_types=1);

/**
 * Reportes y estadisticas (6.14).
 *
 * @var array<string,mixed> $filters
 * @var array<string,mixed> $summary
 * @var array<string,mixed> $storage
 * @var int $downloads
 * @var int $views
 * @var int $rejections
 * @var float|string|null $reviewDays
 * @var array<int,array<string,mixed>> $byYear
 * @var array<int,array<string,mixed>> $byCollection
 * @var array<int,array<string,mixed>> $byType
 * @var array<int,array<string,mixed>> $byStatus
 * @var array<int,array<string,mixed>> $topAuthors
 * @var array<int,array<string,mixed>> $topSearches
 * @var array<int,array<string,mixed>> $communities
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $programs
 */

$qs = http_build_query(array_filter($filters, static fn ($v) => $v !== '' && $v !== 0 && $v !== '0'));
$fmtSize = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return number_format($n, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
};
$statuses = ['DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'CHANGES_REQUESTED', 'APPROVED', 'REJECTED', 'PUBLISHED', 'WITHDRAWN', 'ARCHIVED'];
?>
<div class="flex-between">
    <div>
        <h1>Reportes y estadísticas</h1>
        <p class="muted">Indicadores reales con filtros por fecha y catálogo. Las métricas de visitas y descargas distinguen vistas de ficha y descargas completas.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/reportes/exportar' . ($qs !== '' ? '?' . $qs : ''))) ?>"
       target="_blank" rel="noopener">Exportar CSV</a>
</div>

<form method="get" action="<?= e(url('/admin/reportes')) ?>">
    <div class="card mt-3">
        <div class="form-grid">
            <div class="field">
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= e($filters['desde']) ?>">
            </div>
            <div class="field">
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($filters['hasta']) ?>">
            </div>
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>"<?= $filters['estado'] === $s ? ' selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="comunidad">Comunidad</label>
                <select id="comunidad" name="comunidad">
                    <option value="">Todas</option>
                    <?php foreach ($communities as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) $filters['comunidad'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="coleccion">Colección</label>
                <select id="coleccion" name="coleccion">
                    <option value="">Todas</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) $filters['coleccion'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['community_name']) ?> / <?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="tipo">Tipo documental</label>
                <select id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"<?= (int) $filters['tipo'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="programa">Programa</label>
                <select id="programa" name="programa">
                    <option value="">Todos</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"<?= (int) $filters['programa'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="align-self:end">
                <button class="btn" type="submit">Aplicar</button>
            </div>
        </div>
    </div>
</form>

<div class="grid grid-4 mt-3">
    <div class="stat"><span class="stat-value"><?= number_format((int) ($summary['total'] ?? 0), 0, ',', '.') ?></span><span class="stat-label">Recursos en filtros</span></div>
    <div class="stat"><span class="stat-value"><?= number_format((int) ($summary['publicados'] ?? 0), 0, ',', '.') ?></span><span class="stat-label">Publicados</span></div>
    <div class="stat"><span class="stat-value" style="<?= (int) ($summary['pendientes'] ?? 0) > 0 ? 'color:var(--warning)' : '' ?>"><?= number_format((int) ($summary['pendientes'] ?? 0), 0, ',', '.') ?></span><span class="stat-label">Pendientes de revisión</span></div>
    <div class="stat"><span class="stat-value"><?= number_format((int) ($summary['rechazados'] ?? 0), 0, ',', '.') ?></span><span class="stat-label">Rechazados (filtro actual)</span></div>
</div>
<div class="grid grid-4 mt-2">
    <div class="stat"><span class="stat-value"><?= number_format($views, 0, ',', '.') ?></span><span class="stat-label">Visitas a fichas (rango)</span></div>
    <div class="stat"><span class="stat-value"><?= number_format($downloads, 0, ',', '.') ?></span><span class="stat-label">Descargas (rango)</span></div>
    <div class="stat"><span class="stat-value"><?= number_format($rejections, 0, ',', '.') ?></span><span class="stat-label">Rechazos históricos (rango)</span></div>
    <div class="stat"><span class="stat-value"><?= $reviewDays !== null ? number_format((float) $reviewDays, 1, ',', '.') : '—' ?></span><span class="stat-label">Días promedio de revisión</span></div>
</div>
<div class="grid grid-2 mt-2">
    <div class="stat"><span class="stat-value"><?= number_format((int) ($storage['files'] ?? 0), 0, ',', '.') ?></span><span class="stat-label">Archivos almacenados</span></div>
    <div class="stat"><span class="stat-value"><?= e($fmtSize((int) ($storage['bytes'] ?? 0))) ?></span><span class="stat-label">Volumen almacenado</span></div>
</div>

<div class="grid grid-2 mt-3">
    <div class="card">
        <h2 style="font-size:1.05rem">Distribución por estado</h2>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Estado</th><th>Recursos</th></tr></thead>
                <tbody>
                <?php foreach ($byStatus as $s): ?>
                    <tr><td><?= e($s['status']) ?></td><td><?= number_format((int) $s['total'], 0, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                <?php if ($byStatus === []): ?><tr><td colspan="2" class="muted">Sin datos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h2 style="font-size:1.05rem">Documentos por año de emisión</h2>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Año</th><th>Documentos</th></tr></thead>
                <tbody>
                <?php foreach ($byYear as $y): ?>
                    <tr><td><?= (int) $y['anio'] ?></td><td><?= number_format((int) $y['total'], 0, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                <?php if ($byYear === []): ?><tr><td colspan="2" class="muted">Sin datos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-3 mt-2">
    <div class="card">
        <h2 style="font-size:1.05rem">Por colección</h2>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Colección</th><th>Total</th><th>Pub.</th></tr></thead>
                <tbody>
                <?php foreach ($byCollection as $c): ?>
                    <tr>
                        <td class="small"><?= e($c['comunidad']) ?> / <?= e($c['coleccion']) ?></td>
                        <td><?= number_format((int) $c['total'], 0, ',', '.') ?></td>
                        <td><?= number_format((int) $c['publicados'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($byCollection === []): ?><tr><td colspan="3" class="muted">Sin datos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h2 style="font-size:1.05rem">Por tipo documental</h2>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Tipo</th><th>Recursos</th></tr></thead>
                <tbody>
                <?php foreach ($byType as $t): ?>
                    <tr><td><?= e($t['tipo']) ?></td><td><?= number_format((int) $t['total'], 0, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                <?php if ($byType === []): ?><tr><td colspan="2" class="muted">Sin datos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h2 style="font-size:1.05rem">Autores más productivos (publicados)</h2>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Autor</th><th>Recursos</th></tr></thead>
                <tbody>
                <?php foreach ($topAuthors as $a): ?>
                    <tr><td><?= e($a['full_name']) ?></td><td><?= number_format((int) $a['total'], 0, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                <?php if ($topAuthors === []): ?><tr><td colspan="2" class="muted">Sin datos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-2">
    <h2 style="font-size:1.05rem">Búsquedas agregadas del sitio público</h2>
    <?php if ($topSearches === []): ?>
        <p class="muted">Aún no se registran búsquedas.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Término</th><th>Búsquedas</th><th>Última</th></tr></thead>
                <tbody>
                <?php foreach ($topSearches as $s): ?>
                    <tr><td><?= e($s['term']) ?></td><td><?= number_format((int) $s['searches'], 0, ',', '.') ?></td><td class="small"><?= format_date_es($s['last_at']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
