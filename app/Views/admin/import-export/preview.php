<?php

declare(strict_types=1);

use App\Core\Authorization;

/**
 * Detalle de lote: mapeo de columnas, vista previa e informe por fila.
 *
 * @var array<string,mixed> $batch
 * @var array<int,string> $headers
 * @var array<string,int> $savedMapping
 * @var array<string,mixed> $defaults
 * @var array<string,string> $targets
 * @var array<int,array<string,mixed>> $rows
 * @var array{ok:int,error:int} $counts
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,string> $languages
 */
$meta = json_decode((string) $batch['mapping_json'], true) ?: [];
$path = (string) ($meta['path'] ?? '');
$fileOk = $path !== '' && is_file($path);
$status = (string) $batch['status'];
$canImport = Authorization::can('imports.manage');
$batchStatusLabel = [
    'preview' => 'Pendiente de mapeo',
    'validated' => 'Prevalidado',
    'imported' => 'Importado',
    'reverted' => 'Revertido',
    'failed' => 'Fallido',
][$status] ?? $status;
?>
<div class="flex-between">
    <div>
        <h1><?= e((string) $batch['filename']) ?></h1>
        <p class="muted">
            Lote #<?= (int) $batch['id'] ?> ·
            <span class="badge badge-status"><?= e($batchStatusLabel) ?></span>
            · <?= (int) $batch['total_rows'] ?> fila(s) ·
            <?= (int) $batch['ok_rows'] ?> válida(s) ·
            <?= (int) $batch['error_rows'] ?> con error(es)
        </p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('/admin/importar-exportar')) ?>">Volver al panel</a>
</div>

<?php if ($canImport && in_array($status, ['preview', 'validated'], true)): ?>
<form method="post" action="<?= e(url('/admin/importar-exportar/' . (int) $batch['id'] . '/preparar')) ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="card mt-3">
        <h2>Mapeo de columnas</h2>
        <?php if (!$fileOk): ?>
            <div class="alert alert-danger">El archivo CSV original ya no está disponible: no es posible revalidar.</div>
        <?php endif; ?>
        <p class="small muted">Elija qué columna del CSV alimenta cada campo. Los campos no mapeados quedan vacíos. La primera fila del archivo son los encabezados.</p>
        <div class="form-grid mt-2">
            <?php foreach ($targets as $target => $label): ?>
                <div class="field">
                    <label for="map_<?= e($target) ?>"><?= e($label) ?><?= in_array($target, ['title'], true) ? ' *' : '' ?></label>
                    <select id="map_<?= e($target) ?>" name="map[<?= e($target) ?>]"<?= $target === 'title' ? ' required' : '' ?>>
                        <option value="">— sin mapear —</option>
                        <?php foreach ($headers as $i => $header): ?>
                            <option value="<?= $i ?>"<?= (int) ($savedMapping[$target] ?? -1) === $i ? ' selected' : '' ?>><?= $i ?>: <?= e($header) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="field">
                <label for="defaults_collection">Colección de destino *</label>
                <select id="defaults_collection" name="defaults[collection_id]" required>
                    <option value="">— seleccione —</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) ($defaults['collection_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="defaults_language">Idioma por defecto</label>
                <select id="defaults_language" name="defaults[language_code]">
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?= e($lang) ?>"<?= (string) ($defaults['language_code'] ?? 'es') === $lang ? ' selected' : '' ?>><?= e($lang) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="defaults_access">Nivel de acceso por defecto</label>
                <select id="defaults_access" name="defaults[access_level]">
                    <?php foreach (['open' => 'Abierto', 'metadata_only' => 'Solo metadatos', 'embargoed' => 'Embargado', 'restricted' => 'Restringido', 'private' => 'Privado'] as $val => $lab): ?>
                        <option value="<?= e($val) ?>"<?= (string) ($defaults['access_level'] ?? 'open') === $val ? ' selected' : '' ?>><?= e($lab) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary" type="submit"<?= $fileOk ? '' : ' disabled' ?>><?= $status === 'validated' ? 'Revalidar' : 'Prevalidar filas' ?></button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php if ($status === 'validated'): ?>
    <div class="card mt-3">
        <h2>Importar lote</h2>
        <p class="small muted">La importación es transaccional: si una fila válida falla, se revierte el lote completo. Los recursos se crean en estado <strong>borrador</strong> y luego puede revisarlos antes de someterlos.</p>
        <div class="mt-2">
            <button class="btn btn-primary" type="submit" form="form-importar" data-confirm="¿Importar <?= (int) $batch['ok_rows'] ?> fila(s) válida(s)?">Importar ahora</button>
        </div>
        <form id="form-importar" method="post" action="<?= e(url('/admin/importar-exportar/' . (int) $batch['id'] . '/importar')) ?>">
            <?= \App\Core\Csrf::field() ?>
        </form>
    </div>
<?php endif; ?>

<?php if ($status === 'imported'): ?>
    <div class="card mt-3">
        <h2>Lote importado</h2>
        <p class="small muted">Los <?= (int) $batch['ok_rows'] ?> recurso(s) fueron creados. Puede revertir el lote mientras los recursos sigan en borrador.</p>
        <div class="mt-2">
            <form method="post" action="<?= e(url('/admin/importar-exportar/' . (int) $batch['id'] . '/revertir')) ?>" data-confirm="¿Revertir el lote? Se eliminarán los recursos que sigan en borrador.">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-secondary" type="submit">Revertir lote</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($rows !== []): ?>
<div class="card mt-3">
    <h2>Informe por fila</h2>
    <p class="small muted">
        <span class="badge badge-published"><?= $counts['ok'] ?> válida(s)</span>
        <span class="badge badge-rejected"><?= $counts['error'] ?> con error(es)</span>
        (se muestran hasta 300 filas)
    </p>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>Fila</th><th>Estado</th><th>Título</th><th>Colección</th><th>Idioma</th><th>Fecha</th><th>Errores / avisos</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $raw = $row['raw']; ?>
                <tr>
                    <td class="small"><?= (int) $row['row_no'] ?></td>
                    <td>
                        <span class="badge <?= $row['status'] === 'ok' ? 'badge-published' : 'badge-rejected' ?>">
                            <?= $row['status'] === 'ok' ? 'válida' : 'error' ?>
                        </span>
                    </td>
                    <td class="small" style="max-width:240px"><?= e(mb_substr((string) ($raw['title'] ?? ''), 0, 100)) ?></td>
                    <td class="small"><?= (int) ($raw['collection_id'] ?? 0) ?></td>
                    <td class="small"><?= e((string) ($raw['language_code'] ?? '')) ?></td>
                    <td class="small"><?= e((string) ($raw['issued_at'] ?? '—')) ?></td>
                    <td class="small" style="max-width:320px">
                        <?php if (!empty($row['error_list'])): ?>
                            <?php foreach ($row['error_list'] as $err): ?>
                                <span class="badge <?= $row['status'] === 'ok' ? 'badge-withdrawn' : 'badge-rejected' ?>"><?= e($err) ?></span><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if (!empty($raw['doi'])): ?>DOI: <?= e($raw['doi']) ?><?php endif; ?>
                        <?php if (!empty($row['item_id'])): ?>
                            <a href="<?= e(url('/admin/recursos/' . (int) $row['item_id'])) ?>">ver recurso</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
