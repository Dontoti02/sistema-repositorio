<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,mixed> $inst */
/** @var array<string,string>|null $errors */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <h1>Contacto</h1>
        <p class="muted">
            Correo institucional:
            <?php if (!empty($inst['email'])): ?>
                <a href="mailto:<?= e($inst['email']) ?>"><?= e($inst['email']) ?></a>
            <?php else: ?>
                (no configurado)
            <?php endif; ?>
        </p>

        <div class="card mt-2">
            <h2 style="font-size:1.1rem">Formulario de consulta</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/contacto')) ?>">
                <?= Csrf::field() ?>
                <div class="field mb-2">
                    <label for="name">Nombre completo *</label>
                    <input type="text" id="name" name="name" required maxlength="190" value="<?= e($old['name'] ?? '') ?>">
                </div>
                <div class="field mb-2">
                    <label for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email" required maxlength="190" value="<?= e($old['email'] ?? '') ?>">
                </div>
                <div class="field mb-2">
                    <label for="message">Mensaje *</label>
                    <textarea id="message" name="message" required maxlength="4000" rows="5"><?= e($old['message'] ?? '') ?></textarea>
                </div>
                <button class="btn" type="submit">Enviar consulta</button>
            </form>
        </div>
    </div>
</section>
