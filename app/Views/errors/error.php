<?php

declare(strict_types=1);
/** @var int $status */
/** @var string $message */
/** @var string|null $incident */

$titles = [
    400 => 'Solicitud incorrecta',
    401 => 'Sesión requerida',
    403 => 'Acceso denegado',
    404 => 'Página no encontrada',
    405 => 'Método no permitido',
    409 => 'Conflicto',
    419 => 'Sesión expirada',
    422 => 'Datos no válidos',
    429 => 'Demasiadas solicitudes',
    500 => 'Error interno',
];
$title = $titles[$status] ?? 'Error';
?>
<section class="section">
    <div class="container text-center" style="max-width:640px">
        <div class="card">
            <p class="stat-value" style="font-size:3rem;margin:0"><?= (int) $status ?></p>
            <h1><?= e($title) ?></h1>
            <p class="muted"><?= e($message) ?></p>
            <?php if (!empty($incident)): ?>
                <p class="small muted">Identificador de incidente: <code><?= e($incident) ?></code></p>
            <?php endif; ?>
            <div class="flex mt-3" style="justify-content:center">
                <a class="btn" href="<?= e(url('/')) ?>">Ir al inicio</a>
                <a class="btn btn-secondary" href="<?= e(url('/buscar')) ?>">Buscar en el repositorio</a>
            </div>
        </div>
    </div>
</section>
