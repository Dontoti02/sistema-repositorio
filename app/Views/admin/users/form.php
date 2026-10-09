<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Alta/edición de usuario.
 *
 * @var array<string,mixed>|null $user
 * @var array<int,array<string,mixed>> $roles
 * @var array<int> $selectedRoleIds
 */
$isEdit = $user !== null;
$action = $isEdit ? url('/admin/usuarios/' . $user['id']) : url('/admin/usuarios');
?>
<h1><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/usuarios')) ?>">← Volver a la lista</a></p>

<div class="card mt-3" style="max-width:760px">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>

        <div class="field mb-2">
            <label for="full_name">Nombre completo *</label>
            <input type="text" id="full_name" name="full_name" required maxlength="190"
                   value="<?= e($old['full_name'] ?? $user['full_name'] ?? '') ?>">
        </div>

        <div class="field mb-2">
            <label for="email">Correo electrónico *</label>
            <input type="email" id="email" name="email" required maxlength="190"
                   value="<?= e($old['email'] ?? $user['email'] ?? '') ?>">
        </div>

        <?php if (!$isEdit): ?>
            <div class="field mb-2">
                <label for="password">Contraseña inicial * (mínimo 10 caracteres)</label>
                <input type="password" id="password" name="password" required minlength="10" maxlength="100"
                       autocomplete="new-password">
            </div>
            <div class="field mb-2">
                <label for="password_confirmation">Confirmar contraseña *</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       minlength="10" maxlength="100" autocomplete="new-password">
            </div>
        <?php endif; ?>

        <fieldset class="mb-2" style="border:1px solid var(--border);border-radius:8px;padding:14px">
            <legend style="font-weight:700;padding:0 6px">Roles en este instituto</legend>
            <p class="small muted">Los permisos efectivos se derivan de los roles seleccionados.</p>
            <div class="grid grid-2">
                <?php foreach ($roles as $role): ?>
                    <label class="flex gap-1" style="align-items:center">
                        <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>"
                            <?= in_array((int) $role['id'], $selectedRoleIds, true) ? 'checked' : '' ?>>
                        <span><?= e($role['name']) ?></span>
                        <?php if ((int) $role['is_system'] === 1): ?><span class="badge badge-status">sistema</span><?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($roles === []): ?>
                <p class="muted">No hay roles definidos en la institución.</p>
            <?php endif; ?>
        </fieldset>

        <?php if ($isEdit): ?>
            <label class="flex gap-1 mb-2" style="align-items:center">
                <input type="checkbox" name="must_change_password" value="1"
                    <?= (int) ($user['must_change_password'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span>Exigir cambio de contraseña en el próximo inicio de sesión</span>
            </label>
        <?php endif; ?>

        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/usuarios')) ?>">Cancelar</a>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <hr class="divider">
        <form method="post" action="<?= e(url('/admin/usuarios/' . $user['id'] . '/restablecer-contrasena')) ?>"
              data-confirm="Se generará una nueva contraseña temporal y se cerrarán las sesiones activas de este usuario. ¿Continuar?">
            <?= Csrf::field() ?>
            <button class="btn btn-secondary" type="submit">Restablecer contraseña</button>
        </form>
    <?php endif; ?>
</div>
