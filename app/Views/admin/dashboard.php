<?php

declare(strict_types=1);

/** @var array<string,int> $statusMap */
/** @var int $pending */
/** @var int $usersTotal */
/** @var int $communities */
/** @var int $collections */
/** @var int $storageBytes */
/** @var int $filesTotal */
/** @var array<int,array<string,mixed>> $recentItems */
/** @var array<int,array<string,mixed>> $activity */

use App\Core\Authorization;
use App\Core\View;

$fmtSize = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return number_format($n, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
};

$statusColors = [
    'PUBLISHED'       => 'var(--success)',
    'DRAFT'           => 'var(--muted)',
    'SUBMITTED'       => 'var(--info)',
    'UNDER_REVIEW'    => 'var(--info)',
    'APPROVED'        => 'var(--success)',
    'CHANGES_REQUESTED' => 'var(--warning)',
    'REJECTED'        => 'var(--danger)',
    'WITHDRAWN'       => 'var(--warning)',
    'ARCHIVED'        => 'var(--muted)',
];
?>
<div class="page-head">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span style="width:40px;height:40px;background:linear-gradient(135deg,var(--brand-primary),#1a6ab0);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </span>
            Escritorio
        </h1>
        <p class="muted" style="margin:0;font-size:.9rem;">Resumen operativo del repositorio. Datos en tiempo real.</p>
    </div>
    <?php if (Authorization::can('items.create')): ?>
        <div class="page-actions">
            <a class="btn btn-accent" href="<?= e(url('/admin/deposito')) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo depósito
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- KPIs fila 1 -->
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-icon" style="background:#e8f5ee;color:var(--success);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-value" style="color:var(--success);"><?= number_format($statusMap['PUBLISHED'] ?? 0, 0, ',', '.') ?></div>
        <div class="kpi-label">Publicados</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:<?= $pending > 0 ? 'var(--warning-light)' : 'var(--surface-3)' ?>;color:<?= $pending > 0 ? 'var(--warning)' : 'var(--muted)' ?>;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="kpi-value" style="color:<?= $pending > 0 ? 'var(--warning)' : 'var(--muted)' ?>;"><?= number_format($pending, 0, ',', '.') ?></div>
        <div class="kpi-label">Pendientes de revisión</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-value"><?= number_format($statusMap['DRAFT'] ?? 0, 0, ',', '.') ?></div>
        <div class="kpi-label">Borradores</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#e8f3fd;color:var(--info);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="kpi-value" style="color:var(--info);"><?= number_format($usersTotal, 0, ',', '.') ?></div>
        <div class="kpi-label">Usuarios activos</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#f0eafb;color:#7c4dba;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div class="kpi-value" style="color:#7c4dba;"><?= number_format($communities, 0, ',', '.') ?></div>
        <div class="kpi-label">Comunidades</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#fef9ec;color:var(--brand-accent);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="kpi-value" style="color:var(--brand-accent);"><?= number_format($collections, 0, ',', '.') ?></div>
        <div class="kpi-label">Colecciones</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
        </div>
        <div class="kpi-value"><?= number_format($filesTotal, 0, ',', '.') ?></div>
        <div class="kpi-label">Archivos</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#e8f5ee;color:var(--success);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="kpi-value" style="font-size:1.3rem;color:var(--success);"><?= e($fmtSize($storageBytes)) ?></div>
        <div class="kpi-label">Almacenamiento usado</div>
    </div>
</div>

<!-- Tablas de estados y actividad -->
<div class="grid grid-2 mt-2">
    <div class="card">
        <div class="flex-between" style="margin-bottom:16px;">
            <h2 style="font-size:1rem;margin:0;display:flex;align-items:center;gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Estados de los recursos
            </h2>
            <?php if (Authorization::can('items.read_all')): ?>
                <a class="btn btn-sm btn-secondary" href="<?= e(url('/admin/recursos')) ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    Ver todos
                </a>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Estado</th><th>Recursos</th></tr></thead>
                <tbody>
                <?php foreach ($statusMap as $status => $total): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?= e(strtolower($status)) ?>">
                                <?= e($status) ?>
                            </span>
                        </td>
                        <td><strong><?= number_format($total, 0, ',', '.') ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Actividad reciente
        </h2>
        <?php if ($activity === []): ?>
            <div style="text-align:center;padding:24px 0;color:var(--muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity:.4;margin:0 auto 10px;display:block;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p style="font-size:.88rem;margin:0;">Aún no hay cambios de estado registrados.</p>
            </div>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($activity as $a): ?>
                    <li style="display:flex;align-items:flex-start;gap:10px;padding-bottom:10px;border-bottom:1px solid var(--surface-3);">
                        <div style="width:32px;height:32px;background:var(--surface-3);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.78rem;font-weight:700;color:var(--muted);">
                            <?= strtoupper(mb_substr($a['full_name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <div style="min-width:0;">
                            <p style="margin:0;font-size:.85rem;">
                                <strong><?= e($a['full_name'] ?? 'Sistema') ?></strong>
                                <span class="muted"> cambió estado: </span>
                                <span class="muted"><?= e($a['from_status'] ?? '—') ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline;vertical-align:middle;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                <span class="badge badge-<?= e(strtolower($a['to_status'])) ?>" style="font-size:.70rem;"><?= e($a['to_status']) ?></span>
                            </p>
                            <p style="margin:4px 0 0;font-size:.80rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e(mb_substr((string) $a['title'], 0, 60)) ?></p>
                            <p style="margin:2px 0 0;font-size:.76rem;color:var(--muted-2);"><?= format_date_es($a['created_at']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Últimos recursos modificados -->
<div class="card mt-2">
    <div class="flex-between" style="margin-bottom:18px;">
        <h2 style="font-size:1rem;margin:0;display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Últimos recursos modificados
        </h2>
    </div>
    <?php if ($recentItems === []): ?>
        <div class="empty-state" style="padding:36px;">
            <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <h3>No hay recursos todavía</h3>
            <p>Comience con un <a href="<?= e(url('/admin/deposito')) ?>">depósito guiado</a>.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="display:flex;align-items:center;gap:5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                            ID
                        </th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Modificado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentItems as $it): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/recursos/' . $it['id'])) ?>" style="font-family:monospace;font-size:.82rem;"><?= e($it['public_id']) ?></a></td>
                        <td style="max-width:400px;">
                            <span style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px;" title="<?= e($it['title']) ?>">
                                <?= e(mb_substr((string) $it['title'], 0, 70)) ?>
                            </span>
                        </td>
                        <td><span class="badge badge-<?= e(strtolower($it['status'])) ?>"><?= e($it['status']) ?></span></td>
                        <td style="color:var(--muted);font-size:.85rem;white-space:nowrap;"><?= format_date_es($it['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
