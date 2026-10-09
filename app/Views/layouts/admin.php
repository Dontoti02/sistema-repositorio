<?php

declare(strict_types=1);
/** @var string $content */
/** @var array<string,mixed>|null $authUser */
/** @var array<int,array{type:string,message:string}> $flashes */

use App\Core\Authorization;
use App\Core\TenantContext;

$inst = TenantContext::institution();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Iconos SVG para el sidebar
$sideIcons = [
    '/admin'                    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    '/admin/buscar'             => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    '/admin/notificaciones'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    '/admin/recursos'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    '/admin/revision'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    '/admin/deposito'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
    '/admin/comunidades'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    '/admin/calidad'            => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    '/admin/institucion'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    '/admin/apariencia'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C21.996 6.5 17.5 2 12 2z"/></svg>',
    '/admin/usuarios'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',

    '/admin/roles'              => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    '/admin/programas'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    '/admin/tipos-documentales' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    '/admin/metadatos'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    '/admin/reportes'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    '/admin/auditoria'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    '/admin/importar-exportar'  => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg>',
    '/admin/sistema'            => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41"/><path d="M21 12h-2"/><path d="M17.66 17.66l-1.41-1.41"/><path d="M12 21v-2"/><path d="M7.75 17.75l-1.41 1.41"/><path d="M5 12H3"/><path d="M6.34 6.34L4.93 4.93"/><path d="M12 5V3"/></svg>',
];

$sections = [
    'Principal' => [
        '/admin'                 => ['dashboard.view', null],
        '/admin/buscar'          => ['items.read_all', null],
        '/admin/notificaciones'  => [null, null],
    ],
    'Contenido' => [
        '/admin/recursos'        => ['items.read_all', null],
        '/admin/revision'        => ['items.review', null],
        '/admin/deposito'        => ['items.create', null],
        '/admin/comunidades'     => ['communities.manage', null],
        '/admin/calidad'         => ['items.read_all', null],
    ],
    'Administración' => [
        '/admin/institucion'        => ['institution.read', null],
        '/admin/apariencia'         => ['institution.update', null],
        '/admin/usuarios'           => ['users.view', null],
        '/admin/roles'              => ['roles.manage', null],
        '/admin/programas'          => ['programs.manage', null],
        '/admin/tipos-documentales' => ['metadata.manage', null],
        '/admin/metadatos'          => ['metadata.manage', null],
        '/admin/reportes'           => ['reports.view', null],
        '/admin/auditoria'          => ['audit.view', null],
        '/admin/importar-exportar'  => ['exports.manage', null],
        '/admin/sistema'            => ['settings.manage', null],
    ],
];
$labels = [
    '/admin/institucion'        => 'Institución',
    '/admin/apariencia'         => 'Apariencia e identidad',
    '/admin/notificaciones'     => 'Notificaciones',
    '/admin/importar-exportar'  => 'Importar / Exportar',
    '/admin/tipos-documentales' => 'Tipos documentales',
];

// Helper: obtener iniciales del usuario
$initials = '';
if (!empty($authUser['full_name'])) {
    $parts = explode(' ', trim((string) $authUser['full_name']));
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= strtoupper(mb_substr($p, 0, 1));
    }
}
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Panel') ?> — <?= e($inst['name'] ?? 'Repositorio') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <style>:root{--brand-primary:<?= e($inst['primary_color'] ?? '#0b3d6b') ?>;--brand-secondary:<?= e($inst['secondary_color'] ?? '#12634a') ?>;--brand-accent:<?= e($inst['accent_color'] ?? '#c9922b') ?>;}</style>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido principal</a>

<!-- Overlay para sidebar móvil -->
<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

<div class="admin-shell">
    <!-- ============================================================
         SIDEBAR
    ============================================================ -->
    <aside class="admin-sidebar" id="sidebar-admin" aria-label="Navegación del panel">
        <a class="side-brand" href="<?= e(url('/')) ?>" title="Ir al sitio público">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div class="side-brand-text">
                <span>Repositorio</span>
                <span class="side-brand-sub">Panel de administración</span>
            </div>
        </a>

        <nav class="sidebar-nav" aria-label="Menú del panel">
            <?php foreach ($sections as $group => $links): ?>
                <?php
                $visible = array_filter($links, static fn (array $l): bool => $l[0] === null || Authorization::can($l[0]));
                if ($visible === []) { continue; }
                ?>
                <div class="side-group"><?= e($group) ?></div>
                <?php foreach ($visible as $href => [$perm, $sub]): ?>
                    <?php $label = $labels[$href] ?? (basename($href) === 'admin' ? 'Escritorio' : ucwords(str_replace(['-', '/admin/'], [' ', ''], $href))); ?>
                    <a href="<?= e(url($href)) ?>"<?= $path === $href || ($href !== '/admin' && str_starts_with($path, $href . '/')) ? ' aria-current="page"' : '' ?>>
                        <span class="nav-icon" aria-hidden="true"><?= $sideIcons[$href] ?? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>' ?></span>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.40);font-size:.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Sistema de Repositorio Institucional
            </div>
        </div>
    </aside>

    <!-- ============================================================
         ÁREA PRINCIPAL
    ============================================================ -->
    <div class="admin-main">
        <!-- TOPBAR -->
        <div class="admin-topbar">
            <button class="btn btn-sm btn-ghost sidebar-toggle" type="button" data-sidebar-toggle aria-controls="sidebar-admin" aria-label="Abrir menú lateral">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <strong><?= e($inst['name'] ?? '') ?></strong>

            <div class="user-box">
                <?php
                $helpSeg = '';
                if ($path === '/admin') {
                    $helpSeg = 'inicio';
                } elseif (str_starts_with($path, '/admin/')) {
                    $helpSeg = explode('/', substr($path, 7))[0];
                }
                ?>
                <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/ayuda' . ($helpSeg !== '' ? '?seccion=' . rawurlencode($helpSeg) : ''))) ?>" title="Ayuda">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span class="visually-hidden">Ayuda</span>
                </a>

                <?php if (!empty($authUser)): ?>
                    <div class="user-avatar" title="<?= e($authUser['full_name'] ?? '') ?>"><?= e($initials ?: '?') ?></div>
                    <div style="display:flex;flex-direction:column;line-height:1.3;min-width:0;">
                        <span style="font-weight:600;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;"><?= e($authUser['full_name'] ?? '') ?></span>
                        <?php if (!empty($authUser['role_names'])): ?>
                            <span class="badge badge-status" style="font-size:.70rem;padding:1px 7px;"><?= e(implode(', ', $authUser['role_names'])) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0;">
                    <?= \App\Core\Csrf::field() ?>
                    <button class="btn btn-sm btn-secondary" type="submit" title="Cerrar sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Salir
                    </button>
                </form>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="admin-content" id="contenido">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>" role="alert">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                    <?php elseif ($flash['type'] === 'error'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php endif; ?>
                    <span><?= e($flash['message']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong>Revise los campos marcados:</strong>
                        <ul class="mt-1" style="margin:0;padding-left:16px;">
                            <?php foreach ($errors as $field => $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </div>
</div>

<div class="toasts" role="status" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast <?= e($flash['type']) ?>">
            <?php if ($flash['type'] === 'success'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            <?php elseif ($flash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
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
