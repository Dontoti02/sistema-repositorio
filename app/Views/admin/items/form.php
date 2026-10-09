<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Asistente de depósito guiado en 6 pasos y edición de metadatos.
 *
 * @var array<string,mixed>|null $item
 * @var array<int,array<string,mixed>> $authors
 * @var string $keywords
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $programs
 */
$isEdit = $item !== null;
$action = $isEdit ? url('/admin/recursos/' . $item['id']) : url('/admin/recursos');
$oldData = is_array($old ?? null) ? $old : [];
$val = static function (string $key, mixed $default = '') use ($oldData, $item): mixed {
    if ($oldData !== []) {
        return $oldData[$key] ?? $default;
    }
    return $item[$key] ?? $default;
};
$authors = $authors === [] ? [['full_name' => '', 'author_role' => 'author', 'affiliation' => '', 'orcid' => '', 'is_corresponding' => 0]] : $authors;
?>
<div class="flex-between mb-4">
    <div>
        <h1><?= $isEdit ? 'Editar recurso' : 'Depósito guiado de documentos' ?></h1>
        <p class="muted">
            <?php if ($isEdit): ?>
                <?= e($item['public_id']) ?> · <span class="badge badge-<?= e(strtolower((string) $item['status'])) ?>"><?= e($item['status']) ?></span>
                · <a href="<?= e(url('/admin/recursos/' . $item['id'])) ?>">Ver ficha del recurso</a>
            <?php else: ?>
                Asistente guiado de 6 pasos para catalogar y enviar recursos a revisión editorial.
            <?php endif; ?>
        </p>
    </div>
</div>

