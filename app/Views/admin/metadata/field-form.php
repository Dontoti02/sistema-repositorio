<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed>|null $field */
/** @var array<int,array<string,mixed>> $vocabularies */
$isEdit = $field !== null;
$action = $isEdit ? url('/admin/metadatos/campos/' . $field['id']) : url('/admin/metadatos/campos');
?>
<h1><?= $isEdit ? 'Editar campo' : 'Nuevo campo de metadatos' ?></h1>
<p class="muted"><a href="<?= e(url('/admin/metadatos')) ?>">← Volver a metadatos</a></p>

<div class="card mt-3" style="max-width:760px">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="field_key">Clave *</label>
                <input type="text" id="field_key" name="field_key" required maxlength="80" pattern="[a-z0-9_]+"
                       title="Solo minúsculas, números y guion bajo"
                       placeholder="titulo_alternativo" value="<?= e($old['field_key'] ?? $field['field_key'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="namespace">Espacio de nombres *</label>
                <?php $ns = $old['namespace'] ?? $field['namespace'] ?? 'local'; ?>
                <select id="namespace" name="namespace" required>
                    <option value="local"<?= $ns === 'local' ? ' selected' : '' ?>>local</option>
                    <option value="dublin_core"<?= $ns === 'dublin_core' ? ' selected' : '' ?>>dublin_core</option>
                    <option value="dim"<?= $ns === 'dim' ? ' selected' : '' ?>>dim</option>
                </select>
            </div>
        </div>
        <div class="field mb-2">
            <label for="label">Etiqueta *</label>
            <input type="text" id="label" name="label" required maxlength="140"
                   value="<?= e($old['label'] ?? $field['label'] ?? '') ?>">
        </div>
        <div class="field mb-2">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="2" maxlength="255"><?= e($old['description'] ?? $field['description'] ?? '') ?></textarea>
        </div>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="datatype">Tipo de dato *</label>
                <?php
                $types = ['text', 'textarea', 'select', 'date', 'url', 'integer', 'boolean'];
                $dt = $old['datatype'] ?? $field['datatype'] ?? 'text';
                ?>
                <select id="datatype" name="datatype" required>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>"<?= $dt === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="cardinality">Cardinalidad *</label>
                <?php $card = $old['cardinality'] ?? $field['cardinality'] ?? 'single'; ?>
                <select id="cardinality" name="cardinality" required>
                    <option value="single"<?= $card === 'single' ? ' selected' : '' ?>>Simple (un valor)</option>
                    <option value="multiple"<?= $card === 'multiple' ? ' selected' : '' ?>>Múltiple (varios valores)</option>
                </select>
            </div>
            <div class="field">
                <label for="vocabulary_id">Vocabulario controlado</label>
                <?php $vid = (int) ($old['vocabulary_id'] ?? $field['vocabulary_id'] ?? 0); ?>
                <select id="vocabulary_id" name="vocabulary_id">
                    <option value="">— sin vocabulario —</option>
                    <?php foreach ($vocabularies as $v): ?>
                        <option value="<?= (int) $v['id'] ?>"<?= $vid === (int) $v['id'] ? ' selected' : '' ?>><?= e($v['name']) ?> (<?= e($v['slug']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="validation_rule">Regla de validación</label>
                <input type="text" id="validation_rule" name="validation_rule" maxlength="190"
                       placeholder="regex:…, max:255…" value="<?= e($old['validation_rule'] ?? $field['validation_rule'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="sort_order">Orden</label>
                <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                       value="<?= e((string) ($old['sort_order'] ?? $field['sort_order'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-grid mb-2">
            <div class="field">
                <label for="example_text">Ejemplo</label>
                <input type="text" id="example_text" name="example_text" maxlength="190"
                       value="<?= e($old['example_text'] ?? $field['example_text'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="help_text">Texto de ayuda</label>
                <input type="text" id="help_text" name="help_text" maxlength="255"
                       value="<?= e($old['help_text'] ?? $field['help_text'] ?? '') ?>">
            </div>
        </div>
        <?php
        $pub = isset($old['is_public']) ? $old['is_public'] === '1' : (int) ($field['is_public'] ?? 1) === 1;
        $int = isset($old['is_internal']) ? $old['is_internal'] === '1' : (int) ($field['is_internal'] ?? 0) === 1;
        ?>
        <div class="flex gap-3 mb-2">
            <label><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" <?= $pub ? 'checked' : '' ?>> Visible en portada público</label>
            <label><input type="hidden" name="is_internal" value="0"><input type="checkbox" name="is_internal" value="1" <?= $int ? 'checked' : '' ?>> Solo interno</label>
        </div>
        <div class="flex mt-3">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear campo' ?></button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/metadatos')) ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <div class="card mt-3" style="max-width:760px">
        <h3>Zona de peligro</h3>
        <p class="muted">La eliminación quita el campo de todos los perfiles. No es posible si el campo ya tiene valores en recursos.</p>
        <form method="post" action="<?= e(url('/admin/metadatos/campos/' . $field['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar el campo «<?= e($field['label']) ?>»?">
            <?= Csrf::field() ?>
            <button class="btn btn-danger" type="submit">Eliminar campo</button>
        </form>
    </div>
<?php endif; ?>
