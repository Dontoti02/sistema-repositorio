<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $roles */
/** @var array<int,int> $counts */
?>
<h1>Roles y permisos</h1>
<p class="muted">Cada rol agrupa permisos. Los usuarios obtienen permisos a través de los roles asignados a su membresía.</p>

<div class="table-wrap mt-3">
    <table>
        <thead>
            <tr><th>Rol</th><th>Identificador</th><th>Usuarios activos</th><th>Tipo</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($roles as $role): ?>
            <tr>
                <td>
                    <strong><?= e($role['name']) ?></strong>
                    <?php if (!empty($role['description'])): ?><br><span class="small muted"><?= e($role['description']) ?></span><?php endif; ?>
                </td>
                <td><code><?= e($role['slug']) ?></code></td>
                <td><?= number_format($counts[(int) $role['id']] ?? 0, 0, ',', '.') ?></td>
                <td>
                    <?php if ((int) $role['is_system'] === 1): ?>
                        <span class="badge badge-status">Sistema</span>
                    <?php else: ?>
                        <span class="muted small">Personalizado</span>
                    <?php endif; ?>
                </td>
                <td><a class="btn btn-sm" href="<?= e(url('/admin/roles/' . $role['id'] . '/permisos')) ?>">Asignar permisos</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
