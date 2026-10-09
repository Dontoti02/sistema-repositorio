<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed>|null $type */
$isEdit = $type !== null;
$action = $isEdit ? url('/admin/tipos-documentales/' . $type['id']) : url('/admin/tipos-documentales');
?>
<h1><?= $isEdit ? 'Editar tipo documental' : 'Nuevo tipo documental' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/tipos-documentales')) ?>">← Volver a la lista</a></p>

<div class="card mt-3" style="max-width:680px">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="field mb-2">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required maxlength="140"
                   placeholder="Ej.: Proyecto de investigación"
                   value="<?= e($old['name'] ?? $type['name'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="slug">Identificador web (slug)</label>
            <input type="text" id="slug" name="slug" maxlength="100" pattern="[a-z0-9\-]*"
                   placeholder="Se genera automáticamente si se deja vacío"
                   value="<?= e($old['slug'] ?? $type['slug'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="description">Descripción</label>
            <input type="text" id="description" name="description" maxlength="255"
                   value="<?= e($old['description'] ?? $type['description'] ?? '') ?>">
        </div>
        <?php
        $oldInput = is_array($old ?? null) ? $old : [];
        $requiresChecked = is_array($old ?? null)
            ? ($oldInput['requires_degree_fields'] ?? null) !== null
            : (int) ($type['requires_degree_fields'] ?? 0) === 1;
        $activeChecked = is_array($old ?? null)
            ? ($oldInput['is_active'] ?? null) !== null
            : (int) ($type['is_active'] ?? 1) === 1;
        ?>
        <label class="flex gap-1 mb-1" style="align-items:center">
            <input type="checkbox" name="requires_degree_fields" value="1"<?= $requiresChecked ? ' checked' : '' ?>>
            <span>Incluye campos específicos de trabajo de grado (tesis, asesor, jurados)</span>
        </label>
        <label class="flex gap-1 mb-2" style="align-items:center">
            <input type="checkbox" name="is_active" value="1"<?= $activeChecked ? ' checked' : '' ?>>
            <span>Activo (disponible para depósitos)</span>
        </label>
        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear tipo' ?></button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/tipos-documentales')) ?>">Cancelar</a>
        </div>
    </form>
</div>
