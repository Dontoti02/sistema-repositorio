<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\TenantContext;
use App\Services\BrandAssetService;
use App\Services\ThemeService;

/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $recent */
/** @var array<int,array<string,mixed>> $mostViewed */
/** @var array<int,array<string,mixed>> $communities */

$inst = TenantContext::institution();
$instId = (int) ($inst['id'] ?? 1);
$theme = ThemeService::getPublishedTheme($instId);
$layout = $theme['layout'];
$texts = $theme['texts'];

$heroTitle = !empty($texts['hero_title']) ? $texts['hero_title'] : ($inst['name'] ?? 'Repositorio institucional');
$heroSubtitle = !empty($texts['hero_subtitle']) ? $texts['hero_subtitle'] : ($inst['description'] ?: 'Repositorio digital de acceso abierto de la producción académica, científica, tecnológica e institucional.');
$heroUrl = BrandAssetService::getAssetUrl($instId, BrandAssetService::KIND_HERO)
    ?? (!empty($inst['hero_image_path']) ? url('storage/media/' . $inst['hero_image_path']) : null);

$heroClass = 'hero';
$heroStyle = '';
if (($layout['hero_mode'] ?? '') === 'solid') {
    $heroClass .= ' hero-solid';
} elseif (($layout['hero_mode'] ?? '') === 'image' && $heroUrl) {
    $heroClass .= ' hero-image';
    $heroStyle = "background-image:url('" . e($heroUrl) . "');";
} else {
    $heroClass .= ' hero-gradient';
}
?>
<section class="<?= $heroClass ?>" style="<?= $heroStyle ?>">
    <?php if (($layout['hero_mode'] ?? '') === 'image'): ?>
        <div class="hero-overlay-dark"></div>
    <?php endif; ?>
    <div class="container" style="position:relative;z-index:2;">
        <h1><?= e($heroTitle) ?></h1>
        <p class="lead" style="font-size:clamp(1rem,2vw,1.2rem);color:rgba(255,255,255,.9);max-width:800px;margin:0 auto 28px auto;">
            <?= e($heroSubtitle) ?>
        </p>
        <form class="searchbar" action="<?= e(url('/buscar')) ?>" method="get" role="search" style="max-width:720px;margin:0 auto;">
            <label class="visually-hidden" for="q-home">Buscar en el repositorio</label>
            <input type="search" id="q-home" name="q" placeholder="Buscar por título, autor, palabra clave…" maxlength="200" autocomplete="off">
            <button class="btn btn-accent btn-lg" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Buscar
            </button>
        </form>
        <p class="mt-2" style="text-align:center;">
            <a href="<?= e(url('/busqueda-avanzada')) ?>" style="color:rgba(255,255,255,.85);display:inline-flex;align-items:center;gap:6px;font-size:.92rem;font-weight:500;transition:color .2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Búsqueda avanzada
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </p>
    </div>
</section>

<?php if (!empty($layout['show_stats'])): ?>
<!-- Estadísticas -->
<section class="section" style="padding-top:32px;padding-bottom:32px;">
    <div class="container">
        <div class="grid grid-4">
            <div class="stat">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <span class="stat-value"><?= number_format($stats['items'], 0, ',', '.') ?></span>
                <span class="stat-label">Recursos publicados</span>
            </div>
            <div class="stat">
                <div class="stat-icon" style="background:#e8f5ee;color:var(--brand-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <span class="stat-value" style="color:var(--brand-secondary);"><?= number_format($stats['communities'], 0, ',', '.') ?></span>
                <span class="stat-label">Comunidades</span>
            </div>
            <div class="stat">
                <div class="stat-icon" style="background:#fef9ec;color:var(--brand-accent);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <span class="stat-value" style="color:var(--brand-accent);"><?= number_format($stats['collections'], 0, ',', '.') ?></span>
                <span class="stat-label">Colecciones</span>
            </div>
            <div class="stat">
                <div class="stat-icon" style="background:#f0eafb;color:#7c4dba;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </div>
                <span class="stat-value" style="color:#7c4dba;"><?= number_format($stats['downloads'], 0, ',', '.') ?></span>
                <span class="stat-label">Descargas registradas</span>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($layout['show_collections']) && $communities !== []): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title">
            <h2 style="display:flex;align-items:center;gap:10px;">
                <span style="width:36px;height:36px;background:#e6eef8;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:var(--brand-primary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </span>
                Comunidades y colecciones
            </h2>
            <a href="<?= e(url('/comunidades')) ?>">
                Ver todas
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
        <div class="grid grid-3">
            <?php foreach ($communities as $community): ?>
                <div class="card card-hover" style="gap:0;">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--brand-primary),#1a6ab0);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#fff;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <h3 style="margin-bottom:8px;"><a href="<?= e(url('/comunidades/' . $community['slug'])) ?>"><?= e($community['name']) ?></a></h3>
                    <?php if (!empty($community['description'])): ?>
                        <p class="muted small" style="margin-bottom:14px;line-height:1.55;"><?= e(mb_substr((string) $community['description'], 0, 150)) ?></p>
                    <?php endif; ?>
                    <div style="margin-top:auto;display:flex;gap:6px;flex-wrap:wrap;">
                        <span class="badge badge-status" style="display:flex;align-items:center;gap:4px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                            <?= (int) $community['collections_count'] ?> colección(es)
                        </span>
                        <span class="badge badge-published" style="display:flex;align-items:center;gap:4px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?= (int) $community['items_count'] ?> recurso(s)
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($layout['show_recent'])): ?>
<!-- Publicaciones recientes -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title">
            <h2 style="display:flex;align-items:center;gap:10px;">
                <span style="width:36px;height:36px;background:#e8f5ee;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:var(--brand-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                Publicaciones recientes
            </h2>
            <a href="<?= e(url('/buscar?orden=fecha')) ?>">
                Ver todas
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
        <?php if ($recent === []): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3>Aún no hay recursos publicados</h3>
                <p>Cuando el personal autorizado publique los primeros documentos, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($recent as $item): ?>
                    <?= \App\Core\View::component('item_card', ['item' => $item]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($mostViewed !== []): ?>
<section class="section" style="padding-top:0;padding-bottom:56px;">
    <div class="container">
        <div class="section-title">
            <h2 style="display:flex;align-items:center;gap:10px;">
                <span style="width:36px;height:36px;background:#f0eafb;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#7c4dba;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                Los más consultados
            </h2>
        </div>
        <div class="grid grid-3">
            <?php foreach ($mostViewed as $item): ?>
                <?= \App\Core\View::component('item_card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
