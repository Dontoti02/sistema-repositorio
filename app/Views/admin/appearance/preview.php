<?php

declare(strict_types=1);

/** @var array<string,mixed> $theme */
/** @var array<string,mixed> $institution */
/** @var array<string,string|null> $assets */

use App\Services\ThemeService;

$tokens = $theme['tokens'];
$layout = $theme['layout'];
$texts  = $theme['texts'];

$instName  = $texts['site_name'] ?: ($institution['name'] ?? 'Repositorio Institucional');
$instShort = $texts['site_short'] ?: ($institution['short_name'] ?? 'RI');
$logoUrl   = $assets['logo_primary'] ?? null;
$heroUrl   = $assets['hero'] ?? null;
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista previa — <?= e($instName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <style id="theme-preview-vars">
        <?= ThemeService::compileCss($tokens) ?>
    </style>
</head>
<body style="min-height:100vh;display:flex;flex-direction:column;background:var(--page-bg);">

    <!-- Barra institucional -->
    <div class="instbar">
        <div class="container">
            <span style="display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                <?= e($instName) ?>
            </span>
            <span style="opacity:0.8;font-size:0.75rem;">Portal del Repositorio Digital</span>
        </div>
    </div>

    <!-- Cabecera -->
    <header class="site-header">
        <div class="container">
            <a class="brand" href="javascript:void(0);">
                <?php if ($logoUrl): ?>
                    <img class="logo" id="preview-logo-img" src="<?= e($logoUrl) ?>" alt="Logo">
                <?php else: ?>
                    <div class="brand-fallback" id="preview-logo-fallback">
                        <?= e(strtoupper(mb_substr($instShort, 0, 2))) ?>
                    </div>
                <?php endif; ?>
                <div class="brand-text">
                    <span class="brand-name" id="preview-brand-name"><?= e($instName) ?></span>
                    <span class="brand-sub">Repositorio institucional</span>
                </div>
            </a>
            <nav class="main-nav">
                <a href="javascript:void(0);" aria-current="page">Inicio</a>
                <a href="javascript:void(0);">Explorar</a>
                <a href="javascript:void(0);">Buscar</a>
                <a href="javascript:void(0);" class="btn btn-sm btn-secondary" style="margin-left:8px;">Iniciar sesión</a>
            </nav>
        </div>
    </header>

    <!-- Hero de Inicio -->
    <?php
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
    <section class="<?= $heroClass ?>" id="preview-hero-section" style="<?= $heroStyle ?>">
        <?php if (($layout['hero_mode'] ?? '') === 'image'): ?>
            <div class="hero-overlay-dark"></div>
        <?php endif; ?>
        <div class="container">
            <h1 id="preview-hero-title"><?= e($texts['hero_title']) ?></h1>
            <p class="hero-subtitle" id="preview-hero-subtitle"><?= e($texts['hero_subtitle']) ?></p>

            <form class="hero-searchbar" onsubmit="return false;">
                <input type="search" placeholder="Buscar por título, autor, palabras clave…" disabled>
                <button class="btn btn-accent" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Buscar
                </button>
            </form>
        </div>
    </section>

    <!-- Estadísticas -->
    <section class="section" style="padding:36px 0;">
        <div class="container">
            <div class="grid grid-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div>
                        <div class="stat-number">128</div>
                        <div class="stat-label">Documentos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--brand-secondary);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <div>
                        <div class="stat-number">8</div>
                        <div class="stat-label">Comunidades</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--brand-accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div>
                        <div class="stat-number">24</div>
                        <div class="stat-label">Colecciones</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--success);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <div>
                        <div class="stat-number">1.4K</div>
                        <div class="stat-label">Descargas</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pie de página -->
    <footer class="site-footer" style="margin-top:auto;">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span id="preview-footer-name"><?= e($instName) ?></span>
                    </div>
                    <p class="small text-muted" id="preview-footer-legal" style="color:rgba(255,255,255,0.7);line-height:1.5;">
                        <?= e($texts['legal_footer']) ?>
                    </p>
                </div>
                <div>
                    <h4 style="color:#fff;margin-bottom:12px;">Enlaces</h4>
                    <p class="small" style="display:flex;flex-direction:column;gap:6px;">
                        <a href="javascript:void(0);" style="color:rgba(255,255,255,0.75);">Comunidades</a>
                        <a href="javascript:void(0);" style="color:rgba(255,255,255,0.75);">Búsqueda avanzada</a>
                        <a href="javascript:void(0);" style="color:rgba(255,255,255,0.75);">Políticas y preservación</a>
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <span><?= e($instName) ?> — Repositorio Institucional de Acceso Abierto.</span>
                <span>Conforme a la normativa nacional de repositorios digitales.</span>
            </div>
        </div>
    </footer>

    <script>
        // Escucha mensajes del personalizador padre para aplicar cambios en vivo
        window.addEventListener('message', function(event) {
            if (!event.data || typeof event.data !== 'object') return;
            var data = event.data;

            if (data.type === 'UPDATE_CSS') {
                var styleEl = document.getElementById('theme-preview-vars');
                if (styleEl && data.css) {
                    styleEl.innerHTML = data.css;
                }
            }
            if (data.type === 'UPDATE_TEXTS') {
                if (data.siteName) {
                    var el = document.getElementById('preview-brand-name');
                    if (el) el.textContent = data.siteName;
                    var fel = document.getElementById('preview-footer-name');
                    if (fel) fel.textContent = data.siteName;
                }
                if (data.heroTitle) {
                    var ht = document.getElementById('preview-hero-title');
                    if (ht) ht.textContent = data.heroTitle;
                }
                if (data.heroSubtitle) {
                    var hs = document.getElementById('preview-hero-subtitle');
                    if (hs) hs.textContent = data.heroSubtitle;
                }
                if (data.legalFooter) {
                    var lf = document.getElementById('preview-footer-legal');
                    if (lf) lf.textContent = data.legalFooter;
                }
            }
            if (data.type === 'UPDATE_HERO_MODE') {
                var hero = document.getElementById('preview-hero-section');
                if (hero) {
                    hero.className = 'hero hero-' + (data.mode || 'gradient');
                }
            }
        });
    </script>
</body>
</html>
