<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $authors */
?>
<section class="section">
    <div class="container">
        <h1>Autores</h1>
        <p class="muted">Autores con publicaciones registradas en el repositorio.</p>
        <?php if ($authors === []): ?>
            <div class="empty-state">
                <h3>Aún no hay autores con publicaciones</h3>
                <p>Esta lista se llena automáticamente al publicar recursos.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap mt-3">
                <table>
                    <thead><tr><th>Autor</th><th>Publicaciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($authors as $a): ?>
                        <tr>
                            <td><a href="<?= e(url('/autores/' . rawurlencode($a['full_name']))) ?>"><?= e($a['full_name']) ?></a></td>
                            <td><?= (int) $a['items_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
