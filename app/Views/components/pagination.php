<?php

declare(strict_types=1);

/**
 * Paginación con conservación de parámetros en la URL.
 *
 * @var int $page
 * @var int $total
 * @var int $perPage
 * @var array<string,mixed> $filters
 */
$page = (int) ($page ?? 1);
$perPage = max(1, (int) ($perPage ?? 12));
$total = (int) ($total ?? 0);
$filters = $filters ?? [];
$label = $label ?? 'registro(s)';
$pages = (int) max(1, ceil($total / $perPage));
if ($pages <= 1) {
    return;
}
$base = $_SERVER['REQUEST_URI'] ?? '/';

$link = static function (int $p) use ($base): string {
    $parts = parse_url($base) ?? [];
    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $query['pagina'] = $p;
    $path = $parts['path'] ?? '/';
    return $path . '?' . http_build_query($query);
};

$from = max(1, $page - 2);
$to = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="Paginación de resultados">
    <?php if ($page > 1): ?>
        <a href="<?= e($link($page - 1)) ?>" rel="prev">Anterior</a>
    <?php endif; ?>
    <?php if ($from > 1): ?>
        <a href="<?= e($link(1)) ?>">1</a>
        <?php if ($from > 2): ?><span aria-hidden="true">…</span><?php endif; ?>
    <?php endif; ?>
    <?php for ($p = $from; $p <= $to; $p++): ?>
        <?php if ($p === $page): ?>
            <span class="current" aria-current="page"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= e($link($p)) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($to < $pages): ?>
        <?php if ($to < $pages - 1): ?><span aria-hidden="true">…</span><?php endif; ?>
        <a href="<?= e($link($pages)) ?>"><?= $pages ?></a>
    <?php endif; ?>
    <?php if ($page < $pages): ?>
        <a href="<?= e($link($page + 1)) ?>" rel="next">Siguiente</a>
    <?php endif; ?>
</nav>
<p class="text-center small muted mt-1"><?= number_format($total, 0, ',', '.') ?> <?= e($label) ?> — página <?= $page ?> de <?= $pages ?></p>
