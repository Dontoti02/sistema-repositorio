<?php

declare(strict_types=1);

use App\Core\Authorization;
use App\Core\Csrf;

/** @var array<int,array<string,mixed>> $communities */
/** @var array<int,array<string,mixed>> $collections */
/** @var string $q */
?>
<div class="flex-between">
    <div>
        <h1>Comunidades y colecciones</h1>
        <p class="muted">Estructura temática del repositorio. Las colecciones agrupan los recursos sujetos a políticas de depósito.</p>
    </div>
    <div class="flex">
        <a class="btn btn-secondary" href="<?= e(url('/admin/comunidades/colecciones/nueva')) ?>">Nueva colección</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/comunidades/nuevo')) ?>">Nueva comunidad</a>
    </div>
</div>

<div class="card mt-3">
    <form method="get" action="<?= e(url('/admin/comunidades')) ?>" class="flex">
        <label class="visually-hidden" for="q">Buscar comunidad</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar comunidad…" maxlength="190" style="flex:1">
        <button class="btn" type="submit">Buscar</button>
    </form>
</div>

<h2 class="mt-3" style="font-size:1.1rem">Comunidades (<?= count($communities) ?>)</h2>
<?php if ($communities === []): ?>
    <div class="empty-state">
        <h3>Sin comunidades</h3>
        <p>Cree la primera comunidad para empezar a organizar el repositorio.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Colecciones</th><th>Subcomunidades</th><th>Visibilidad</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($communities as $c): ?>
                <tr>
                    <td><strong><?= e($c['name']) ?></strong><br><span class="small muted">/comunidades/<?= e($c['slug']) ?></span></td>
                    <td><?= (int) $c['collections_count'] ?></td>
                    <td><?= (int) $c['children_count'] ?></td>
                    <td>
                        <?php if ((int) $c['is_public'] === 1): ?>
                            <span class="badge badge-published">Pública</span>
                        <?php else: ?>
                            <span class="badge badge-status">Oculta</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex">
                            <a class="btn btn-sm" href="<?= e(url('/admin/comunidades/' . $c['id'] . '/editar')) ?>">Editar</a>
                            <?php if ((int) $c['collections_count'] === 0 && Authorization::can('communities.manage')): ?>
                                <form method="post" action="<?= e(url('/admin/comunidades/' . $c['id'] . '/eliminar')) ?>"
                                      data-confirm="¿Eliminar la comunidad «<?= e($c['name']) ?>»?">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-secondary" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2 class="mt-4" style="font-size:1.1rem">Colecciones (<?= count($collections) ?>)</h2>
<?php if ($collections === []): ?>
    <div class="empty-state">
        <h3>Sin colecciones</h3>
        <p>Las colecciones se crean dentro de una comunidad.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Colección</th><th>Comunidad</th><th>Recursos</th><th>Depósito</th><th>Visibilidad</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($collections as $col): ?>
                <tr>
                    <td><strong><?= e($col['name']) ?></strong><br><span class="small muted">/colecciones/<?= e($col['slug']) ?></span></td>
                    <td><?= e($col['community_name']) ?></td>
                    <td><?= (int) $col['items_count'] ?></td>
                    <td>
                        <?php if ((int) $col['deposit_enabled'] === 1): ?>
                            <span class="badge badge-published">Habilitado</span>
                        <?php else: ?>
                            <span class="badge badge-status">Deshabilitado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $col['is_public'] === 1): ?>
                            <span class="badge badge-published">Pública</span>
                        <?php else: ?>
                            <span class="badge badge-status">Oculta</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex">
                            <a class="btn btn-sm" href="<?= e(url('/admin/comunidades/colecciones/' . $col['id'] . '/editar')) ?>">Editar</a>
                            <?php if ((int) $col['items_count'] === 0 && Authorization::can('collections.manage')): ?>
                                <form method="post" action="<?= e(url('/admin/comunidades/colecciones/' . $col['id'] . '/eliminar')) ?>"
                                      data-confirm="¿Eliminar la colección «<?= e($col['name']) ?>»?">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-secondary" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
