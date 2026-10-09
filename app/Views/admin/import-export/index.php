<?php

declare(strict_types=1);

use App\Core\Authorization;

/**
 * Importar y exportar (6.16): subida de CSV, lotes recientes,
 * exportación de registros y descargas.
 *
 * @var array<int,array<string,mixed>> $batches
 * @var array<int,array<string,mixed>> $exports
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,string> $statuses
 */
$canImport = Authorization::can('imports.manage');
$canExport = Authorization::can('exports.manage');
$batchStatus = [
    'preview' => ['Pendiente de mapeo', 'badge-withdrawn'],
    'validated' => ['Prevalidado', 'badge-submitted'],
    'imported' => ['Importado', 'badge-published'],
    'reverted' => ['Revertido', 'badge-archived'],
    'failed' => ['Fallido', 'badge-rejected'],
];
?>
<div>
    <h1>Importar y exportar</h1>
    <p class="muted">Carga de registros en CSV UTF-8 con mapeo de columnas y prevalidación por fila; exportación trazable de registros en CSV o JSON.</p>
</div>

<?php if ($canImport): ?>
<div class="card mt-3" id="importar">
    <h2>Importar CSV</h2>
    <p class="small muted">Primera fila = encabezados. Máximo 5 MB y 5000 filas por lote. Codificación UTF-8 obligatoria. La importación crea recursos en estado <strong>borrador</strong>.</p>
    <form method="post" action="<?= e(url('/admin/importar-exportar/subir')) ?>" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-grid mt-2">
            <div class="field">
                <label for="csv">Archivo CSV (UTF-8) *</label>
                <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
            </div>
            <div class="field" style="align-self:end">
                <button class="btn btn-primary" type="submit">Subir y definir mapeo</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($canExport): ?>
<div class="card mt-3" id="exportar">
    <h2>Exportar registros</h2>
    <form method="post" action="<?= e(url('/admin/importar-exportar/exportar')) ?>">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-grid mt-2">
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= e($st) ?>"><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="collection_id">Colección</label>
                <select id="collection_id" name="collection_id">
                    <option value="0">Todas</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="desde">Creados desde</label>
                <input type="date" id="desde" name="desde">
            </div>
            <div class="field">
                <label for="hasta">Creados hasta</label>
                <input type="date" id="hasta" name="hasta">
            </div>
            <div class="field">
                <label for="format">Formato</label>
                <select id="format" name="format">
                    <option value="csv">CSV (UTF-8 con BOM, separador ;)</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            <div class="field" style="align-self:end">
                <button class="btn btn-primary" type="submit">Generar exportación</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card mt-3">
    <h2>Lotes de importación</h2>
    <?php if ($batches === []): ?>
        <p class="muted">Sin lotes todavía.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>#</th><th>Archivo</th><th>Estado</th><th>Filas</th><th>Válidas</th><th>Errores</th><th>Creado por</th><th>Fecha (UTC)</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($batches as $b): ?>
                    <?php [$label, $badge] = $batchStatus[(string) $b['status']] ?? [(string) $b['status'], 'badge-status']; ?>
                    <tr>
                        <td class="small"><?= (int) $b['id'] ?></td>
                        <td class="small" style="max-width:220px;word-break:break-all"><?= e($b['filename']) ?></td>
                        <td><span class="badge <?= e($badge) ?>"><?= e($label) ?></span></td>
                        <td class="small"><?= (int) $b['total_rows'] ?></td>
                        <td class="small"><?= (int) $b['ok_rows'] ?></td>
                        <td class="small"><?= (int) $b['error_rows'] ?></td>
                        <td class="small"><?= e((string) ($b['creator'] ?? '—')) ?></td>
                        <td class="small"><?= format_date_es($b['created_at']) ?></td>
                        <td class="small"><a class="btn btn-sm" href="<?= e(url('/admin/importar-exportar/' . (int) $b['id'])) ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <h2>Exportaciones recientes</h2>
    <?php if ($exports === []): ?>
        <p class="muted">Sin exportaciones todavía.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>#</th><th>Formato</th><th>Estado</th><th>Registros</th><th>Creado por</th><th>Fecha (UTC)</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($exports as $x): ?>
                    <tr>
                        <td class="small"><?= (int) $x['id'] ?></td>
                        <td class="small"><?= e(strtoupper((string) $x['format'])) ?></td>
                        <td>
                            <span class="badge <?= $x['status'] === 'done' ? 'badge-published' : ($x['status'] === 'failed' ? 'badge-rejected' : 'badge-withdrawn') ?>"><?= e($x['status']) ?></span>
                            <?php if (($x['filters_json'] ?? '') !== '' && $x['filters_json'] !== null): ?>
                                <br><span class="small muted"><?= e(mb_substr((string) $x['filters_json'], 0, 80)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= (int) $x['row_count'] ?></td>
                        <td class="small"><?= e((string) ($x['creator'] ?? '—')) ?></td>
                        <td class="small"><?= format_date_es($x['created_at']) ?></td>
                        <td class="small">
                            <?php if ($x['status'] === 'done'): ?>
                                <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/importar-exportar/exportar/' . (int) $x['id'])) ?>">Descargar</a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
