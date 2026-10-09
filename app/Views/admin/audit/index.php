<?php

declare(strict_types=1);

use App\Core\View;

/**
 * Bitacora de auditoria (6.18).
 *
 * @var array<int,array<string,mixed>> $rows
 * @var int $total
 * @var int $page
 * @var array{q?:string,actor?:int,entidad?:string,desde?:string,hasta?:string,pagina?:int} $filters
 * @var array<int,array<string,mixed>> $actors
 * @var array<int,string> $entityTypes
 */
$qs = http_build_query(array_filter([
    'q' => $filters['q'] ?? '',
    'actor' => $filters['actor'] ?? 0,
    'entidad' => $filters['entidad'] ?? '',
    'desde' => $filters['desde'] ?? '',
    'hasta' => $filters['hasta'] ?? '',
], static fn ($v) => $v !== '' && $v !== 0 && $v !== '0'));
?>
<div class="flex-between">
    <div>
        <h1>Bitácora y auditoría</h1>
        <p class="muted">Quién, qué, cuándo y sobre qué recurso. Consulta restringida a portadores del permiso <code>audit.view</code>; no registra contraseñas ni tokens.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/auditoria/exportar' . ($qs !== '' ? '?' . $qs : ''))) ?>"
       target="_blank" rel="noopener">Exportar CSV</a>
</div>

<form method="get" action="<?= e(url('/admin/auditoria')) ?>">
    <div class="card mt-3">
        <div class="form-grid">
            <div class="field">
                <label for="q">Buscar en acciones y detalles</label>
                <input type="search" id="q" name="q" maxlength="200" value="<?= e($filters['q'] ?? '') ?>" placeholder="items.transition, título…">
            </div>
            <div class="field">
                <label for="actor">Usuario</label>
                <select id="actor" name="actor">
                    <option value="">Todos</option>
                    <?php foreach ($actors as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"<?= (int) ($filters['actor'] ?? 0) === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="entidad">Entidad</label>
                <select id="entidad" name="entidad">
                    <option value="">Todas</option>
                    <?php foreach ($entityTypes as $t): ?>
                        <option value="<?= e($t) ?>"<?= ($filters['entidad'] ?? '') === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= e($filters['desde'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($filters['hasta'] ?? '') ?>">
            </div>
            <div class="field" style="align-self:end">
                <button class="btn" type="submit">Filtrar</button>
            </div>
        </div>
    </div>
</form>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <h3>Sin eventos</h3>
        <p>No hay registros de auditoría que coincidan con los filtros.</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead>
                <tr><th>Fecha (UTC)</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="small"><?= format_date_es($row['created_at']) ?></td>
                    <td class="small">
                        <?php if ($row['actor_name'] !== null): ?>
                            <?= e($row['actor_name']) ?>
                        <?php else: ?>
                            <span class="muted">Sistema</span>
                        <?php endif; ?>
                    </td>
                    <td><code class="small"><?= e($row['action']) ?></code></td>
                    <td class="small">
                        <?= e($row['entity_type']) ?><?= $row['entity_id'] !== null ? ' #' . (int) $row['entity_id'] : '' ?>
                        <?php if ($row['entity_type'] === 'items' && $row['entity_id'] !== null): ?>
                            · <a href="<?= e(url('/admin/recursos/' . $row['entity_id'])) ?>">ver recurso</a>
                        <?php endif; ?>
                    </td>
                    <td class="small" style="max-width:420px">
                        <?php if ($row['before_summary'] !== null && $row['before_summary'] !== ''): ?>
                            <span class="muted">antes:</span> <?= e(mb_substr((string) $row['before_summary'], 0, 140)) ?><br>
                        <?php endif; ?>
                        <?php if ($row['after_summary'] !== null && $row['after_summary'] !== ''): ?>
                            <?= e(mb_substr((string) $row['after_summary'], 0, 200)) ?>
                        <?php endif; ?>
                        <?php if (($row['before_summary'] ?? '') === '' && ($row['after_summary'] ?? '') === ''): ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($row['ip_masked'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small muted mt-2"><?= number_format($total, 0, ',', '.') ?> evento(s) en total.</p>
    <?= View::partial('components/pagination', ['page' => $page, 'total' => $total, 'perPage' => 50, 'filters' => [], 'label' => 'evento(s)']) ?>
<?php endif; ?>
