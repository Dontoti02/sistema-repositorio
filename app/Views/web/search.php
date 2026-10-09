<?php

declare(strict_types=1);

use App\Core\View;

/**
 * @var array<string,mixed> $filters
 * @var array{rows:array<int,array<string,mixed>>,total:int} $result
 * @var int $page
 * @var int $perPage
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $communities
 * @var array<int,array<string,mixed>> $programs
 * @var array<int,array<string,mixed>> $languages
 * @var array<int,array<string,mixed>> $years
 */

$order = $filters['orden'] ?? 'relevancia';
$baseUrl = '/buscar';
?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h1><?= !empty($filters['q']) ? 'Resultados para «' . e($filters['q']) . '»' : 'Buscar en el repositorio' ?></h1>
            <a href="<?= e(url('/busqueda-avanzada')) ?>">Búsqueda avanzada →</a>
        </div>

        <form class="mb-3" method="get" action="<?= e(url($baseUrl)) ?>" role="search">
            <div class="flex">
                <label class="visually-hidden" for="q">Término de búsqueda</label>
                <input type="search" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Título, resumen o palabra…" maxlength="200" style="flex:1 1 320px">
                <label class="visually-hidden" for="orden">Ordenar</label>
                <select id="orden" name="orden">
                    <?php foreach (['relevancia' => 'Más relevantes', 'fecha' => 'Más recientes', 'titulo' => 'Título (A-Z)', 'consultas' => 'Más consultados'] as $val => $lbl): ?>
                        <option value="<?= $val ?>"<?= $order === $val ? ' selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Buscar</button>
            </div>
            <?php foreach ($filters as $key => $value): ?>
                <?php if (!in_array($key, ['q', 'orden'], true)): ?>
                    <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </form>

        <div class="search-layout">
            <aside class="filters" aria-label="Filtros de resultados">
                <h2>Filtrar</h2>
                <form method="get" action="<?= e(url($baseUrl)) ?>">
                    <input type="hidden" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>">
                    <input type="hidden" name="orden" value="<?= e($order) ?>">

                    <div class="field">
                        <label for="f-comunidad">Comunidad</label>
                        <select id="f-comunidad" name="comunidad">
                            <option value="">Todas</option>
                            <?php foreach ($communities as $c): ?>
                                <option value="<?= e($c['slug']) ?>"<?= ($filters['comunidad'] ?? '') === $c['slug'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-tipo">Tipo documental</label>
                        <select id="f-tipo" name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= e($t['slug']) ?>"<?= ($filters['tipo'] ?? '') === $t['slug'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-programa">Programa</label>
                        <select id="f-programa" name="programa">
                            <option value="">Todos</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= e($p['slug']) ?>"<?= ($filters['programa'] ?? '') === $p['slug'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-autor">Autor</label>
                        <input type="text" id="f-autor" name="autor" value="<?= e((string) ($filters['autor'] ?? '')) ?>" maxlength="190">
                    </div>
                    <div class="field">
                        <label for="f-anio-desde">Año desde</label>
                        <select id="f-anio-desde" name="anio_desde">
                            <option value="">Cualquiera</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= (int) $y['year'] ?>"<?= (string) ($filters['anio_desde'] ?? '') === (string) $y['year'] ? ' selected' : '' ?>><?= (int) $y['year'] ?> (<?= (int) $y['total'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-anio-hasta">Año hasta</label>
                        <select id="f-anio-hasta" name="anio_hasta">
                            <option value="">Cualquiera</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= (int) $y['year'] ?>"<?= (string) ($filters['anio_hasta'] ?? '') === (string) $y['year'] ? ' selected' : '' ?>><?= (int) $y['year'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-idioma">Idioma</label>
                        <select id="f-idioma" name="idioma">
                            <option value="">Todos</option>
                            <?php foreach ($languages as $l): ?>
                                <option value="<?= e($l['term_value']) ?>"<?= ($filters['idioma'] ?? '') === $l['term_value'] ? ' selected' : '' ?>><?= e($l['term']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-acceso">Nivel de acceso</label>
                        <select id="f-acceso" name="acceso">
                            <option value="">Todos</option>
                            <?php foreach (['open' => 'Acceso abierto', 'metadata_only' => 'Solo metadatos', 'embargoed' => 'Bajo embargo', 'restricted' => 'Restringido'] as $val => $lbl): ?>
                                <option value="<?= $val ?>"<?= ($filters['acceso'] ?? '') === $val ? ' selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex">
                        <button class="btn btn-sm" type="submit">Aplicar</button>
                        <a class="btn btn-sm btn-secondary" href="<?= e(url('/buscar')) ?>">Limpiar</a>
                    </div>
                </form>
            </aside>

            <div>
                <?php if ($result['total'] === 0): ?>
                    <div class="empty-state">
                        <h3>Sin resultados</h3>
                        <p>No se encontraron recursos con esos criterios. Pruebe con menos filtros o otras palabras.</p>
                        <a class="btn btn-secondary mt-2" href="<?= e(url('/buscar')) ?>">Nueva búsqueda</a>
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($result['rows'] as $item): ?>
                            <?= View::component('item_card', ['item' => $item]) ?>
                        <?php endforeach; ?>
                    </div>
                    <?= View::partial('components/pagination', [
                        'page' => $page,
                        'total' => $result['total'],
                        'perPage' => $perPage,
                        'filters' => $filters,
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
