<?php

declare(strict_types=1);
/** @var string $content */
/** @var array<int,array{type:string,message:string}> $flashes */
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Instalación') ?> — Repositorio Institucional</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<header class="site-header">
    <div class="container">
        <span class="brand"><span class="brand-name">Asistente de instalación<span class="brand-short">Repositorio Institucional</span></span></span>
    </div>
</header>
<main id="contenido" class="container" style="max-width:860px;padding-top:34px;padding-bottom:60px">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Revise los campos marcados:</strong>
            <ul class="mt-1"><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
