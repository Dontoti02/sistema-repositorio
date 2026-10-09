<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\View;

/** @var array<int,array<string,mixed>> $rows */
/** @var int $total */
/** @var int $page */
/** @var string $q */
/** @var string $status */
?>
<div class="flex-between">
    <div>
        <h1>Usuarios</h1>
        <p class="muted">Personal con acceso al repositorio institucional (<?= number_format($total, 0, ',', '.') ?> usuario(s)).</p>
    </div>
    <?php if (\App\Core\Authorization::can('users.create')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/usuarios/nuevo')) ?>">Nuevo usuario</a>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/usuarios')) ?>" class="flex">
        <label class="visually-hidden" for="q">Buscar usuario</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o correo…" maxlength="190" style="flex:1">
        <label class="visually-hidden" for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="">Todos los estados</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option>
            <option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>>Suspendidos</option>
        </select>
        <button class="btn" type="submit">Buscar</button>
    </form>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <h3>Sin resultados</h3>
        <p>No hay usuarios que coincidan con la búsqueda.</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Roles</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td>
                        <?php foreach ($row['roles'] as $r): ?>
                            <span class="badge badge-status"><?= e($r['name']) ?></span>
                        <?php endforeach; ?>
                        <?php if ($row['roles'] === []): ?><span class="muted small">Sin roles</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['membership_status'] === 'active'): ?>
                            <span class="badge badge-published">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">Suspendido</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['last_login_at'] ? format_date_es($row['last_login_at']) : '<span class="muted">Nunca</span>' ?></td>
                    <td>
                        <div class="flex">
                            <?php if (\App\Core\Authorization::can('users.update')): ?>
                                <a class="btn btn-sm" href="<?= e(url('/admin/usuarios/' . $row['id'] . '/editar')) ?>">Editar</a>
                            <?php endif; ?>
                            <?php if (\App\Core\Authorization::can('users.disable') && (int) $row['id'] !== (int) Auth::id()): ?>
                                <form method="post" action="<?= e(url('/admin/usuarios/' . $row['id'] . '/suspender')) ?>"
                                      data-confirm="<?= $row['membership_status'] === 'active' ? '¿Suspender este usuario? Perderá el acceso de inmediato.' : '¿Reactivar este usuario?' ?>">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-secondary" type="submit"><?= $row['membership_status'] === 'active' ? 'Suspender' : 'Reactivar' ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= View::partial('components/pagination', ['page' => $page, 'total' => $total, 'perPage' => 20, 'filters' => [], 'label' => 'usuario(s)']) ?>
<?php endif; ?>
