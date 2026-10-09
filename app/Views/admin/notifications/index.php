<?php

declare(strict_types=1);

use App\Core\View;

/**
 * Bandeja de notificaciones internas (6.15).
 *
 * @var array<int,array<string,mixed>> $items
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var bool $unreadOnly
 * @var int $unread
 */
?>
<div class="flex-between">
    <div>
        <h1>Notificaciones</h1>
        <p class="muted">Avisos del sistema y del flujo editorial. <?= $unread > 0 ? '<strong>' . $unread . ' sin leer</strong>' : 'Todo leído.' ?></p>
    </div>
    <div class="flex gap-2" style="flex-wrap:wrap">
        <a class="btn btn-secondary" href="<?= e(url('/admin/notificaciones')) ?>">Todas</a>
        <a class="btn btn-secondary" href="<?= e(url('/admin/notificaciones?filtro=no-leidas')) ?>">No leídas</a>
        <a class="btn btn-secondary" href="<?= e(url('/admin/notificaciones/preferencias')) ?>">Preferencias</a>
        <?php if ($unread > 0): ?>
            <form method="post" action="<?= e(url('/admin/notificaciones/leer-todas')) ?>">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-primary" type="submit">Marcar todas como leídas</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="empty-state">
        <h3>Sin notificaciones</h3>
        <p><?php echo $unreadOnly ? 'No tiene notificaciones sin leer.' : 'Aún no recibe avisos. Las asignaciones y observaciones de revisión aparecerán aquí.'; ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead><tr><th>Fecha (UTC)</th><th>Título</th><th>Mensaje</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $n): ?>
                <tr<?= $n['read_at'] === null ? ' style="background:var(--surface-2)"' : '' ?>>
                    <td class="small"><?= format_date_es($n['created_at']) ?></td>
                    <td class="small">
                        <?= $n['read_at'] === null ? '<strong>' : '' ?><?= e($n['title']) ?><?= $n['read_at'] === null ? '</strong>' : '' ?>
                        <br><span class="muted"><code><?= e($n['type']) ?></code></span>
                    </td>
                    <td class="small" style="max-width:480px"><?= e(mb_substr((string) $n['message'], 0, 300)) ?></td>
                    <td class="small" style="white-space:nowrap">
                        <?php if ($n['read_at'] === null): ?>
                            <form method="post" action="<?= e(url('/admin/notificaciones/' . (int) $n['id'] . '/leer')) ?>" style="display:inline">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="btn btn-sm" type="submit">Marcar leída</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($n['link'] !== null && $n['link'] !== ''): ?>
                            <a class="btn btn-sm btn-secondary" href="<?= e(url((string) $n['link'])) ?>">Ver</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= View::partial('components/pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'filters' => $unreadOnly ? ['filtro' => 'no-leidas'] : [], 'label' => 'notificación(es)']) ?>
<?php endif; ?>