<div id="deposit-wizard">
    <!-- Stepper de 6 pasos -->
    <nav class="wizard-stepper" aria-label="Etapas del depósito">
        <div class="wizard-step active" data-step="1">
            <span class="wizard-step-circle">1</span>
            <span class="wizard-step-label">Colección y Tipo</span>
        </div>
        <div class="wizard-step" data-step="2">
            <span class="wizard-step-circle">2</span>
            <span class="wizard-step-label">Título y Resumen</span>
        </div>
        <div class="wizard-step" data-step="3">
            <span class="wizard-step-circle">3</span>
            <span class="wizard-step-label">Autores</span>
        </div>
        <div class="wizard-step" data-step="4">
            <span class="wizard-step-circle">4</span>
            <span class="wizard-step-label">Metadatos</span>
        </div>
        <div class="wizard-step" data-step="5">
            <span class="wizard-step-circle">5</span>
            <span class="wizard-step-label">Acceso y Derechos</span>
        </div>
        <div class="wizard-step" data-step="6">
            <span class="wizard-step-circle">6</span>
            <span class="wizard-step-label">Resumen y Envío</span>
        </div>
    </nav>

    <form method="post" action="<?= e($action) ?>"<?= $isEdit ? ' data-autosave' : '' ?>>
        <?= Csrf::field() ?>

        <!-- PASO 1: COLECCIÓN Y TIPO DOCUMENTAL -->
        <div class="wizard-pane card" id="step-pane-1">
            <h2 style="font-size:1.15rem;margin-bottom:14px;">Paso 1: Comunidad, colección y tipo</h2>
            <p class="small text-muted mb-4">Seleccione la colección académica donde residirá la producción institucional.</p>

            <div class="form-grid">
                <div class="field">
                    <label for="collection_id">Colección de destino *</label>
                    <select id="collection_id" name="collection_id" required class="form-control">
                        <option value="">Seleccione una colección…</option>
                        <?php $currentCollection = (int) $val('collection_id', 0); ?>
                        <?php foreach ($collections as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === $currentCollection ? ' selected' : '' ?>>
                                <?= e($c['community_name']) ?> / <?= e($c['name']) ?><?= (int) $c['deposit_enabled'] === 1 ? '' : ' (sin depósito)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="document_type_id">Tipo documental</label>
                    <select id="document_type_id" name="document_type_id" class="form-control">
                        <option value="">Seleccione el tipo…</option>
                        <?php $currentType = (int) $val('document_type_id', 0); ?>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $currentType ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="program_id">Programa de estudios / Especialidad</label>
                    <select id="program_id" name="program_id" class="form-control">
                        <option value="">Sin especificar</option>
                        <?php $currentProgram = (int) $val('program_id', 0); ?>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === $currentProgram ? ' selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- PASO 2: TÍTULO Y RESUMEN -->
        <div class="wizard-pane card" id="step-pane-2" style="display:none;">
            <h2 style="font-size:1.15rem;margin-bottom:14px;">Paso 2: Título y descripción general</h2>
            <div class="field mb-3">
                <label for="title">Título completo de la obra o documento *</label>
                <input type="text" id="title" name="title" required minlength="5" maxlength="500" class="form-control"
                       value="<?= e($val('title')) ?>" placeholder="Título académico oficial">
                <span class="form-hint">Escriba el título tal como figura en la portada del trabajo.</span>
            </div>
            <div class="field mb-3">
                <label for="abstract">Resumen / Abstract</label>
                <textarea id="abstract" name="abstract" rows="6" maxlength="20000" class="form-control"
                          placeholder="Síntesis del trabajo, objetivos, metodología y conclusiones principales..."><?= e($val('abstract')) ?></textarea>
            </div>
        </div>

        <!-- PASO 3: AUTORES Y COAUTORES -->
        <div class="wizard-pane card" id="step-pane-3" style="display:none;">
            <div class="flex-between mb-2">
                <h2 style="font-size:1.15rem;">Paso 3: Autores y colaboradores</h2>
                <button class="btn btn-sm btn-secondary" type="button" data-add-row="authors-table">+ Agregar autor</button>
            </div>
            <p class="small text-muted mb-3">Indique la autoría. Puede reordenar los autores utilizando los botones de flecha.</p>

            <div class="table-responsive">
                <table class="table" id="authors-table">
                    <thead>
                        <tr>
                            <th>Nombre completo *</th>
                            <th>Rol</th>
                            <th>Afiliación</th>
                            <th>ORCID</th>
                            <th>Principal</th>
                            <th>Orden</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($authors as $i => $a): ?>
                        <tr>
                            <td><input type="text" name="authors[<?= $i ?>][full_name]" maxlength="190" required class="form-control" value="<?= e($a['full_name']) ?>" placeholder="Apellidos, Nombres"></td>
                            <td>
                                <select name="authors[<?= $i ?>][author_role]" class="form-control">
                                    <?php $role = (string) ($a['author_role'] ?? 'author'); ?>
                                    <option value="author"<?= $role === 'author' ? ' selected' : '' ?>>Autoría</option>
                                    <option value="advisor"<?= $role === 'advisor' ? ' selected' : '' ?>>Asesoría</option>
                                    <option value="contributor"<?= $role === 'contributor' ? ' selected' : '' ?>>Contribución</option>
                                    <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Edición</option>
                                </select>
                            </td>
                            <td><input type="text" name="authors[<?= $i ?>][affiliation]" maxlength="190" class="form-control" value="<?= e($a['affiliation'] ?? '') ?>" placeholder="Instituto"></td>
                            <td><input type="text" name="authors[<?= $i ?>][orcid]" maxlength="19" class="form-control" placeholder="0000-0000-0000-0000" value="<?= e($a['orcid'] ?? '') ?>"></td>
                            <td style="text-align:center">
                                <input type="checkbox" name="authors[<?= $i ?>][is_corresponding]" value="1"<?= !empty($a['is_corresponding']) ? ' checked' : '' ?>>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-ghost" data-move-up title="Subir">▲</button>
                                <button type="button" class="btn btn-sm btn-ghost" data-move-down title="Bajar">▼</button>
                            </td>
                            <td><button class="btn btn-sm btn-ghost btn-danger" type="button" data-remove-row>Quitar</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <template id="authors-row-tpl">
                <tr>
                    <td><input type="text" name="authors[__N__][full_name]" maxlength="190" required class="form-control" placeholder="Apellidos, Nombres"></td>
                    <td>
                        <select name="authors[__N__][author_role]" class="form-control">
                            <option value="author">Autoría</option>
                            <option value="advisor">Asesoría</option>
                            <option value="contributor">Contribución</option>
                            <option value="editor">Edición</option>
                        </select>
                    </td>
                    <td><input type="text" name="authors[__N__][affiliation]" maxlength="190" class="form-control" placeholder="Instituto"></td>
                    <td><input type="text" name="authors[__N__][orcid]" maxlength="19" class="form-control" placeholder="0000-0000-0000-0000"></td>
                    <td style="text-align:center"><input type="checkbox" name="authors[__N__][is_corresponding]" value="1"></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-ghost" data-move-up title="Subir">▲</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-move-down title="Bajar">▼</button>
                    </td>
                    <td><button class="btn btn-sm btn-ghost btn-danger" type="button" data-remove-row>Quitar</button></td>
                </tr>
            </template>
        </div>

        <!-- PASO 4: METADATOS ADICIONALES -->
        <div class="wizard-pane card" id="step-pane-4" style="display:none;">
            <h2 style="font-size:1.15rem;margin-bottom:14px;">Paso 4: Metadatos académicos adicionales</h2>
            <div class="form-grid mb-3">
                <div class="field">
                    <label for="language_code">Idioma principal *</label>
                    <select id="language_code" name="language_code" required class="form-control">
                        <?php $lang = (string) $val('language_code', 'es-PE'); ?>
                        <option value="es-PE"<?= $lang === 'es-PE' ? ' selected' : '' ?>>Español (es-PE)</option>
                        <option value="en"<?= $lang === 'en' ? ' selected' : '' ?>>Inglés (en)</option>
                        <option value="qu"<?= $lang === 'qu' ? ' selected' : '' ?>>Quechua (qu)</option>
                        <option value="ay"<?= $lang === 'ay' ? ' selected' : '' ?>>Aimara (ay)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="issued_at">Fecha de emisión / sustentación</label>
                    <input type="date" id="issued_at" name="issued_at" class="form-control" value="<?= e($val('issued_at')) ?>">
                </div>
            </div>
            <div class="field mb-3">
                <label for="keywords">Palabras clave / Descriptores temáticos</label>
                <input type="text" id="keywords" name="keywords" maxlength="1000" class="form-control"
                       placeholder="innovación tecnológica, gestión ambiental, desarrollo de software" value="<?= e($keywords) ?>">
                <span class="form-hint">Separe cada palabra clave con una coma.</span>
            </div>
        </div>

        <!-- PASO 5: ACCESO Y DERECHOS -->
        <div class="wizard-pane card" id="step-pane-5" style="display:none;">
            <h2 style="font-size:1.15rem;margin-bottom:14px;">Paso 5: Acceso, licencias y derechos</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="access_level">Modalidad de acceso *</label>
                    <select id="access_level" name="access_level" required class="form-control">
                        <?php $access = (string) $val('access_level', 'open'); ?>
                        <option value="open"<?= $access === 'open' ? ' selected' : '' ?>>Acceso abierto (Acceso público a metadatos y archivo)</option>
                        <option value="metadata_only"<?= $access === 'metadata_only' ? ' selected' : '' ?>>Solo metadatos (Sin archivo público)</option>
                        <option value="embargoed"<?= $access === 'embargoed' ? ' selected' : '' ?>>Bajo embargo temporal (Desbloqueo en fecha programada)</option>
                        <option value="restricted"<?= $access === 'restricted' ? ' selected' : '' ?>>Acceso restringido (Comunidad institucional)</option>
                        <option value="private"<?= $access === 'private' ? ' selected' : '' ?>>Privado / Confidencial</option>
                    </select>
                </div>
                <div class="field">
                    <label for="embargo_until">Fecha fin de embargo (si aplica)</label>
                    <input type="date" id="embargo_until" name="embargo_until" class="form-control" value="<?= e($val('embargo_until')) ?>">
                    <span class="form-hint">Obligatorio si la modalidad seleccionada es «Bajo embargo».</span>
                </div>
            </div>
        </div>

        <!-- PASO 6: RESUMEN Y ENVÍO -->
        <div class="wizard-pane card" id="step-pane-6" style="display:none;">
            <h2 style="font-size:1.15rem;margin-bottom:14px;">Paso 6: Verificación y envío a revisión</h2>
            <p class="small text-muted mb-4">Revise los datos capturados antes de guardar o enviar el documento al flujo editorial.</p>

            <div id="wizard-summary-content" class="mb-4">
                <!-- Se llena dinámicamente con JavaScript -->
            </div>

            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Una vez creado el borrador, accederá a la ficha del recurso para adjuntar el archivo PDF principal y los anexos correspondientes.</span>
            </div>
        </div>

        <!-- CONTROLES INFERIORES DE NAVEGACIÓN -->
        <div class="flex-between mt-4" style="border-top:1px solid var(--border);padding-top:20px;">
            <button type="button" class="btn btn-outline" id="wizard-prev-btn" style="display:none;">
                ← Anterior
            </button>
            <div style="margin-left:auto;display:flex;gap:10px;">
                <button type="button" class="btn btn-secondary" id="wizard-next-btn">
                    Siguiente paso →
                </button>
                <button type="submit" class="btn btn-primary" id="wizard-submit-btn" style="display:none;">
                    <?= $isEdit ? 'Guardar cambios' : 'Crear borrador y continuar' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script type="module" src="<?= e(asset('js/modules/deposit-wizard.js')) ?>"></script>
