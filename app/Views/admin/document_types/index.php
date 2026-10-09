<?php

declare(strict_types=1);

use App\Core\Authorization;

/** @var array<int,array<string,mixed>> $types */
?>
<div class="flex-between">
    <div>
        <h1>Tipos documentales</h1>
        <p class="muted">Catálogo de tipos aplicables al depósito (tesis, informes, artículos, proyectos, etc.).</p>
    </div>
    <?php if (Authorization::can('metadata.manage')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/tipos-documentales/nuevo')) ?>">Nuevo tipo</a>
    <?php endif; ?>
</div>

<div class="table-wrap mt-3">
    <table>
        <thead><tr><th>Nombre</th><th>Campos de grado</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($types as $t): ?>
            <tr>
                <td>
                    <strong><?= e($t['name']) ?></strong>
                    <?php if (!empty($t['description'])): ?><br><span class="small muted"><?= e($t['description']) ?></span><?php endif; ?>
                </td>
                <td><?= (int) $t['requires_degree_fields'] === 1 ? 'Sí' : 'No' ?></td>
                <td>
                    <?php if ((int) $t['is_active'] === 1): ?>
                        <span class="badge badge-published">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-status">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td><a class="btn btn-sm" href="<?= e(url('/admin/tipos-documentales/' . $t['id'] . '/editar')) ?>">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
