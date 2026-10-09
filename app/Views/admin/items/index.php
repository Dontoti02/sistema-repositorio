<?php

declare(strict_types=1);

use App\Core\Authorization;
use App\Core\View;

/**
 * @var array<int,array<string,mixed>> $rows
 * @var int $total
 * @var int $page
 * @var array{q?:string,status?:string,collection_id?:int,type_id?:int,page?:int} $filters
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,array<string,mixed>> $types
 */
$statuses = ['DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'CHANGES_REQUESTED', 'APPROVED', 'REJECTED', 'PUBLISHED', 'WITHDRAWN', 'ARCHIVED'];
?>
<div class="flex-between">
    <div>
        <h1>Recursos</h1>
        <p class="muted">Búsqueda interna sobre todos los recursos del instituto (<?= number_format($total, 0, ',', '.') ?>).</p>
    </div>
    <?php if (Authorization::can('items.create')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/deposito')) ?>">Nuevo depósito</a>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/recursos')) ?>">
        <div class="form-grid">
            <div class="field field-full">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" maxlength="200"
                       placeholder="Identificador, título o resumen…">
            </div>
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="pending"<?= ($filters['status'] ?? '') === 'pending' ? ' selected' : '' ?>>Pendientes (revisión)</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>"<?= ($filters['status'] ?? '') === $s ? ' selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="coleccion">Colección</label>
                <select id="coleccion" name="coleccion">
                    <option value="">Todas</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) ($filters['collection_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>>
                            <?= e($c['community_name']) ?> / <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="tipo">Tipo documental</label>
                <select id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"<?= (int) ($filters['type_id'] ?? 0) === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="align-self:end">
                <button class="btn" type="submit">Filtrar</button>
            </div>
        </div>
    </form>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <h3>Sin resultados</h3>
        <p>No hay recursos que coincidan con los filtros.</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead>
                <tr><th>Identificador</th><th>Título</th><th>Colección</th><th>Estado</th><th>Acceso</th><th>Actualizado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/recursos/' . $row['id'])) ?>"><strong><?= e($row['public_id']) ?></strong></a></td>
                    <td>
                        <?= e(mb_substr((string) $row['title'], 0, 70)) ?>
                        <?php if (!empty($row['document_type_name'])): ?><br><span class="small muted"><?= e($row['document_type_name']) ?></span><?php endif; ?>
                    </td>
                    <td class="small"><?= e($row['community_name'] ?? '—') ?> / <?= e($row['collection_name'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= e(strtolower((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td class="small"><?= e($row['access_level']) ?></td>
                    <td class="small"><?= format_date_es($row['updated_at']) ?></td>
                    <td>
                        <div class="flex">
                            <a class="btn btn-sm" href="<?= e(url('/admin/recursos/' . $row['id'])) ?>">Ver</a>
                            <?php if (in_array($row['status'], ['DRAFT', 'CHANGES_REQUESTED', 'REJECTED'], true) && Authorization::can('items.edit_all')): ?>
                                <a class="btn btn-sm btn-secondary" href="<?= e(url('/admin/recursos/' . $row['id'] . '/editar')) ?>">Editar</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= View::partial('components/pagination', ['page' => $page, 'total' => $total, 'perPage' => 20, 'filters' => [], 'label' => 'recurso(s)']) ?>
<?php endif; ?>
