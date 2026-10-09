<?php

declare(strict_types=1);

use App\Core\Csrf;

/** @var array<string,string>|null $errors */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="section" style="min-height:calc(100vh - 200px);display:flex;align-items:center;padding:40px 0;">
    <div class="container" style="max-width:480px;width:100%;">
        <div class="card" style="padding:40px 40px 36px;box-shadow:var(--shadow-lg);border-color:var(--border);">

            <!-- Logo / ícono de encabezado -->
            <div style="text-align:center;margin-bottom:28px;">
                <div style="width:58px;height:58px;background:linear-gradient(135deg,var(--brand-primary),#1a6ab0);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 6px 20px rgba(11,61,107,.25);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <h1 style="font-size:1.55rem;margin-bottom:6px;">Iniciar sesión</h1>
                <p class="muted" style="font-size:.9rem;margin:0;">Acceso para personal autorizado del repositorio.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert" style="margin-bottom:24px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <div>
                        <strong>Revise los campos marcados:</strong>
                        <ul class="mt-1" style="margin:0;padding-left:16px;"><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>">
                <?= Csrf::field() ?>

                <div class="field mb-2">
                    <label for="email" style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Correo electrónico
                    </label>
                    <input type="email" id="email" name="email" required maxlength="190"
                           autocomplete="username" value="<?= e($old['email'] ?? '') ?>"
                           placeholder="usuario@institucion.edu.pe"
                           <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field mb-3">
                    <label for="password" style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Contraseña
                    </label>
                    <input type="password" id="password" name="password" required maxlength="100"
                           autocomplete="current-password" placeholder="••••••••"
                           <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
                </div>

                <button class="btn btn-block btn-lg" type="submit" style="margin-bottom:20px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Entrar al sistema
                </button>
            </form>

            <div style="border-top:1px solid var(--border);padding-top:18px;text-align:center;display:flex;flex-direction:column;gap:10px;">
                <a href="<?= e(url('/recuperar-contrasena')) ?>" style="font-size:.88rem;display:inline-flex;align-items:center;justify-content:center;gap:5px;color:var(--muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    ¿Olvidó su contraseña?
                </a>
                <a href="<?= e(url('/')) ?>" style="font-size:.88rem;display:inline-flex;align-items:center;justify-content:center;gap:5px;color:var(--muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Volver al repositorio
                </a>
            </div>
        </div>
    </div>
</section>
