<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $communities */
?>
<section class="section">
    <div class="container">
        <h1>Comunidades y colecciones</h1>
        <p class="muted">Organización temática del repositorio. Cada comunidad agrupa colecciones con políticas de depósito propias.</p>
        <?php if ($communities === []): ?>
            <div class="empty-state">
                <h3>No hay comunidades publicadas</h3>
                <p>El personal administrador aún no ha creado comunidades visibles para el público.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3 mt-3">
                <?php foreach ($communities as $c): ?>
                    <div class="card card-hover">
                        <h2 style="font-size:1.1rem"><a href="<?= e(url('/comunidades/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></h2>
                        <?php if (!empty($c['description'])): ?>
                            <p class="muted small"><?= e(mb_substr((string) $c['description'], 0, 170)) ?></p>
                        <?php endif; ?>
                        <p class="small">
                            <span class="badge badge-status"><?= (int) $c['collections_count'] ?> colección(es)</span>
                            <span class="badge badge-published"><?= (int) $c['items_count'] ?> recurso(s)</span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
