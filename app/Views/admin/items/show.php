<?php

declare(strict_types=1);

use App\Core\Authorization;
use App\Core\Csrf;
use App\Services\WorkflowService;

/**
 * Ficha del recurso en el panel: metadatos, archivos, flujo y versiones.
 *
 * @var array<string,mixed> $item
 * @var array<int,array<string,mixed>> $authors
 * @var array<int,array<string,mixed>> $keywords
 * @var array<int,array<string,mixed>> $files
 * @var array<int,array<string,mixed>> $versions
 * @var array<int,array<string,mixed>> $history
 * @var array<int,array<string,mixed>> $comments
 * @var array<int,string> $allowedTransitions
 * @var bool $canEdit
 * @var bool $canUpload
 * @var array{allowed_extensions:string,max_file_mb:int,max_files:int} $policy
 */

$statusLabels = [
    'CHANGES_REQUESTED' => 'CAMBIOS SOLICITADOS',
];
$needsReason = ['CHANGES_REQUESTED', 'REJECTED'];
?>
<div class="flex-between">
    <div>
        <p class="muted small"><a href="<?= e(url('/admin/recursos')) ?>">← Volver a la lista</a></p>
        <h1><?= e($item['title'] ?? '(sin título)') ?></h1>
        <p>
            <strong><?= e($item['public_id']) ?></strong>
            <span class="badge badge-<?= e(strtolower((string) $item['status'])) ?>"><?= e($statusLabels[$item['status']] ?? $item['status']) ?></span>
            <span class="badge badge-<?= e($item['access_level']) ?>"><?= e($item['access_level']) ?></span>
            <?php if ($item['status'] === 'PUBLISHED'): ?>
                <a class="btn btn-sm btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'])) ?>" target="_blank" rel="noopener">Ver en el portal</a>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex">
        <?php if ($canEdit): ?>
            <a class="btn" href="<?= e(url('/admin/recursos/' . $item['id'] . '/editar')) ?>">Editar metadatos</a>
        <?php endif; ?>
        <?php if (in_array($item['status'], ['DRAFT', 'CHANGES_REQUESTED'], true) && Authorization::can('items.submit')): ?>
            <form method="post" action="<?= e(url('/admin/recursos/' . $item['id'] . '/enviar')) ?>"
                  data-confirm="¿Enviar este recurso a revisión? El envío se congelará hasta recibir observaciones.">
                <?= Csrf::field() ?>
                <button class="btn btn-primary" type="submit">Enviar a revisión</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($item['status'] === 'CHANGES_REQUESTED'): ?>
    <div class="alert alert-warning mt-2">
        Se solicitaron cambios. Edite los metadatos según las observaciones y vuelva a enviar a revisión.
    </div>
<?php endif; ?>

