<?php

declare(strict_types=1);

/**
 * @var array<string,mixed> $community
 * @var array<int,array<string,mixed>> $collections
 * @var array<int,array<string,mixed>> $children
 */
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <a href="<?= e(url('/comunidades')) ?>">Comunidades</a> ›
            <span><?= e($community['name']) ?></span>
        </nav>
        <h1><?= e($community['name']) ?></h1>
        <?php if (!empty($community['description'])): ?>
            <p style="white-space:pre-wrap"><?= e($community['description']) ?></p>
        <?php endif; ?>

        <?php if ($children !== []): ?>
            <h2 class="mt-3" style="font-size:1.15rem">Subcomunidades</h2>
            <div class="grid grid-3">
                <?php foreach ($children as $child): ?>
                    <div class="card card-hover"><h3 style="font-size:1rem"><a href="<?= e(url('/comunidades/' . $child['slug'])) ?>"><?= e($child['name']) ?></a></h3></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="mt-3" style="font-size:1.15rem">Colecciones</h2>
        <?php if ($collections === []): ?>
            <div class="empty-state">
                <h3>Esta comunidad aún no tiene colecciones públicas</h3>
                <p>Las colecciones aparecerán aquí cuando sean creadas y publicadas.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($collections as $c): ?>
                    <div class="card card-hover">
                        <h3 style="font-size:1.02rem"><a href="<?= e(url('/colecciones/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></h3>
                        <?php if (!empty($c['description'])): ?><p class="muted small"><?= e(mb_substr((string) $c['description'], 0, 150)) ?></p><?php endif; ?>
                        <span class="badge badge-published"><?= (int) $c['items_count'] ?> recurso(s)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
