<?php

declare(strict_types=1);

/**
 * @var array<string,int> $stats
 * @var array<int,array<string,mixed>> $perYear
 * @var array<int,array<string,mixed>> $perType
 */
?>
<section class="section">
    <div class="container">
        <h1>Estadísticas del repositorio</h1>
        <p class="muted">Indicadores públicos agregados. No se expone información de visitantes individuales.
        Las métricas no equivalen automáticamente a impacto científico.</p>

        <div class="grid grid-4 mt-3">
            <div class="stat"><span class="stat-value"><?= number_format($stats['items'], 0, ',', '.') ?></span><span class="stat-label">Recursos publicados</span></div>
            <div class="stat"><span class="stat-value"><?= number_format($stats['communities'], 0, ',', '.') ?></span><span class="stat-label">Comunidades</span></div>
            <div class="stat"><span class="stat-value"><?= number_format($stats['collections'], 0, ',', '.') ?></span><span class="stat-label">Colecciones</span></div>
            <div class="stat"><span class="stat-value"><?= number_format($stats['downloads'], 0, ',', '.') ?></span><span class="stat-label">Descargas</span></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div class="card">
                <h2 style="font-size:1.1rem">Publicaciones por año</h2>
                <?php if ($perYear === []): ?>
                    <p class="muted">Sin datos todavía.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Año</th><th>Recursos</th></tr></thead>
                            <tbody>
                            <?php foreach ($perYear as $row): ?>
                                <tr><td><?= (int) $row['year'] ?></td><td><?= (int) $row['total'] ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <h2 style="font-size:1.1rem">Recursos por tipo documental</h2>
                <?php if ($perType === []): ?>
                    <p class="muted">Sin datos todavía.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Tipo</th><th>Recursos</th></tr></thead>
                            <tbody>
                            <?php foreach ($perType as $row): ?>
                                <tr><td><?= e($row['type_name'] ?? 'Sin tipo') ?></td><td><?= (int) $row['total'] ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
