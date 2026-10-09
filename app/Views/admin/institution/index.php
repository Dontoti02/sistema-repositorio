<?php

declare(strict_types=1);

/**
 * Institucion (6.12): ficha institucional, licencias de reutilizacion
 * y embargos activos.
 *
 * @var array<string,mixed> $inst
 * @var array<int,array<string,mixed>> $licenses
 * @var array<int,array<string,mixed>> $embargos
 * @var bool $canUpdate
 * @var bool $canLicenses
 * @var bool $canEmbargos
 */
?>
<div class="flex-between">
    <div>
        <h1>Institución</h1>
        <p class="muted">Ficha institucional, catálogo de licencias de reutilización y embargos de acceso.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/institucion')) ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="card mt-3">
        <h2>Ficha institucional</h2>
        <?php if (!$canUpdate): ?>
            <p class="small muted">Solo lectura: requiere el permiso <code>institution.update</code>.</p>
        <?php endif; ?>
        <div class="form-grid mt-2">
            <div class="field">
                <label for="name">Nombre oficial *</label>
                <input type="text" id="name" name="name" maxlength="200" required value="<?= e((string) $inst['name']) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="short_name">Nombre corto</label>
                <input type="text" id="short_name" name="short_name" maxlength="120" value="<?= e((string) ($inst['short_name'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="institution_type">Tipo de institución</label>
                <input type="text" id="institution_type" name="institution_type" maxlength="40" value="<?= e((string) ($inst['institution_type'] ?? '')) ?>" placeholder="Instituto superior tecnológico"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="institution_code">Código institucional</label>
                <input type="text" id="institution_code" name="institution_code" maxlength="40" value="<?= e((string) ($inst['institution_code'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="ruc">RUC</label>
                <input type="text" id="ruc" name="ruc" maxlength="15" inputmode="numeric" value="<?= e((string) ($inst['ruc'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="dre_gre">DRE / GRE</label>
                <input type="text" id="dre_gre" name="dre_gre" maxlength="160" value="<?= e((string) ($inst['dre_gre'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="address">Dirección</label>
                <input type="text" id="address" name="address" maxlength="255" value="<?= e((string) ($inst['address'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="phone">Teléfono</label>
                <input type="text" id="phone" name="phone" maxlength="60" value="<?= e((string) ($inst['phone'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="email">Correo institucional</label>
                <input type="email" id="email" name="email" maxlength="190" value="<?= e((string) ($inst['email'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="support_email">Correo de soporte</label>
                <input type="email" id="support_email" name="support_email" maxlength="190" value="<?= e((string) ($inst['support_email'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="website">Sitio web</label>
                <input type="url" id="website" name="website" maxlength="255" value="<?= e((string) ($inst['website'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="coordinator_name">Coordinador(a) del repositorio</label>
                <input type="text" id="coordinator_name" name="coordinator_name" maxlength="190" value="<?= e((string) ($inst['coordinator_name'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field">
                <label for="coordinator_email">Correo del coordinador(a)</label>
                <input type="email" id="coordinator_email" name="coordinator_email" maxlength="190" value="<?= e((string) ($inst['coordinator_email'] ?? '')) ?>"<?= $canUpdate ? '' : ' readonly' ?>>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="description">Descripción institucional</label>
                <textarea id="description" name="description" rows="3" maxlength="5000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['description'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="mission">Misión</label>
                <textarea id="mission" name="mission" rows="3" maxlength="5000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['mission'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="vision">Visión</label>
                <textarea id="vision" name="vision" rows="3" maxlength="5000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['vision'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="deposit_policy">Política de depósito</label>
                <textarea id="deposit_policy" name="deposit_policy" rows="4" maxlength="10000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['deposit_policy'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="privacy_policy">Política de privacidad</label>
                <textarea id="privacy_policy" name="privacy_policy" rows="4" maxlength="10000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['privacy_policy'] ?? '')) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="about_text">Acerca de (texto público)</label>
                <textarea id="about_text" name="about_text" rows="4" maxlength="10000"<?= $canUpdate ? '' : ' readonly' ?>><?= e((string) ($inst['about_text'] ?? '')) ?></textarea>
            </div>
        </div>
        <?php if ($canUpdate): ?>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit">Guardar ficha</button>
            </div>
        <?php endif; ?>
    </div>
</form>

