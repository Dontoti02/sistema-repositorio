<?php

declare(strict_types=1);

/**
 * Preferencias de notificación por tipo: bandeja interna y correo (6.15).
 *
 * @var array<string,array{in_app:bool,email:bool}> $prefs
 * @var array<string,string> $types
 */
$defaultEmail = ['item.assigned', 'item.changes_requested', 'item.rejected', 'item.published'];
?>
<div>
    <h1>Preferencias de notificación</h1>
    <p class="muted">Elija cómo recibe cada aviso: en la bandeja interna y, opcionalmente, por correo. Los cambios solo afectan a su cuenta.</p>
</div>

<form method="post" action="<?= e(url('/admin/notificaciones/preferencias')) ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="card mt-3">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tipo de aviso</th>
                        <th style="width:140px">Bandeja interna</th>
                        <th style="width:140px">Correo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($types as $type => $label): ?>
                    <?php
                    $current = $prefs[$type] ?? ['in_app' => true, 'email' => in_array($type, $defaultEmail, true)];
                    ?>
                    <tr>
                        <td class="small">
                            <?= e($label) ?><br>
                            <code class="muted"><?= e($type) ?></code>
                            <?php if (!$current['in_app'] && !$current['email']): ?>
                                <span class="badge badge-status">silenciado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="small">
                                <input type="hidden" name="in_app[<?= e($type) ?>]" value="0">
                                <input type="checkbox" name="in_app[<?= e($type) ?>]" value="1"<?= $current['in_app'] ? ' checked' : '' ?>>
                                Sí
                            </label>
                        </td>
                        <td>
                            <label class="small">
                                <input type="hidden" name="email[<?= e($type) ?>]" value="0">
                                <input type="checkbox" name="email[<?= e($type) ?>]" value="1"<?= $current['email'] ? ' checked' : '' ?>>
                                Sí
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Guardar preferencias</button>
            <a class="btn btn-secondary" href="<?= e(url('/admin/notificaciones')) ?>">Volver a la bandeja</a>
        </div>
    </div>
</form>
