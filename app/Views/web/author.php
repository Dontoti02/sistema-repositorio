<?php

declare(strict_types=1);

use App\Core\View;

/**
 * @var string $authorName
 * @var array{rows:array<int,array<string,mixed>>,total:int} $result
 * @var int $page
 */
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <a href="<?= e(url('/autores')) ?>">Autores</a> ›
            <span><?= e($authorName) ?></span>
        </nav>
        <h1>Publicaciones de <?= e($authorName) ?></h1>
        <div class="grid grid-3 mt-3">
            <?php foreach ($result['rows'] as $item): ?>
                <?= View::component('item_card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
        <?= View::partial('components/pagination', ['page' => $page, 'total' => $result['total'], 'perPage' => 12, 'filters' => []]) ?>
    </div>
</section>
