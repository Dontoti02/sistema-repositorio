<?php

declare(strict_types=1);

/** @var array<string,mixed> $institution */
/** @var array<string,mixed> $publishedTheme */
/** @var array<string,mixed>|null $draftTheme */
/** @var array<string,mixed> $activeTheme */
/** @var array<string,string|null> $assets */
/** @var array<string,array<string,string>> $presets */
/** @var array<int,array<string,mixed>> $history */

$tokens = $activeTheme['tokens'];
$layout = $activeTheme['layout'];
$texts  = $activeTheme['texts'];
?>

<div class="appearance-wrapper" id="appearance-app"
     data-csrf="<?= e(\App\Core\Csrf::token()) ?>"
     data-inst-id="<?= (int) ($institution['id'] ?? 1) ?>"
     data-save-draft-url="<?= e(url('/admin/apariencia/borrador')) ?>"
     data-publish-url="<?= e(url('/admin/apariencia/publicar')) ?>"
     data-restore-url="<?= e(url('/admin/apariencia/restaurar')) ?>"
     data-upload-url="<?= e(url('/admin/apariencia/activos')) ?>"
     data-delete-asset-url="<?= e(url('/admin/apariencia/activos/eliminar')) ?>"
     data-reset-palette-url="<?= e(url('/admin/apariencia/restablecer-paleta')) ?>"
     data-preview-url="<?= e(url('/admin/apariencia/vista-previa')) ?>">

    <!-- BARRA SUPERIOR DE ACCIONES -->
    <header class="appearance-toolbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="display:flex;flex-direction:column;">
                <strong style="font-size:1.05rem;">Personalizador de identidad</strong>
                <span style="font-size:0.75rem;color:var(--text-muted);" id="theme-status-indicator">
                    <?= $draftTheme !== null ? '● Borrador con cambios pendientes' : '✓ Todo publicado (v' . ($publishedTheme['version'] ?? 1) . ')' ?>
                </span>
            </div>
        </div>

        <!-- Conmutador de Dispositivos para Vista Previa -->
        <div class="appearance-device-switcher" role="radiogroup" aria-label="Dispositivo de vista previa">
            <button type="button" class="device-btn active" data-device="desktop" aria-pressed="true" title="Vista escritorio (100%)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Escritorio
            </button>
            <button type="button" class="device-btn" data-device="tablet" aria-pressed="false" title="Vista tablet (768px)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Tablet
            </button>
            <button type="button" class="device-btn" data-device="mobile" aria-pressed="false" title="Vista móvil (375px)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Móvil
            </button>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" class="btn btn-sm btn-outline" id="btn-save-draft">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar borrador
            </button>
            <button type="button" class="btn btn-sm btn-accent" id="btn-publish-theme">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                Publicar cambios
            </button>
        </div>
    </header>

    <!-- CUERPO SPLIT-SCREEN -->
    <div class="appearance-body">

        <!-- PANEL LATERAL DE CONTROLES -->
        <aside class="appearance-sidebar" aria-label="Ajustes de apariencia">
            <nav class="appearance-tabs-nav" role="tablist">
                <button type="button" class="appearance-tab-btn active" data-tab="tab-identidad" role="tab" aria-selected="true" aria-controls="panel-identidad">Identidad</button>
                <button type="button" class="appearance-tab-btn" data-tab="tab-logos" role="tab" aria-selected="false" aria-controls="panel-logos">Logotipos</button>
                <button type="button" class="appearance-tab-btn" data-tab="tab-favicon" role="tab" aria-selected="false" aria-controls="panel-favicon">Favicon</button>
                <button type="button" class="appearance-tab-btn" data-tab="tab-colores" role="tab" aria-selected="false" aria-controls="panel-colores">Colores</button>
                <button type="button" class="appearance-tab-btn" data-tab="tab-portada" role="tab" aria-selected="false" aria-controls="panel-portada">Inicio</button>
                <button type="button" class="appearance-tab-btn" data-tab="tab-historial" role="tab" aria-selected="false" aria-controls="panel-historial">Historial</button>
            </nav>

            <form id="theme-form" class="appearance-tabs-content" onsubmit="return false;">

                <!-- 1. IDENTIDAD -->
                <div class="appearance-tab-panel active" id="panel-identidad" role="tabpanel">
                    <h3 class="mb-4">Identidad institucional</h3>
                    <div class="form-group">
                        <label class="form-label" for="inp-site-name">Nombre oficial del instituto</label>
                        <input type="text" class="form-control" id="inp-site-name" name="texts[site_name]"
                               value="<?= e($texts['site_name'] ?: ($institution['name'] ?? '')) ?>" maxlength="200" required>
                        <span class="form-hint">Nombre que figurará en el encabezado y metadatos oficiales.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inp-site-short">Sigla o nombre corto</label>
                        <input type="text" class="form-control" id="inp-site-short" name="texts[site_short]"
                               value="<?= e($texts['site_short'] ?: ($institution['short_name'] ?? '')) ?>" maxlength="60">
                        <span class="form-hint">Ej: IESTP Perú, IESP Víctor Andrés Belaúnde.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inp-motto">Lema o frase distintiva</label>
                        <input type="text" class="form-control" id="inp-motto" name="texts[motto]"
                               value="<?= e($texts['motto']) ?>" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inp-legal-footer">Pie legal y derechos</label>
                        <textarea class="form-control" id="inp-legal-footer" name="texts[legal_footer]" rows="3" maxlength="400"><?= e($texts['legal_footer']) ?></textarea>
                    </div>
                </div>

                <!-- 2. LOGOTIPOS -->
                <div class="appearance-tab-panel" id="panel-logos" role="tabpanel">
                    <h3 class="mb-2">Logotipos y símbolos</h3>
                    <p class="text-muted small mb-4">Formatos permitidos: PNG, JPEG o WebP. Fondo transparente recomendado.</p>

                    <!-- Logo Principal -->
                    <div class="mb-6">
                        <label class="form-label">Logotipo principal</label>
                        <div class="asset-uploader" data-kind="logo_primary">
                            <input type="file" class="visually-hidden asset-file-input" accept="image/png,image/jpeg,image/webp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px auto;color:var(--brand-primary);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="font-weight:600;font-size:0.88rem;">Haga clic para subir el logo principal</span>
                            <span class="form-hint" style="display:block;margin-top:4px;">Recomendado: 240x80 px, máx 2MB</span>
                        </div>
                        <div class="asset-preview-box" id="box-logo_primary" style="<?= empty($assets['logo_primary']) ? 'display:none;' : '' ?>">
                            <img class="asset-preview-img" src="<?= e($assets['logo_primary'] ?? '') ?>" alt="Logo actual">
                            <button type="button" class="btn btn-sm btn-ghost btn-danger btn-delete-asset" data-kind="logo_primary">Retirar</button>
                        </div>
                    </div>

                    <!-- Logo Alternativo -->
                    <div class="mb-6">
                        <label class="form-label">Logotipo alternativo (fondos oscuros)</label>
                        <div class="asset-uploader" data-kind="logo_alternate">
                            <input type="file" class="visually-hidden asset-file-input" accept="image/png,image/jpeg,image/webp">
                            <span style="font-weight:600;font-size:0.88rem;">Subir logo para fondos oscuros</span>
                        </div>
                        <div class="asset-preview-box" id="box-logo_alternate" style="<?= empty($assets['logo_alternate']) ? 'display:none;' : '' ?>background:#102d52;">
                            <img class="asset-preview-img" src="<?= e($assets['logo_alternate'] ?? '') ?>" alt="Logo alternativo">
                            <button type="button" class="btn btn-sm btn-ghost btn-danger btn-delete-asset" data-kind="logo_alternate">Retirar</button>
                        </div>
                    </div>

                    <!-- Opciones de visualización -->
                    <div class="form-group">
                        <label class="form-label" for="sel-logo-display">Visualización en cabecera</label>
                        <select class="form-control" id="sel-logo-display" name="layout[logo_display]">
                            <option value="logo_name" <?= ($layout['logo_display'] ?? '') === 'logo_name' ? 'selected' : '' ?>>Mostrar Logotipo y Nombre</option>
                            <option value="only_logo" <?= ($layout['logo_display'] ?? '') === 'only_logo' ? 'selected' : '' ?>>Mostrar Solo Logotipo</option>
                            <option value="logo_short" <?= ($layout['logo_display'] ?? '') === 'logo_short' ? 'selected' : '' ?>>Mostrar Logotipo y Sigla</option>
                        </select>
                    </div>
                </div>

                <!-- 3. FAVICON -->
                <div class="appearance-tab-panel" id="panel-favicon" role="tabpanel">
                    <h3 class="mb-2">Favicon y pestaña</h3>
                    <p class="text-muted small mb-4">Icono cuadrado visible en pestañas del navegador y marcadores.</p>

                    <div class="asset-uploader" data-kind="favicon">
                        <input type="file" class="visually-hidden asset-file-input" accept="image/png,image/x-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px auto;color:var(--brand-primary);"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
                        <span style="font-weight:600;font-size:0.88rem;">Subir Favicon (PNG cuadrado o ICO)</span>
                        <span class="form-hint" style="display:block;margin-top:4px;">Tamaño: 64x64 px a 256x256 px</span>
                    </div>

                    <div class="asset-preview-box" id="box-favicon" style="<?= empty($assets['favicon']) ? 'display:none;' : '' ?>">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="<?= e($assets['favicon'] ?? '') ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;">
                            <span class="small font-bold">Vista en pestaña</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost btn-danger btn-delete-asset" data-kind="favicon">Retirar</button>
                    </div>
                </div>

                <!-- 4. COLORES -->
                <div class="appearance-tab-panel" id="panel-colores" role="tabpanel">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <h3>Paleta de colores</h3>
                        <button type="button" class="btn btn-sm btn-ghost" id="btn-reset-palette">Restablecer paleta</button>
                    </div>

                    <!-- Paletas predefinidas como sugerencia -->
                    <div class="mb-4">
                        <label class="form-label">Paletas sugeridas</label>
                        <?php foreach ($presets as $key => $preset): ?>
                            <div class="preset-palette-card" data-primary="<?= e($preset['primary']) ?>" data-secondary="<?= e($preset['secondary']) ?>" data-accent="<?= e($preset['accent']) ?>">
                                <span class="small font-bold"><?= e($preset['name']) ?></span>
                                <div class="preset-swatches">
                                    <div class="swatch" style="background:<?= e($preset['primary']) ?>;" title="Primario"></div>
                                    <div class="swatch" style="background:<?= e($preset['secondary']) ?>;" title="Secundario"></div>
                                    <div class="swatch" style="background:<?= e($preset['accent']) ?>;" title="Acento"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h4 class="mb-2 mt-4" style="font-size:0.95rem;">Tokens principales</h4>

                    <!-- Color Primario -->
                    <div class="color-picker-row">
                        <input type="color" class="color-input-native" id="native-brand_primary" value="<?= e($tokens['brand_primary']) ?>">
                        <div class="color-meta">
                            <span class="color-label">Color primario institucional</span>
                            <span class="form-hint">Encabezados, botones primarios, identidad.</span>
                        </div>
                        <input type="text" class="color-hex-input" id="hex-brand_primary" name="tokens[brand_primary]" value="<?= e($tokens['brand_primary']) ?>" maxlength="7">
                    </div>

                    <!-- Color Secundario -->
                    <div class="color-picker-row">
                        <input type="color" class="color-input-native" id="native-brand_secondary" value="<?= e($tokens['brand_secondary']) ?>">
                        <div class="color-meta">
                            <span class="color-label">Color secundario</span>
                            <span class="form-hint">Enlaces, estados activos y acentos suaves.</span>
                        </div>
                        <input type="text" class="color-hex-input" id="hex-brand_secondary" name="tokens[brand_secondary]" value="<?= e($tokens['brand_secondary']) ?>" maxlength="7">
                    </div>

                    <!-- Color Acento -->
                    <div class="color-picker-row">
                        <input type="color" class="color-input-native" id="native-brand_accent" value="<?= e($tokens['brand_accent']) ?>">
                        <div class="color-meta">
                            <span class="color-label">Color de acento (destacados)</span>
                            <span class="form-hint">Buscador, llamadas a acción, badges.</span>
                        </div>
                        <input type="text" class="color-hex-input" id="hex-brand_accent" name="tokens[brand_accent]" value="<?= e($tokens['brand_accent']) ?>" maxlength="7">
                    </div>

                    <h4 class="mb-2 mt-4" style="font-size:0.95rem;">Estructuras</h4>

                    <!-- Fondo de cabecera -->
                    <div class="color-picker-row">
                        <input type="color" class="color-input-native" id="native-header_bg" value="<?= e($tokens['header_bg']) ?>">
                        <div class="color-meta">
                            <span class="color-label">Fondo de cabecera</span>
                        </div>
                        <input type="text" class="color-hex-input" id="hex-header_bg" name="tokens[header_bg]" value="<?= e($tokens['header_bg']) ?>" maxlength="7">
                    </div>

                    <!-- Fondo de pie -->
                    <div class="color-picker-row">
                        <input type="color" class="color-input-native" id="native-footer_bg" value="<?= e($tokens['footer_bg']) ?>">
                        <div class="color-meta">
                            <span class="color-label">Fondo de pie de página</span>
                        </div>
                        <input type="text" class="color-hex-input" id="hex-footer_bg" name="tokens[footer_bg]" value="<?= e($tokens['footer_bg']) ?>" maxlength="7">
                    </div>

                    <!-- Borde de elementos -->
                    <div class="form-group mt-4">
                        <label class="form-label" for="sel-radius">Radio de bordes (redondeo)</label>
                        <select class="form-control" id="sel-radius" name="tokens[radius_md]">
                            <option value="4px" <?= ($tokens['radius_md'] ?? '') === '4px' ? 'selected' : '' ?>>Recto / Sutil (4px)</option>
                            <option value="8px" <?= ($tokens['radius_md'] ?? '') === '8px' ? 'selected' : '' ?>>Clásico (8px)</option>
                            <option value="12px" <?= ($tokens['radius_md'] ?? '') === '12px' ? 'selected' : '' ?>>Moderno estándar (12px)</option>
                            <option value="18px" <?= ($tokens['radius_md'] ?? '') === '18px' ? 'selected' : '' ?>>Amigable (18px)</option>
                        </select>
                    </div>
                </div>

                <!-- 5. PORTADA E INICIO -->
                <div class="appearance-tab-panel" id="panel-portada" role="tabpanel">
                    <h3 class="mb-4">Portada y página de inicio</h3>

                    <div class="form-group">
                        <label class="form-label" for="sel-hero-mode">Modo de portada (Hero)</label>
                        <select class="form-control" id="sel-hero-mode" name="layout[hero_mode]">
                            <option value="gradient" <?= ($layout['hero_mode'] ?? '') === 'gradient' ? 'selected' : '' ?>>Gradiente institucional</option>
                            <option value="solid" <?= ($layout['hero_mode'] ?? '') === 'solid' ? 'selected' : '' ?>>Color sólido de marca</option>
                            <option value="image" <?= ($layout['hero_mode'] ?? '') === 'image' ? 'selected' : '' ?>>Fotografía institucional de fondo</option>
                        </select>
                    </div>

                    <!-- Portada de imagen -->
                    <div class="mb-4" id="hero-image-uploader-section">
                        <label class="form-label">Imagen de fondo institucional</label>
                        <div class="asset-uploader" data-kind="hero">
                            <input type="file" class="visually-hidden asset-file-input" accept="image/png,image/jpeg,image/webp">
                            <span style="font-weight:600;font-size:0.88rem;">Subir fotografía de portada</span>
                            <span class="form-hint" style="display:block;">Recomendado: 1920x600 px, máx 5MB</span>
                        </div>
                        <div class="asset-preview-box" id="box-hero" style="<?= empty($assets['hero']) ? 'display:none;' : '' ?>">
                            <img class="asset-preview-img" src="<?= e($assets['hero'] ?? '') ?>" alt="Portada actual">
                            <button type="button" class="btn btn-sm btn-ghost btn-danger btn-delete-asset" data-kind="hero">Retirar</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inp-hero-title">Título de portada</label>
                        <input type="text" class="form-control" id="inp-hero-title" name="texts[hero_title]"
                               value="<?= e($texts['hero_title']) ?>" maxlength="140">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inp-hero-sub">Subtítulo explicativo</label>
                        <textarea class="form-control" id="inp-hero-sub" name="texts[hero_subtitle]" rows="2" maxlength="280"><?= e($texts['hero_subtitle']) ?></textarea>
                    </div>

                    <h4 class="mb-2 mt-4" style="font-size:0.95rem;">Visibilidad de bloques de inicio</h4>
                    <label class="switch-label mb-2">
                        <input type="checkbox" class="switch-input" name="layout[show_stats]" value="1" <?= !empty($layout['show_stats']) ? 'checked' : '' ?>>
                        <span>Mostrar indicadores estadísticos en tiempo real</span>
                    </label>
                    <label class="switch-label mb-2">
                        <input type="checkbox" class="switch-input" name="layout[show_collections]" value="1" <?= !empty($layout['show_collections']) ? 'checked' : '' ?>>
                        <span>Mostrar comunidades y colecciones destacadas</span>
                    </label>
                    <label class="switch-label mb-2">
                        <input type="checkbox" class="switch-input" name="layout[show_recent]" value="1" <?= !empty($layout['show_recent']) ? 'checked' : '' ?>>
                        <span>Mostrar recursos recientemente publicados</span>
                    </label>
                </div>

                <!-- 6. HISTORIAL DE VERSIONES -->
                <div class="appearance-tab-panel" id="panel-historial" role="tabpanel">
                    <h3 class="mb-2">Historial de versiones</h3>
                    <p class="text-muted small mb-4">Registro auditable de modificaciones con capacidad de restauración segura.</p>

                    <?php if (empty($history)): ?>
                        <div class="empty-state">
                            <span class="small text-muted">No hay versiones previas archivadas.</span>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ($history as $item): ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);">
                                    <div style="display:flex;flex-direction:column;">
                                        <strong>Versión <?= (int) $item['version'] ?> (<?= e($item['status']) ?>)</strong>
                                        <span class="form-hint"><?= e(format_date_es($item['published_at'] ?: $item['created_at'])) ?></span>
                                    </div>
                                    <?php if ($item['status'] === 'archived'): ?>
                                        <button type="button" class="btn btn-sm btn-outline btn-restore-theme" data-theme-id="<?= (int) $item['id'] ?>">
                                            Restaurar
                                        </button>
                                    <?php else: ?>
                                        <span class="badge badge-open">Activa</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </form>
        </aside>

        <!-- ZONA PRINCIPAL: VISTA PREVIA INTERACTIVA -->
        <main class="appearance-preview-container" id="preview-container">
            <div class="preview-frame-wrapper" id="preview-wrapper">
                <iframe class="preview-iframe" id="preview-iframe"
                        src="<?= e(url('/admin/apariencia/vista-previa')) ?>"
                        title="Vista previa interactiva del portal público"></iframe>
            </div>
        </main>

    </div>
</div>

<!-- Módulo JS del editor -->
<script type="module" src="<?= e(asset('js/modules/theme-editor.js')) ?>"></script>
