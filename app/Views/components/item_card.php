<?php

declare(strict_types=1);

/**
 * Tarjeta de recurso.
 *
 * @var array<string,mixed> $item
 */
$accessLabels = [
    'open'          => 'Acceso abierto',
    'metadata_only' => 'Solo metadatos',
    'embargoed'     => 'Bajo embargo',
    'restricted'    => 'Acceso restringido',
    'private'       => 'Privado',
];

$accessIcons = [
    'open'          => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'metadata_only' => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    'embargoed'     => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'restricted'    => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
    'private'       => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
];

// Icono de tipo de documento
$typeIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
?>
<article class="item-card">
    <?php if (!empty($item['document_type_name'])): ?>
        <span class="item-type"><?= $typeIconSvg ?><?= e($item['document_type_name']) ?></span>
    <?php endif; ?>
    <h3><a href="<?= e(url('/recursos/' . $item['public_id'])) ?>"><?= e($item['title']) ?></a></h3>
    <?php if (!empty($item['authors_line'])): ?>
        <p class="authors">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= e($item['authors_line']) ?>
        </p>
    <?php endif; ?>
    <div class="meta-row">
        <?php if (!empty($item['year'])): ?>
            <span class="badge badge-status" style="display:flex;align-items:center;gap:4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?= (int) $item['year'] ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($item['access_level'])): ?>
            <span class="badge badge-<?= e($item['access_level']) ?>">
                <?= $accessIcons[$item['access_level']] ?? '' ?>
                <?= e($accessLabels[$item['access_level']] ?? $item['access_level']) ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($item['collection_name'])): ?>
            <span class="badge badge-status" style="display:flex;align-items:center;gap:4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <?= e($item['collection_name']) ?>
            </span>
        <?php endif; ?>
        <?php if (isset($item['views'])): ?>
            <span class="badge badge-status" title="Consultas a la ficha" style="display:flex;align-items:center;gap:4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?= (int) $item['views'] ?>
            </span>
        <?php endif; ?>
    </div>
</article>
