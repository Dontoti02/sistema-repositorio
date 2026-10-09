<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Asignación de permisos por rol.
 *
 * @var array<string,mixed> $role
 * @var array<string,array<int,array<string,mixed>>> $grouped
 */
?>
<h1>Permisos del rol: <?= e($role['name']) ?></h1>
<p class="muted">
    Los cambios afectan de inmediato a todos los usuarios con este rol.
    <a href="<?= e(url('/admin/roles')) ?>">← Volver a roles</a>
</p>

<form method="post" action="<?= e(url('/admin/roles/' . $role['id'] . '/permisos')) ?>">
    <?= Csrf::field() ?>

    <div class="flex mb-2">
        <button class="btn btn-sm btn-secondary" type="button" data-check-all>Seleccionar todo</button>
        <button class="btn btn-sm btn-secondary" type="button" data-check-none>Deseleccionar todo</button>
    </div>

    <?php foreach ($grouped as $group => $perms): ?>
        <div class="card mb-2">
            <h2 style="font-size:1.02rem"><?= e($group) ?></h2>
            <div class="grid grid-3">
                <?php foreach ($perms as $perm): ?>
                    <label class="flex gap-1" style="align-items:center">
                        <input type="checkbox" name="permissions[]" value="<?= (int) $perm['id'] ?>"
                            <?= !empty($perm['assigned']) ? 'checked' : '' ?>>
                        <span><?= e($perm['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="flex mt-3">
        <button class="btn btn-primary" type="submit">Guardar permisos</button>
        <a class="btn btn-secondary" href="<?= e(url('/admin/roles')) ?>">Cancelar</a>
    </div>
</form>
