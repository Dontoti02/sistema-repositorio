<?php

declare(strict_types=1);

use App\Core\Authorization;

/** @var array<int,array<string,mixed>> $programs */
/** @var string $q */
/** @var string $status */
?>
<div class="flex-between">
    <div>
        <h1>Programas y dependencias</h1>
        <p class="muted">Oferta académica asociada a los recursos del repositorio.</p>
    </div>
    <?php if (Authorization::can('programs.manage')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/programas/nuevo')) ?>">Nuevo programa</a>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/programas')) ?>" class="flex">
        <label class="visually-hidden" for="q">Buscar</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o código…" maxlength="190" style="flex:1">
        <label class="visually-hidden" for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="">Todos</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option>
            <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn" type="submit">Filtrar</button>
    </form>
</div>

<?php if ($programs === []): ?>
    <div class="empty-state">
        <h3>Sin programas</h3>
        <p>No hay programas que coincidan. Cree el primero con «Nuevo programa».</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead><tr><th>Nombre</th><th>Código</th><th>Nivel</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($programs as $p): ?>
                <tr>
                    <td><strong><?= e($p['name']) ?></strong><br><span class="small muted">/programas/<?= e($p['slug']) ?></span></td>
                    <td><?= e($p['code'] ?? '—') ?></td>
                    <td><?= e($p['level'] ?? '—') ?></td>
                    <td>
                        <?php if ($p['status'] === 'active'): ?>
                            <span class="badge badge-published">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-status">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td><a class="btn btn-sm" href="<?= e(url('/admin/programas/' . $p['id'] . '/editar')) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
