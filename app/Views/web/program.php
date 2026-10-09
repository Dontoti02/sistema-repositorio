<?php

declare(strict_types=1);

use App\Core\View;

/**
 * @var array<string,mixed> $program
 * @var array{rows:array<int,array<string,mixed>>,total:int} $result
 * @var int $page
 */
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <span><?= e($program['name']) ?></span>
        </nav>
        <h1>Programa: <?= e($program['name']) ?></h1>
        <?php if (!empty($program['description'])): ?><p style="white-space:pre-wrap"><?= e($program['description']) ?></p><?php endif; ?>

        <div class="section-title mt-3">
            <h2>Publicaciones (<?= number_format($result['total'], 0, ',', '.') ?>)</h2>
        </div>
        <?php if ($result['total'] === 0): ?>
            <div class="empty-state">
                <h3>Sin publicaciones de este programa</h3>
                <p>Cuando se publiquen recursos asociados, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($result['rows'] as $item): ?>
                    <?= View::component('item_card', ['item' => $item]) ?>
                <?php endforeach; ?>
            </div>
            <?= View::partial('components/pagination', ['page' => $page, 'total' => $result['total'], 'perPage' => 12, 'filters' => []]) ?>
        <?php endif; ?>
    </div>
</section>
