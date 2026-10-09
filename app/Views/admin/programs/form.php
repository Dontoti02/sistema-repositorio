<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed>|null $program */
$isEdit = $program !== null;
$action = $isEdit ? url('/admin/programas/' . $program['id']) : url('/admin/programas');
?>
<h1><?= $isEdit ? 'Editar programa' : 'Nuevo programa' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/programas')) ?>">← Volver a la lista</a></p>

<div class="card mt-3" style="max-width:720px">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="field mb-2">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required maxlength="190"
                   value="<?= e($old['name'] ?? $program['name'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="slug">Identificador web (slug)</label>
            <input type="text" id="slug" name="slug" maxlength="140" pattern="[a-z0-9\-]*"
                   placeholder="Se genera automáticamente si se deja vacío"
                   value="<?= e($old['slug'] ?? $program['slug'] ?? '') ?>">
        </div>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="code">Código</label>
                <input type="text" id="code" name="code" maxlength="40" value="<?= e($old['code'] ?? $program['code'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="level">Nivel</label>
                <input type="text" id="level" name="level" maxlength="40" placeholder="Técnico, Superior…" value="<?= e($old['level'] ?? $program['level'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="status">Estado *</label>
                <select id="status" name="status" required>
                    <?php $current = $old['status'] ?? $program['status'] ?? 'active'; ?>
                    <option value="active"<?= $current === 'active' ? ' selected' : '' ?>>Activo</option>
                    <option value="inactive"<?= $current === 'inactive' ? ' selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <div class="field mb-2">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="4" maxlength="4000"><?= e($old['description'] ?? $program['description'] ?? '') ?></textarea>
        </div>
        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear programa' ?></button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/programas')) ?>">Cancelar</a>
        </div>
    </form>
</div>