<div class="record-layout mt-3">
    <div>
        <div class="card mb-2">
            <h2 style="font-size:1.05rem">Metadatos</h2>
            <?php if (!empty($item['abstract'])): ?>
                <h3 style="font-size:.95rem">Resumen</h3>
                <p style="white-space:pre-wrap"><?= e($item['abstract']) ?></p>
            <?php endif; ?>
            <div class="record-meta">
                <dl>
                    <div class="meta-row-item"><dt>Colección</dt><dd><?= e($item['community_name'] ?? '—') ?> / <?= e($item['collection_name'] ?? '—') ?></dd></div>
                    <div class="meta-row-item"><dt>Tipo documental</dt><dd><?= e($item['document_type_name'] ?? 'Sin especificar') ?></dd></div>
                    <div class="meta-row-item"><dt>Programa</dt><dd><?= e($item['program_name'] ?? 'Sin especificar') ?></dd></div>
                    <div class="meta-row-item"><dt>Idioma</dt><dd><?= e($item['language_code'] ?? '') ?></dd></div>
                    <div class="meta-row-item"><dt>Fecha de emisión</dt><dd><?= e($item['issued_at'] ?? '—') ?></dd></div>
                    <div class="meta-row-item"><dt>Creado por</dt><dd><?= e($item['created_by_name'] ?? '—') ?> · <?= format_date_es($item['created_at']) ?></dd></div>
                    <?php if (!empty($item['embargo_until'])): ?>
                        <div class="meta-row-item"><dt>Embargo hasta</dt><dd><?= format_date_es($item['embargo_until'], 'd/m/Y') ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <h3 style="font-size:.95rem">Autores</h3>
            <ul style="padding-left:18px">
                <?php foreach ($authors as $a): ?>
                    <li><?= e($a['full_name']) ?> <span class="muted small">(<?= e($a['author_role']) ?>)<?= !empty($a['is_corresponding']) ? ' · corresponsal' : '' ?></span></li>
                <?php endforeach; ?>
                <?php if ($authors === []): ?><li class="muted">Sin autores registrados.</li><?php endif; ?>
            </ul>

            <?php if ($keywords !== []): ?>
                <h3 style="font-size:.95rem">Palabras clave</h3>
                <p><?php foreach ($keywords as $kw): ?><span class="badge badge-status"><?= e($kw['term']) ?></span> <?php endforeach; ?></p>
            <?php endif; ?>
        </div>

        <div class="card mb-2">
            <div class="flex-between">
                <h2 style="font-size:1.05rem">Archivos (<?= count($files) ?>/<?= (int) $policy['max_files'] ?>)</h2>
            </div>
            <?php if ($files !== []): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Archivo</th><th>Tipo</th><th>Tamaño</th><th>SHA-256</th><th>Descargas</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td>
                                    <?php if ((int) $f['is_primary'] === 1): ?><span class="badge badge-published">principal</span><?php endif; ?>
                                    <?= e($f['original_filename']) ?>
                                </td>
                                <td class="small"><?= e($f['mime_type']) ?></td>
                                <td class="small"><?= number_format(((int) $f['file_size']) / 1048576, 2, ',', '.') ?> MB</td>
                                <td class="small"><code><?= e(substr((string) $f['sha256'], 0, 12)) ?>…</code></td>
                                <td><?= (int) $f['download_count'] ?></td>
                                <td>
                                    <?php if ($canUpload): ?>
                                        <form method="post" action="<?= e(url('/admin/recursos/' . $item['id'] . '/archivos/' . $f['id'] . '/eliminar')) ?>"
                                              data-confirm="¿Eliminar el archivo «<?= e($f['original_filename']) ?>»?">
                                            <?= Csrf::field() ?>
                                            <button class="btn btn-sm btn-secondary" type="submit">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="muted">Sin archivos adjuntos.
                    <?php if ($item['access_level'] !== 'metadata_only'): ?>
                        Para publicar, suba al menos un archivo o cambie el nivel a «solo metadatos».
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($canUpload): ?>
                <hr class="divider">
                <form method="post" action="<?= e(url('/admin/recursos/' . $item['id'] . '/archivos')) ?>"
                      enctype="multipart/form-data" data-upload-progress="upload-bar">
                    <?= Csrf::field() ?>
                    <div class="field mb-1">
                        <label for="archivo">Adjuntar archivo</label>
                        <input type="file" id="archivo" name="archivo" required
                               accept=".<?= e(str_replace(',', ',.', $policy['allowed_extensions'])) ?>">
                        <p class="small muted">
                            Extensiones: <?= e($policy['allowed_extensions']) ?> · máximo <?= (int) $policy['max_file_mb'] ?> MB.
                            Prohibido subir archivos ejecutables.
                        </p>
                    </div>
                    <div class="progress mt-1" id="upload-bar" aria-hidden="true"><span class="bar-fill" style="width:0%"></span></div>
                    <button class="btn mt-2" type="submit">Subir archivo</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card mb-2">
            <div class="flex-between">
                <h2 style="font-size:1.05rem">Observaciones de revisión</h2>
            </div>
            <?php if ($comments !== []): ?>
                <ul style="padding-left:18px">
                    <?php foreach ($comments as $c): ?>
                        <li class="mb-1">
                            <span class="small muted"><?= format_date_es($c['created_at']) ?> · <?= e($c['full_name'] ?? 'Sistema') ?>
                                <?= $c['visibility'] === 'internal' ? '· <strong>interna</strong>' : '' ?>:</span><br>
                            <?= nl2br(e($c['comment'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">Sin observaciones registradas.</p>
            <?php endif; ?>
            <?php if (Authorization::can('items.review')): ?>
                <form method="post" action="<?= e(url('/admin/revision/' . $item['id'] . '/observaciones')) ?>" class="mt-2">
                    <?= Csrf::field() ?>
                    <div class="field mb-1">
                        <label for="obs-comment">Nueva observación</label>
                        <textarea id="obs-comment" name="comment" rows="2" maxlength="4000" required
                                  placeholder="Indique el campo a corregir y la acción esperada…"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <label class="small" style="align-self:center"><input type="radio" name="visibility" value="author" checked> visible para el autor</label>
                        <label class="small" style="align-self:center"><input type="radio" name="visibility" value="internal"> interna</label>
                        <button class="btn btn-sm" type="submit">Registrar observación</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="font-size:1.05rem">Historial de estados</h2>
            <?php if ($history === []): ?>
                <p class="muted">Sin transiciones registradas.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Fecha</th><th>De</th><th>A</th><th>Actor</th><th>Motivo</th></tr></thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="small"><?= format_date_es($h['created_at']) ?></td>
                                <td class="small"><?= e($h['from_status'] ?? '—') ?></td>
                                <td class="small"><strong><?= e($h['to_status']) ?></strong></td>
                                <td class="small"><?= e($h['full_name'] ?? 'Sistema') ?></td>
                                <td class="small"><?= e($h['reason'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <?php if ($allowedTransitions !== []): ?>
            <div class="card mb-2">
                <h2 style="font-size:1.02rem">Acciones del flujo</h2>
                <p class="small muted">Estado actual: <strong><?= e($item['status']) ?></strong>. Se muestran solo las transiciones permitidas por la matriz editorial.</p>
                <?php foreach ($allowedTransitions as $to): ?>
                    <form method="post" action="<?= e(url('/admin/recursos/' . $item['id'] . '/transicion')) ?>" class="mb-2"
                          data-confirm="<?= in_array($to, ['PUBLISHED', 'WITHDRAWN', 'ARCHIVED', 'REJECTED'], true) ? '¿Confirmar la transición a ' . $to . '?' : '' ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="to" value="<?= e($to) ?>">
                        <?php if (in_array($to, $needsReason, true)): ?>
                            <label class="small" for="reason-<?= e($to) ?>">Motivo para <?= e($to) ?> *</label>
                            <textarea id="reason-<?= e($to) ?>" name="reason" rows="2" required maxlength="500" class="small"></textarea>
                        <?php endif; ?>
                        <button class="btn btn-sm <?= in_array($to, ['PUBLISHED', 'APPROVED'], true) ? 'btn-primary' : 'btn-secondary' ?>" type="submit">
                            <?= e($to) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($item['status'] === 'PUBLISHED' && Authorization::can('items.submit')): ?>
            <div class="card mb-2">
                <h2 style="font-size:1.02rem">Nueva versión (6.11)</h2>
                <form method="post" action="<?= e(url('/admin/recursos/' . $item['id'] . '/nueva-version')) ?>"
                      data-confirm="Se creará una nueva versión y volverá a evaluación. ¿Continuar?">
                    <?= Csrf::field() ?>
                    <label class="small" for="nv-reason">Motivo del cambio *</label>
                    <textarea id="nv-reason" name="reason" rows="2" required maxlength="255" class="small" placeholder="Corrección de metadatos, nueva edición…"></textarea>
                    <button class="btn btn-sm mt-1" type="submit">Crear nueva versión</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card mb-2">
            <h2 style="font-size:1.02rem">Versiones</h2>
            <?php if ($versions === []): ?>
                <p class="muted small">Sin versiones.</p>
            <?php else: ?>
                <ul class="small" style="padding-left:18px">
                    <?php foreach ($versions as $v): ?>
                        <li>
                            v<?= (int) $v['version_number'] ?>
                            <?= (int) $v['version_number'] === (int) ($item['version_number'] ?? 0) ? '<span class="badge badge-published">vigente</span>' : '' ?>
                            — <?= format_date_es($v['created_at'], 'd/m/Y') ?>
                            <?php if (!empty($v['change_reason'])): ?><br><span class="muted"><?= e($v['change_reason']) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="font-size:1.02rem">Identificación</h2>
            <p class="small"><strong>Identificador:</strong> <?= e($item['public_id']) ?></p>
            <p class="small"><strong>URI estable:</strong> <?= e(url('/recursos/' . $item['public_id'])) ?></p>
            <p class="small"><strong>UUID:</strong> <code><?= e($item['uuid']) ?></code></p>
            <p class="small"><strong>Última modificación:</strong> <?= format_date_es($item['updated_at']) ?></p>
        </div>
    </aside>
</div>
