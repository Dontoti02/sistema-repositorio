<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var string $token */
/** @var array<string,string>|null $errors */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="section">
    <div class="container" style="max-width:460px">
        <div class="card">
            <h1>Nueva contraseña</h1>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/recuperar')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="field mb-2">
                    <label for="password">Nueva contraseña (mínimo 10 caracteres)</label>
                    <input type="password" id="password" name="password" required minlength="10" maxlength="100" autocomplete="new-password">
                </div>
                <div class="field mb-2">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="10" maxlength="100" autocomplete="new-password">
                </div>
                <button class="btn btn-block" type="submit">Guardar contraseña</button>
            </form>
        </div>
    </div>
</section>
