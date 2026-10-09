<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed> $profile */
/** @var array<int,array<string,mixed>> $assigned */
/** @var array<int,array<string,mixed>> $fields */
$action = url('/admin/metadatos/perfiles/' . $profile['id']);
?>
<div class="flex-between">
    <div>
        <h1>Perfil: <?= e($profile['name']) ?></h1>
        <p class="muted"><a href="<?= e(url('/admin/metadatos')) ?>">← Volver a metadatos</a> · versión <?= e($profile['version']) ?> · <code><?= e($profile['slug']) ?></code></p>
    </div>
    <div class="flex gap-2">
        <form method="post" action="<?= e($action . '/duplicar') ?>">
            <?= Csrf::field() ?>
            <button class="btn" type="submit">Duplicar como nueva versión</button>
        </form>
    </div>
</div>

<div class="card mt-3" style="max-width:760px">
    <h3>Datos del perfil</h3>
    <form method="post" action="<?= e($action) ?>" class="mt-2">
        <?= Csrf::field() ?>
        <div class="field mb-2">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required maxlength="140" value="<?= e($old['name'] ?? $profile['name']) ?>">
        </div>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="version">Versión *</label>
                <input type="text" id="version" name="version" required maxlength="30" value="<?= e($old['version'] ?? $profile['version']) ?>">
            </div>
            <div class="field">
                <label for="slug">Identificador web (slug)</label>
                <input type="text" id="slug" name="slug" maxlength="100" pattern="[a-z0-9\-]*" placeholder="Se genera automáticamente" value="<?= e($old['slug'] ?? $profile['slug']) ?>">
            </div>
        </div>
        <div class="field mb-2">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="2" maxlength="255"><?= e($old['description'] ?? ($profile['description'] ?? '')) ?></textarea>
        </div>
        <?php
        $isDefault = isset($old['is_default']) ? $old['is_default'] === '1' : (int) $profile['is_default'] === 1;
        $isActive = isset($old['is_active']) ? $old['is_active'] === '1' : (int) $profile['is_active'] === 1;
        ?>
        <div class="flex gap-3 mb-2">
            <label><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" <?= $isDefault ? 'checked' : '' ?>> Perfil por defecto</label>
            <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>> Perfil activo</label>
        </div>
        <button class="btn btn-primary" type="submit">Guardar datos</button>
    </form>
</div>

<div class="card mt-3">
    <h3>Campos del perfil</h3>
    <p class="muted">Marque los campos que forman parte del perfil, su orden y si son obligatorios o visibles al público.</p>
    <form method="post" action="<?= e($action . '/campos') ?>">
        <?= Csrf::field() ?>
        <div class="table-wrap mt-2">
            <table>
                <thead>
                <tr>
                    <th>Incluir</th><th>Campo</th><th>Tipo</th>
                    <th>Etiqueta (sobrescribe)</th><th>Obligatorio</th><th>Público</th><th>Orden</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($fields as $f):
                    $fid = (int) $f['id'];
                    $a = $assigned[$fid] ?? null;
                    ?>
                    <tr>
                        <td><input type="checkbox" name="fields[<?= $fid ?>]" value="1" <?= $a !== null ? 'checked' : '' ?>></td>
                        <td>
                            <strong><?= e($f['label']) ?></strong><br>
                            <code class="small"><?= e($f['field_key']) ?></code>
                        </td>
                        <td><?= e($f['datatype']) ?><br><span class="small muted"><?= $f['cardinality'] === 'multiple' ? 'múltiple' : 'simple' ?></span></td>
                        <td><input type="text" name="label[<?= $fid ?>]" maxlength="140" placeholder="<?= e($f['label']) ?>" value="<?= e($a['label_override'] ?? '') ?>"></td>
                        <td><input type="checkbox" name="required[<?= $fid ?>]" value="1" <?= ($a['is_required'] ?? 0) ? 'checked' : '' ?>></td>
                        <td><input type="checkbox" name="public[<?= $fid ?>]" value="1" <?= ($a === null || $a['show_in_public']) ? 'checked' : '' ?>></td>
                        <td><input type="number" name="order[<?= $fid ?>]" min="0" max="9999" style="width:5.5rem" value="<?= e((string) ($a['sort_order'] ?? '')) ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit">Guardar campos del perfil</button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/metadatos')) ?>">Volver</a>
        </div>
    </form>
</div>
