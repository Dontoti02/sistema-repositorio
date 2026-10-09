<?php

declare(strict_types=1);
/** @var string $content */
/** @var array<string,mixed>|null $authUser */
/** @var array<int,array{type:string,message:string}> $flashes */

use App\Core\TenantContext;
use App\Services\BrandAssetService;
use App\Services\ThemeService;

$inst = TenantContext::institution();
$instId = (int) ($inst['id'] ?? 1);
$theme = ThemeService::getPublishedTheme($instId);
$tokens = $theme['tokens'];
$texts  = $theme['texts'];
$layout = $theme['layout'];

$instName = !empty($texts['site_name']) ? $texts['site_name'] : ($inst['name'] ?? config('app.name', 'Repositorio Institucional'));
$instShort = !empty($texts['site_short']) ? $texts['site_short'] : ($inst['short_name'] ?? null);
$logo = BrandAssetService::getAssetUrl($instId, BrandAssetService::KIND_LOGO_PRIMARY)
    ?? (!empty($inst['logo_path']) ? url('storage/media/' . $inst['logo_path']) : null);
$favicon = BrandAssetService::getAssetUrl($instId, BrandAssetService::KIND_FAVICON)
    ?? (!empty($inst['favicon_path']) ? url('storage/media/' . $inst['favicon_path']) : null);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$navLinks = [
    '/'                  => ['label' => 'Inicio',             'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
    '/comunidades'       => ['label' => 'Explorar',           'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'],
    '/buscar'            => ['label' => 'Buscar',             'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'],
    '/busqueda-avanzada' => ['label' => 'Búsqueda avanzada', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>'],
    '/acerca-de'         => ['label' => 'Acerca de',         'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'],
];
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? ($instName . ' — Repositorio institucional')) ?></title>
    <?php if (!empty($metaDescription)): ?>
        <meta name="description" content="<?= e($metaDescription) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= e(url($path)) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle ?? $instName) ?>">
    <?php if (!empty($metaDescription)): ?>
        <meta property="og:description" content="<?= e($metaDescription) ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= e(url($path)) ?>">
    <meta property="og:site_name" content="<?= e($instName) ?>">
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= e($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url("assets/temas/{$instId}/" . ($theme['version'] ?? 1) . '.css')) ?>">
    <?php if (!empty($schemaJson)): ?>
        <script type="application/ld+json"><?= $schemaJson ?></script>
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido principal</a>

<div class="instbar">
    <div class="container">
        <span style="display:flex;align-items:center;gap:7px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <?= e($instName) ?>
        </span>
        <span style="display:flex;align-items:center;gap:10px;">
            <?php if (!empty($inst['website'])): ?>
                <a href="<?= e($inst['website']) ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Sitio web institucional
                </a>
            <?php endif; ?>
            <?php if (!empty($inst['email'])): ?>
                <a href="mailto:<?= e($inst['email']) ?>" style="display:flex;align-items:center;gap:5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <?= e($inst['email']) ?>
                </a>
            <?php endif; ?>
        </span>
    </div>
</div>

<header class="site-header" id="site-header">
    <div class="container">
        <a class="brand" href="<?= e(url('/')) ?>">
            <?php if ($logo): ?>
                <img class="logo" src="<?= e($logo) ?>" alt="Escudo de <?= e($instName) ?>">
            <?php else: ?>
                <div class="brand-fallback" title="<?= e($instName) ?>">
                    <?= e(strtoupper(mb_substr($instShort ?: $instName, 0, 2))) ?>
                </div>
            <?php endif; ?>
            <span class="brand-name"><?= e($instShort ?? $instName) ?><span class="brand-short" style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:500;">Repositorio institucional</span></span>
        </a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="menu-principal" aria-label="Abrir menú">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <nav class="main-nav" id="menu-principal" aria-label="Navegación principal">
            <?php foreach ($navLinks as $href => $nav): ?>
                <a href="<?= e(url($href)) ?>"<?= $path === $href || ($href !== '/' && str_starts_with($path, $href)) ? ' aria-current="page"' : '' ?>>
                    <?= $nav['icon'] ?>
                    <?= e($nav['label']) ?>
                </a>
            <?php endforeach; ?>
            <?php if ($authUser): ?>
                <a class="btn btn-sm" href="<?= e(url('/admin')) ?>" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Mi panel
                </a>
            <?php else: ?>
                <a class="btn btn-sm btn-secondary" href="<?= e(url('/login')) ?>" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Iniciar sesión
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="contenido">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <?= e($instName) ?>
                </div>
                <?php if (!empty($inst['address'])): ?><p style="margin-bottom:6px;font-size:.84rem;"><?= e($inst['address']) ?></p><?php endif; ?>
                <?php if (!empty($inst['phone'])): ?>
                    <p style="font-size:.84rem;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.85a16 16 0 0 0 6.15 6.15l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?= e($inst['phone']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($inst['email'])): ?>
                    <p style="font-size:.84rem;">
                        <a href="mailto:<?= e($inst['email']) ?>" style="display:flex;align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <?= e($inst['email']) ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <h4>Repositorio</h4>
                <p style="display:flex;flex-direction:column;gap:8px;font-size:.87rem;">
                    <a href="<?= e(url('/comunidades')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Comunidades y colecciones
                    </a>
                    <a href="<?= e(url('/busqueda-avanzada')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Búsqueda avanzada
                    </a>
                    <a href="<?= e(url('/estadisticas')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Estadísticas
                    </a>
                    <a href="<?= e(url('/oai')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        OAI-PMH
                    </a>
                </p>
            </div>
            <div>
                <h4>Información</h4>
                <p style="display:flex;flex-direction:column;gap:8px;font-size:.87rem;">
                    <a href="<?= e(url('/acerca-de')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Acerca del repositorio
                    </a>
                    <a href="<?= e(url('/politicas')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Políticas y privacidad
                    </a>
                    <a href="<?= e(url('/contacto')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Contacto
                    </a>
                    <a href="<?= e(url('/preguntas-frecuentes')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Preguntas frecuentes
                    </a>
                    <a href="<?= e(url('/manual')) ?>" style="display:flex;align-items:center;gap:7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        Manual de uso
                    </a>
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <span><?= e($instName) ?> — Repositorio digital de acceso abierto.</span>
            <span>Los metadatos se publican bajo términos indicados en cada recurso.</span>
        </div>
    </div>
</footer>

<div class="toasts" role="status" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast <?= e($flash['type']) ?>">
            <?php if ($flash['type'] === 'success'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            <?php elseif ($flash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php elseif ($flash['type'] === 'warning'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php endif; ?>
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>
</div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
