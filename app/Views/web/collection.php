<?php

declare(strict_types=1);

use App\Core\View;

/**
 * @var array<string,mixed> $collection
 * @var array{rows:array<int,array<string,mixed>>,total:int} $result
 * @var int $page
 */
$perPage = 12;
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <a href="<?= e(url('/comunidades')) ?>">Comunidades</a> ›
            <a href="<?= e(url('/comunidades/' . $collection['community_slug'])) ?>"><?= e($collection['community_name']) ?></a> ›
            <span><?= e($collection['name']) ?></span>
        </nav>
        <h1><?= e($collection['name']) ?></h1>
        <?php if (!empty($collection['description'])): ?>
            <p style="white-space:pre-wrap"><?= e($collection['description']) ?></p>
        <?php endif; ?>

        <div class="section-title mt-3">
            <h2>Recursos (<?= number_format($result['total'], 0, ',', '.') ?>)</h2>
            <form method="get" action="<?= e(url('/buscar')) ?>">
                <input type="hidden" name="coleccion" value="<?= e($collection['slug']) ?>">
                <label class="visually-hidden" for="cq">Buscar en esta colección</label>
                <input type="search" id="cq" name="q" placeholder="Buscar aquí…" maxlength="200">
                <button class="btn btn-sm" type="submit">Buscar</button>
            </form>
        </div>

        <?php if ($result['total'] === 0): ?>
            <div class="empty-state">
                <h3>Colección sin recursos publicados</h3>
                <p>No hay resultados que mostrar (no se inventan estadísticas).</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($result['rows'] as $item): ?>
                    <?= View::component('item_card', ['item' => $item]) ?>
                <?php endforeach; ?>
            </div>
            <?= View::partial('components/pagination', ['page' => $page, 'total' => $result['total'], 'perPage' => $perPage, 'filters' => []]) ?>
        <?php endif; ?>
    </div>
</section>
