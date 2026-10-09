<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,string>|null $errors */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="section">
    <div class="container" style="max-width:460px">
        <div class="card">
            <h1>Recuperar contraseña</h1>
            <p class="muted">Escriba su correo institucional. Si existe en el sistema, recibirá un enlace de un solo uso válido por 60 minutos.</p>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert"><?php foreach ($errors as $msg): ?><p><?= e($msg) ?></p><?php endforeach; ?></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/recuperar-contrasena')) ?>">
                <?= Csrf::field() ?>
                <div class="field mb-2">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" required maxlength="190" value="<?= e($old['email'] ?? '') ?>">
                </div>
                <button class="btn btn-block" type="submit">Enviar enlace</button>
            </form>
            <p class="mt-3 small text-center"><a href="<?= e(url('/login')) ?>">Volver a iniciar sesión</a></p>
        </div>
    </div>
</section>
