<?php

declare(strict_types=1);

use App\Core\Authorization;
use App\Core\Csrf;

/** @var array<int,array<string,mixed>> $fields */
/** @var array<int,array<string,mixed>> $profiles */
/** @var array<int,array<string,mixed>> $vocabularies */
/** @var string $q */
$canEdit = Authorization::can('metadata.manage');
?>
<div class="flex-between">
    <div>
        <h1>Metadatos y perfiles</h1>
        <p class="muted">Diccionario de campos, vocabularios controlados y perfiles de captura.</p>
    </div>
    <?php if ($canEdit): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/metadatos/campos/nuevo')) ?>">Nuevo campo</a>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/metadatos')) ?>" class="flex">
        <label class="visually-hidden" for="q">Buscar campos</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar por clave o etiqueta…" maxlength="190" style="flex:1">
        <button class="btn" type="submit">Buscar</button>
    </form>
</div>

<h2 class="mt-3">Campos del diccionario</h2>
<p class="muted">Campos globales disponibles para perfiles, importación y exportación.</p>

<?php if ($fields === []): ?>
    <div class="empty-state">
        <h3>Sin campos</h3>
        <p>No hay campos que coincidan con la búsqueda.</p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-2">
        <table>
            <thead>
            <tr>
                <th>Etiqueta</th><th>Clave</th><th>Espacio</th><th>Tipo</th>
                <th>Vocabulario</th><th>Uso</th><th>Orden</th><?php if ($canEdit): ?><th></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($fields as $f): ?>
                <tr>
                    <td><strong><?= e($f['label']) ?></strong><?= $f['description'] ? '<br><span class="small muted">' . e($f['description']) . '</span>' : '' ?></td>
                    <td><code><?= e($f['field_key']) ?></code></td>
                    <td><?= e($f['namespace']) ?></td>
                    <td><?= e($f['datatype']) ?><br><span class="small muted"><?= $f['cardinality'] === 'multiple' ? 'múltiple' : 'simple' ?></span></td>
                    <td><?= e($f['vocab_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($f['is_public']): ?><span class="badge badge-published">público</span><?php endif; ?>
                        <?php if ($f['is_internal']): ?><span class="badge badge-status">interno</span><?php endif; ?>
                        <?php if (!$f['is_public'] && !$f['is_internal']): ?><span class="badge badge-status">oculto</span><?php endif; ?>
                    </td>
                    <td><?= (int) $f['sort_order'] ?></td>
                    <?php if ($canEdit): ?>
                        <td class="nowrap">
                            <a class="btn btn-sm" href="<?= e(url('/admin/metadatos/campos/' . $f['id'] . '/editar')) ?>">Editar</a>
                            <form method="post" action="<?= e(url('/admin/metadatos/campos/' . $f['id'] . '/eliminar')) ?>" class="inline-form" data-confirm="¿Eliminar el campo «<?= e($f['label']) ?>»? Se quitará de todos los perfiles.">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2 class="mt-3">Perfiles de metadatos</h2>
<p class="muted">Cada institución define sus perfiles de captura; el perfil por defecto se usa al depositar recursos.</p>

<div class="table-wrap mt-2">
    <table>
        <thead><tr><th>Nombre</th><th>Versión</th><th>Campos</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($profiles as $p): ?>
            <tr>
                <td>
                    <strong><?= e($p['name']) ?></strong><br>
                    <span class="small muted"><?= e($p['slug']) ?><?= $p['description'] ? ' — ' . e($p['description']) : '' ?></span>
                </td>
                <td><?= e($p['version']) ?></td>
                <td><?= (int) $p['field_count'] ?></td>
                <td class="nowrap">
                    <?php if ($p['is_default']): ?><span class="badge badge-published">por defecto</span><?php endif; ?>
                    <?php if ($p['is_active']): ?><span class="badge badge-status">activo</span><?php else: ?><span class="badge badge-status">inactivo</span><?php endif; ?>
                </td>
                <td class="nowrap">
                    <a class="btn btn-sm" href="<?= e(url('/admin/metadatos/perfiles/' . $p['id'])) ?>">Abrir</a>
                    <?php if ($canEdit): ?>
                        <form method="post" action="<?= e(url('/admin/metadatos/perfiles/' . $p['id'] . '/duplicar')) ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm" type="submit">Duplicar versión</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($profiles === []): ?>
            <tr><td colspan="5" class="muted">Aún no hay perfiles. Cree el primero aquí abajo.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($canEdit): ?>
    <div class="card mt-3" style="max-width:720px">
        <h3>Nuevo perfil</h3>
        <form method="post" action="<?= e(url('/admin/metadatos/perfiles')) ?>" class="mt-2">
            <?= Csrf::field() ?>
            <div class="field mb-2">
                <label for="p-name">Nombre *</label>
                <input type="text" id="p-name" name="name" required maxlength="140" value="<?= e($old['name'] ?? '') ?>">
            </div>
            <div class="form-grid mb-2">
                <div class="field">
                    <label for="p-version">Versión *</label>
                    <input type="text" id="p-version" name="version" required maxlength="30" placeholder="1.0" value="<?= e($old['version'] ?? '1.0') ?>">
                </div>
                <div class="field">
                    <label for="p-slug">Identificador web (slug)</label>
                    <input type="text" id="p-slug" name="slug" maxlength="100" pattern="[a-z0-9\-]*" placeholder="Se genera automáticamente" value="<?= e($old['slug'] ?? '') ?>">
                </div>
            </div>
            <div class="field mb-2">
                <label for="p-desc">Descripción</label>
                <textarea id="p-desc" name="description" rows="2" maxlength="255"><?= e($old['description'] ?? '') ?></textarea>
            </div>
            <div class="flex gap-3 mb-2">
                <label><input type="checkbox" name="is_default" value="1" <?= !empty($old['is_default']) ? 'checked' : '' ?>> Usar como perfil por defecto</label>
                <label><input type="checkbox" name="is_active" value="1" <?= ($old['is_active'] ?? '1') === '1' ? 'checked' : '' ?>> Perfil activo</label>
            </div>
            <button class="btn btn-primary" type="submit">Crear perfil</button>
        </form>
    </div>
<?php endif; ?>
