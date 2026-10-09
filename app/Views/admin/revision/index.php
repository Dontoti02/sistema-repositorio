<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Bandejas de revision (6.10).
 *
 * @var string $tab
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $reviewers
 * @var array<string,int> $counts
 */

$tabs = [
    'pendientes' => ['Pendientes', $counts['pendientes'] ?? 0],
    'asignados' => ['Mis asignaciones', $counts['mis-asignaciones'] ?? 0],
    'observados' => ['Observados', $counts['observados'] ?? 0],
    'aprobados' => ['Aprobados', $counts['aprobados'] ?? 0],
    'publicados' => ['Publicados', $counts['publicados'] ?? 0],
    'por-vencer' => ['Por vencer', $counts['por-vencer'] ?? 0],
];
$empty = [
    'pendientes' => 'No hay recursos esperando revisión.',
    'asignados' => 'No tiene revisiones asignadas.',
    'observados' => 'No hay recursos observados.',
    'aprobados' => 'No hay recursos aprobados pendientes de publicación.',
    'publicados' => 'Aún no hay recursos publicados.',
    'por-vencer' => 'No hay asignaciones por vencer en los próximos 3 días.',
];
?>
<h1>Revisión editorial</h1>
<p class="muted">Bandejas del flujo (6.10). Las transiciones de estado se ejecutan desde la ficha de cada recurso.</p>

<div class="tabs mt-3" role="tablist">
    <?php foreach ($tabs as $key => [$label, $count]): ?>
        <a class="tab<?= $tab === $key ? ' active' : '' ?>" href="<?= e(url('/admin/revision?bandeja=' . $key)) ?>">
            <?= e($label) ?> <span class="badge badge-status"><?= (int) $count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <h3>Sin recursos</h3>
        <p><?= e($empty[$tab] ?? 'Sin resultados.') ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap mt-3">
        <table>
            <thead>
                <tr>
                    <th>Identificador</th><th>Título</th><th>Estado</th><th>Comunidad / Colección</th>
                    <th>Depositario</th><th>Enviado</th><th>Revisor / vence</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $a = $row['assignment'] ?? null; ?>
                <tr>
                    <td><a href="<?= e(url('/admin/recursos/' . $row['id'])) ?>"><strong><?= e($row['public_id']) ?></strong></a></td>
                    <td><?= e(mb_substr((string) $row['title'], 0, 60)) ?></td>
                    <td><span class="badge badge-<?= e(strtolower((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td class="small"><?= e($row['community_name'] ?? '—') ?> / <?= e($row['collection_name'] ?? '—') ?></td>
                    <td class="small"><?= e($row['creator_name'] ?? '—') ?></td>
                    <td class="small"><?= $row['submitted_at'] !== null ? format_date_es($row['submitted_at']) : '—' ?></td>
                    <td class="small">
                        <?php if ($a !== null): ?>
                            <?= e($a['reviewer_name']) ?><br>
                            <?php if ($a['due_at'] !== null): ?>
                                <span style="<?= strtotime((string) $a['due_at']) < time() ? 'color:var(--danger)' : '' ?>">vence <?= format_date_es($a['due_at'], 'd/m/Y') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="muted">sin asignar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex">
                            <a class="btn btn-sm" href="<?= e(url('/admin/recursos/' . $row['id'])) ?>">Ver</a>
                            <?php if ($reviewers !== []): ?>
                                <details style="position:relative">
                                    <summary class="btn btn-sm btn-secondary" style="list-style:none">Asignar</summary>
                                    <form method="post" action="<?= e(url('/admin/revision/asignar')) ?>" class="card p-2" style="min-width:230px;position:absolute;right:0;z-index:5">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="desde_tab" value="<?= e($tab) ?>">
                                        <div class="field mb-1">
                                            <label class="small" for="rev-<?= (int) $row['id'] ?>">Revisor</label>
                                            <select id="rev-<?= (int) $row['id'] ?>" name="reviewer_id" required>
                                                <option value="">Seleccione…</option>
                                                <?php foreach ($reviewers as $rv): ?>
                                                    <option value="<?= (int) $rv['id'] ?>"<?= $a !== null && (int) ($a['reviewer_id'] ?? 0) === (int) $rv['id'] ? ' selected' : '' ?>>
                                                        <?= e($rv['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field mb-1">
                                            <label class="small" for="due-<?= (int) $row['id'] ?>">Vence (opcional)</label>
                                            <input type="date" id="due-<?= (int) $row['id'] ?>" name="due_at" value="<?= e(!empty($a['due_at']) ? substr((string) $a['due_at'], 0, 10) : '') ?>">
                                        </div>
                                        <button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($rows) >= 200): ?>
        <p class="small muted mt-2">Se muestran los primeros 200 recursos de esta bandeja.</p>
    <?php endif; ?>
<?php endif; ?>
