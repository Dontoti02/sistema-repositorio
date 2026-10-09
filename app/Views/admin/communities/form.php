<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed>|null $community */
$isEdit = $community !== null;
$action = $isEdit ? url('/admin/comunidades/' . $community['id']) : url('/admin/comunidades');
?>
<h1><?= $isEdit ? 'Editar comunidad' : 'Nueva comunidad' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/comunidades')) ?>">← Volver a la lista</a></p>

<div class="card mt-3" style="max-width:720px">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="field mb-2">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required maxlength="190"
                   value="<?= e($old['name'] ?? $community['name'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="slug">Identificador web (slug)</label>
            <input type="text" id="slug" name="slug" maxlength="140" pattern="[a-z0-9\-]*"
                   placeholder="Se genera automáticamente si se deja vacío"
                   value="<?= e($old['slug'] ?? $community['slug'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="5" maxlength="8000"><?= e($old['description'] ?? $community['description'] ?? '') ?></textarea>
        </div>
        <label class="flex gap-1 mb-2" style="align-items:center">
            <?php $checked = is_array($old ?? null) ? ($old['is_public'] ?? null) !== null : (int) ($community['is_public'] ?? 1) === 1; ?>
            <input type="checkbox" name="is_public" value="1"<?= $checked ? ' checked' : '' ?>>
            <span>Visible en el portal público</span>
        </label>
        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear comunidad' ?></button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/comunidades')) ?>">Cancelar</a>
        </div>
    </form>
</div>
