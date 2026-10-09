<?php

declare(strict_types=1);

use App\Core\View;

/**
 * Ficha pública de un recurso.
 *
 * @var array<string,mixed> $item
 * @var array<int,array<string,mixed>> $authors
 * @var array<int,array<string,mixed>> $keywords
 * @var array<int,array<string,mixed>> $files
 * @var array<string,mixed>|null $license
 * @var array<int,array<string,mixed>> $identifiers
 * @var array<int,array<string,mixed>> $versions
 * @var array<int,array<string,mixed>> $related
 * @var array<int,array<string,mixed>> $reviewerNotes
 * @var array<string,array<int,array<string,mixed>>> $metadata
 * @var array{format:string,content:string,warnings:array<int,string>} $citation
 * @var array<string,mixed>|null $filePreview
 */

$accessLabels = [
    'open' => 'Acceso abierto',
    'metadata_only' => 'Solo metadatos',
    'embargoed' => 'Bajo embargo',
    'restricted' => 'Acceso restringido',
    'private' => 'Privado',
];
$year = !empty($item['issued_at']) ? substr((string) $item['issued_at'], 0, 4) : '';
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <a href="<?= e(url('/comunidades')) ?>">Comunidades</a> ›
            <a href="<?= e(url('/comunidades/' . $item['community_slug'])) ?>"><?= e($item['community_name']) ?></a> ›
            <a href="<?= e(url('/colecciones/' . $item['collection_slug'])) ?>"><?= e($item['collection_name']) ?></a> ›
            <span><?= e($item['public_id']) ?></span>
        </nav>

        <div class="flex-between mb-2">
            <div>
                <?php if (!empty($item['document_type_name'])): ?>
                    <span class="item-type" style="color:var(--brand-secondary);font-weight:700;text-transform:uppercase;font-size:.8rem;letter-spacing:.05em"><?= e($item['document_type_name']) ?></span>
                <?php endif; ?>
                <h1><?= e($item['title']) ?></h1>
            </div>
            <div class="flex">
                <span class="badge badge-<?= e($item['access_level']) ?>"><?= e($accessLabels[$item['access_level']] ?? $item['access_level']) ?></span>
                <?php if ($item['status'] !== 'PUBLISHED'): ?>
                    <span class="badge badge-<?= e(strtolower($item['status'])) ?>"><?= e($item['status']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="record-layout">
            <div>
                <?php if ($authors !== []): ?>
                    <h2 class="h3" style="font-size:1.05rem">Autores</h2>
                    <ul style="list-style:none;padding:0;margin:0 0 18px">
                        <?php foreach ($authors as $author): ?>
                            <li class="mb-1">
                                <a href="<?= e(url('/autores/' . rawurlencode($author['full_name']))) ?>"><?= e($author['full_name']) ?></a>
                                <?php if (!empty($author['affiliation'])): ?><span class="muted small"> — <?= e($author['affiliation']) ?></span><?php endif; ?>
                                <?php if (!empty($author['orcid'])): ?>
                                    <span class="small"> · ORCID: <a href="https://orcid.org/<?= e($author['orcid']) ?>" rel="noopener nofollow" target="_blank"><?= e($author['orcid']) ?></a></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($item['abstract'])): ?>
                    <h2 style="font-size:1.15rem">Resumen</h2>
                    <p style="white-space:pre-wrap"><?= e($item['abstract']) ?></p>
                <?php endif; ?>

                <?php if ($keywords !== []): ?>
                    <h2 style="font-size:1.15rem">Palabras clave</h2>
                    <p class="flex">
                        <?php foreach ($keywords as $kw): ?>
                            <a class="badge badge-status" href="<?= e(url('/buscar?palabra_clave=' . urlencode($kw['slug']))) ?>"><?= e($kw['term']) ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <?php if ($metadata !== []): ?>
                    <h2 style="font-size:1.15rem">Metadatos detallados</h2>
                    <div class="record-meta mb-3">
                        <dl>
                            <?php foreach ($metadata as $fieldKey => $values): ?>
                                <?php if (in_array($fieldKey, ['abstract'], true)) { continue; } ?>
                                <div class="meta-row-item">
                                    <dt><?= e($values[0]['label']) ?></dt>
                                    <dd>
                                        <?php
                                        $rendered = [];
                                        foreach ($values as $v) {
                                            $val = trim((string) ($v['value_text'] ?? ''));
                                            if ($val === '') { continue; }
                                            if (!empty($v['value_uri'])) {
                                                $rendered[] = '<a href="' . e($v['value_uri']) . '" rel="noopener">' . e($val) . '</a>';
                                            } else {
                                                $rendered[] = e($val);
                                            }
                                        }
                                        echo implode(' · ', $rendered) ?: '<span class="muted">Sin información</span>';
                                        ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>

                <?php if ($reviewerNotes !== []): ?>
                    <h2 style="font-size:1.15rem">Notas del revisor (visibles para el autor)</h2>
                    <?php foreach ($reviewerNotes as $note): ?>
                        <div class="alert alert-info">
                            <p class="small muted"><?= e($note['full_name'] ?? 'Revisor') ?> — <?= format_date_es($note['created_at']) ?></p>
                            <p><?= e($note['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <aside>
                <div class="card mb-2">
                    <h2 style="font-size:1.02rem">Archivos</h2>
                    <?php if ($files === []): ?>
                        <p class="muted small">
                            <?php if ($item['access_level'] === 'metadata_only'): ?>
                                Este recurso publica solo sus metadatos; el archivo no está disponible.
                            <?php elseif ($item['access_level'] === 'restricted'): ?>
                                El archivo tiene acceso restringido. Contacte con el responsable del repositorio.
                            <?php elseif ($item['access_level'] === 'private'): ?>
                                Archivo no disponible.
                            <?php else: ?>
                                Este registro aún no tiene archivos publicados.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <ul style="list-style:none;padding:0;margin:0">
                            <?php foreach ($files as $file): ?>
                                <li class="mb-2">
                                    <div class="flex-between">
                                        <span>
                                            <strong><?= e($file['original_filename']) ?></strong><br>
                                            <span class="small muted"><?= e($file['mime_type']) ?> · <?= number_format(((int) $file['file_size']) / 1048576, 2, ',', '.') ?> MB · <?= (int) $file['download_count'] ?> descarga(s)</span>
                                        </span>
                                    </div>
                                    <div class="flex mt-1">
                                        <?php if (strtolower((string) $file['extension']) === 'pdf'): ?>
                                            <a class="btn btn-sm btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/archivos/' . $file['id'] . '/ver')) ?>">Ver</a>
                                        <?php endif; ?>
                                        <a class="btn btn-sm" href="<?= e(url('/recursos/' . $item['public_id'] . '/archivos/' . $file['id'] . '/descargar')) ?>">Descargar</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <hr class="divider">
                    <h2 style="font-size:1.02rem">Citar este recurso</h2>
                    <div class="flex mb-1">
                        <a class="btn btn-sm btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=apa')) ?>">APA</a>
                        <a class="btn btn-sm btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=bibtex')) ?>">BibTeX</a>
                        <a class="btn btn-sm btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=ris')) ?>">RIS</a>
                    </div>
                    <label class="visually-hidden" for="citation-text">Cita sugerida</label>
                    <textarea id="citation-text" readonly rows="4" class="small"><?= e($citation['content']) ?></textarea>
                    <button class="btn btn-sm mt-1" type="button" data-copy="#citation-text">Copiar cita</button>
                    <?php if ($citation['warnings'] !== []): ?>
                        <p class="small" style="color:var(--warning)">⚠ <?= e(implode(' ', $citation['warnings'])) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($license !== null): ?>
                    <div class="card mb-2">
                        <h2 style="font-size:1.02rem">Licencia</h2>
                        <p class="small">
                            <?php if (!empty($license['url'])): ?>
                                <a href="<?= e($license['url']) ?>" target="_blank" rel="noopener"><?= e($license['name']) ?></a>
                            <?php else: ?>
                                <?= e($license['name']) ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($license['statement'])): ?><p class="small muted"><?= e($license['statement']) ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-2">
                    <h2 style="font-size:1.02rem">Identificación</h2>
                    <p class="small"><strong>Identificador estable:</strong><br>
                        <a href="<?= e(url('/recursos/' . $item['public_id'])) ?>"><?= e(url('/recursos/' . $item['public_id'])) ?></a>
                    </p>
                    <?php foreach ($identifiers as $id): ?>
                        <p class="small"><strong><?= e(strtoupper(str_replace('_', ' ', $id['identifier_type']))) ?>:</strong>
                            <?php if ($id['identifier_type'] === 'local_uri'): ?>
                                <?= e($id['identifier_value']) ?>
                            <?php else: ?>
                                <?= e($id['identifier_value']) ?><?= $id['is_verified'] ? ' ✓ verificado' : '' ?>
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                    <?php if ($identifiers === []): ?>
                        <p class="small muted">URI propia: <?= e(url('/recursos/' . $item['public_id'])) ?> (identificador local; no se ha registrado DOI/Handle con proveedor externo).</p>
                    <?php endif; ?>
                    <p class="small"><strong>Publicado:</strong> <?= format_date_es($item['published_at'], 'd \d\e F \d\e Y') ?></p>
                    <p class="small"><strong>Versión:</strong> <?= (int) ($item['version_number'] ?? 1) ?></p>
                </div>

                <?php if ($versions !== []): ?>
                    <div class="card mb-2">
                        <h2 style="font-size:1.02rem">Versiones</h2>
                        <ul class="small" style="padding-left:18px">
                            <?php foreach ($versions as $v): ?>
                                <li>Versión <?= (int) $v['version_number'] ?> — <?= format_date_es($v['created_at'], 'd/m/Y') ?>
                                    <?php if (!empty($v['change_reason'])): ?><br><span class="muted"><?= e($v['change_reason']) ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($related !== []): ?>
                    <div class="card">
                        <h2 style="font-size:1.02rem">Relacionados</h2>
                        <ul class="small" style="padding-left:18px">
                            <?php foreach ($related as $rel): ?>
                                <li><a href="<?= e(url('/recursos/' . $rel['public_id'])) ?>"><?= e($rel['title']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>
        </div>

        <?php if ($filePreview !== null && $item['access_level'] === 'open'): ?>
            <div class="mt-4">
                <h2>Visualización del documento</h2>
                <object class="pdf-frame" data="<?= e(url('/recursos/' . $item['public_id'] . '/archivos/' . $filePreview['id'] . '/ver')) ?>" type="application/pdf">
                    <p class="p-2">
                        Su navegador no puede mostrar este PDF.
                        <a href="<?= e(url('/recursos/' . $item['public_id'] . '/archivos/' . $filePreview['id'] . '/descargar')) ?>">Descargar el archivo</a>.
                    </p>
                </object>
            </div>
        <?php endif; ?>
    </div>
</section>
