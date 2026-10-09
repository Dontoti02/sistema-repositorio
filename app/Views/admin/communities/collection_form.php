<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Alta/edición de colección.
 *
 * @var array<string,mixed>|null $collection
 * @var array<int,array<string,mixed>> $communities
 * @var int $defaultCommunityId
 */
$isEdit = $collection !== null;
$action = $isEdit ? url('/admin/comunidades/colecciones/' . $collection['id']) : url('/admin/comunidades/colecciones');
$currentCommunity = $old['community_id'] ?? $defaultCommunityId;
?>
<h1><?= $isEdit ? 'Editar colección' : 'Nueva colección' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/comunidades')) ?>">← Volver a la lista</a></p>

<?php if ($communities === []): ?>
    <div class="alert alert-warning">
        Primero debe crear una comunidad antes de crear colecciones.
        <a href="<?= e(url('/admin/comunidades/nuevo')) ?>">Crear comunidad</a>
    </div>
<?php else: ?>
    <div class="card mt-3" style="max-width:720px">
        <form method="post" action="<?= e($action) ?>">
            <?= Csrf::field() ?>
            <div class="field mb-2">
                <label for="community_id">Comunidad *</label>
                <select id="community_id" name="community_id" required>
                    <?php foreach ($communities as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === (int) $currentCommunity ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field mb-2">
                <label for="name">Nombre *</label>
                <input type="text" id="name" name="name" required maxlength="190"
                       value="<?= e($old['name'] ?? $collection['name'] ?? '') ?>">
            </div>
            <div class="field mb-2">
                <label for="slug">Identificador web (slug)</label>
                <input type="text" id="slug" name="slug" maxlength="140" pattern="[a-z0-9\-]*"
                       placeholder="Se genera automáticamente si se deja vacío"
                       value="<?= e($old['slug'] ?? $collection['slug'] ?? '') ?>">
            </div>
            <div class="field mb-2">
                <label for="description">Descripción y política de la colección</label>
                <textarea id="description" name="description" rows="5" maxlength="8000"><?= e($old['description'] ?? $collection['description'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-3 mb-2">
                <?php
                $oldData = is_array($old ?? null) ? $old : [];
                $p = static function (string $key, mixed $default) use ($oldData, $collection): mixed {
                    if ($oldData !== []) {
                        return ($oldData[$key] ?? null) !== null;
                    }
                    return (int) ($collection[$key] ?? $default) === 1;
                };
                ?>
                <label class="flex gap-1" style="align-items:center">
                    <input type="checkbox" name="is_public" value="1"<?= $p('is_public', 1) ? ' checked' : '' ?>>
                    <span>Visible públicamente</span>
                </label>
                <label class="flex gap-1" style="align-items:center">
                    <input type="checkbox" name="deposit_enabled" value="1"<?= $p('deposit_enabled', 1) ? ' checked' : '' ?>>
                    <span>Acepta depósitos</span>
                </label>
                <label class="flex gap-1" style="align-items:center">
                    <input type="checkbox" name="requires_approval" value="1"<?= $p('requires_approval', 1) ? ' checked' : '' ?>>
                    <span>Requiere aprobación</span>
                </label>
            </div>
            <div class="flex mt-3">
                <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear colección' ?></button>
                <a class="btn btn-secondary" href="<?= e(url('/admin/comunidades')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>