<div id="licencias" class="card mt-3">
    <h2>Licencias de reutilización</h2>
    <p class="small muted">Licencias aplicables a los recursos publicados. Solo la licencia predeterminada se sugiere en el formulario de depósito.</p>

    <?php if ($canLicenses): ?>
    <form method="post" action="<?= e(url('/admin/institucion/licencias')) ?>">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-grid mt-2">
            <div class="field">
                <label for="lic-code">Código *</label>
                <input type="text" id="lic-code" name="code" maxlength="60" required placeholder="CC-BY-4.0">
            </div>
            <div class="field">
                <label for="lic-name">Nombre *</label>
                <input type="text" id="lic-name" name="name" maxlength="140" required placeholder="Atribución 4.0 Internacional">
            </div>
            <div class="field">
                <label for="lic-url">URL</label>
                <input type="url" id="lic-url" name="url" maxlength="255" placeholder="https://creativecommons.org/licenses/by/4.0/">
            </div>
            <div class="field">
                <label for="lic-desc">Descripción</label>
                <input type="text" id="lic-desc" name="description" maxlength="500">
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <label class="small"><input type="checkbox" name="requires_attribution" value="1"> Requiere atribución</label>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <label class="small"><input type="checkbox" name="is_default" value="1"> Predeterminada</label>
            </div>
        </div>
        <div class="mt-2">
            <button class="btn btn-primary" type="submit">Añadir licencia</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="table-wrap mt-3">
        <table>
            <thead><tr><th>Código</th><th>Nombre</th><th>URL</th><th>Atribución</th><th>Predet.</th><th>Activa</th><?php if ($canLicenses): ?><th>Gestión</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($licenses as $lic): ?>
                <tr>
                    <td class="small"><code><?= e($lic['code']) ?></code><?= (int) $lic['is_global'] === 1 ? ' <span class="muted">(global)</span>' : '' ?></td>
                    <td class="small"><?= e($lic['name']) ?></td>
                    <td class="small" style="max-width:220px;word-break:break-all">
                        <?php if ($lic['url'] !== null && $lic['url'] !== ''): ?>
                            <a href="<?= e((string) $lic['url']) ?>" target="_blank" rel="noopener nofollow">ver enlace</a>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= (int) $lic['requires_attribution'] === 1 ? 'Sí' : 'No' ?></td>
                    <td class="small"><?= (int) $lic['is_default'] === 1 ? 'Sí' : '—' ?></td>
                    <td><span class="badge <?= (int) $lic['is_active'] === 1 ? 'badge-published' : 'badge-archived' ?>"><?= (int) $lic['is_active'] === 1 ? 'Activa' : 'Inactiva' ?></span></td>
                    <?php if ($canLicenses): ?>
                    <td style="white-space:nowrap">
                        <details style="display:inline-block;vertical-align:middle">
                            <summary class="btn btn-sm" style="display:inline-block">Editar</summary>
                            <form method="post" action="<?= e(url('/admin/institucion/licencias/' . (int) $lic['id'])) ?>" class="card mt-2" style="min-width:320px">
                                <?= \App\Core\Csrf::field() ?>
                                <div class="field">
                                    <label>Nombre *</label>
                                    <input type="text" name="name" maxlength="140" required value="<?= e($lic['name']) ?>">
                                </div>
                                <div class="field">
                                    <label>URL</label>
                                    <input type="url" name="url" maxlength="255" value="<?= e((string) ($lic['url'] ?? '')) ?>">
                                </div>
                                <div class="field">
                                    <label>Descripción</label>
                                    <input type="text" name="description" maxlength="500" value="<?= e((string) ($lic['description'] ?? '')) ?>">
                                </div>
                                <label class="small"><input type="checkbox" name="requires_attribution" value="1"<?= (int) $lic['requires_attribution'] === 1 ? ' checked' : '' ?>> Requiere atribución</label><br>
                                <label class="small"><input type="checkbox" name="is_default" value="1"<?= (int) $lic['is_default'] === 1 ? ' checked' : '' ?>> Predeterminada</label>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                                </div>
                            </form>
                        </details>
                        <form method="post" action="<?= e(url('/admin/institucion/licencias/' . (int) $lic['id'] . '/alternar')) ?>" style="display:inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm" type="submit"><?= (int) $lic['is_active'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="embargos" class="card mt-3">
    <h2>Embargos</h2>
    <p class="small muted">Recursos con acceso restringido por embargo. Al vencer (o liberar manualmente) el archivo queda accesible; la acción queda en la bitácora y se notifica al depositario.</p>
    <?php if ($embargos === []): ?>
        <p class="muted">No hay embargos activos.</p>
    <?php else: ?>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Recurso</th><th>Título</th><th>Depositario</th><th>Embargo hasta (UTC)</th><th>Acceso</th><?php if ($canEmbargos): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($embargos as $emb): ?>
                    <tr>
                        <td class="small">
                            <code><?= e($emb['public_id']) ?></code><br>
                            <a href="<?= e(url('/admin/recursos/' . (int) $emb['id'])) ?>">ver ficha</a>
                        </td>
                        <td class="small" style="max-width:300px"><?= e(mb_substr((string) ($emb['title'] ?? ''), 0, 120)) ?></td>
                        <td class="small"><?= e((string) ($emb['owner_name'] ?? '—')) ?></td>
                        <td class="small"><?= $emb['embargo_until'] !== null ? format_date_es($emb['embargo_until']) : '<span class="muted">sin fecha</span>' ?></td>
                        <td><span class="badge badge-embargoed"><?= e((string) $emb['access_level']) ?></span></td>
                        <?php if ($canEmbargos): ?>
                        <td>
                            <?php if ($emb['access_level'] === 'embargoed'): ?>
                                <form method="post" action="<?= e(url('/admin/institucion/embargos/' . (int) $emb['id'] . '/liberar')) ?>" data-confirm="¿Liberar el embargo ahora? El archivo quedará accesible.">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-primary" type="submit">Liberar ahora</button>
                                </form>
                            <?php else: ?>
                                <span class="muted small">solo vencimiento</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
