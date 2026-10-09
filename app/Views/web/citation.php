<?php

declare(strict_types=1);

/**
 * @var array<string,mixed> $item
 * @var array{format:string,content:string,warnings:array<int,string>} $result
 * @var string $format
 */
?>
<section class="section">
    <div class="container" style="max-width:820px">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(url('/')) ?>">Inicio</a> ›
            <a href="<?= e(url('/recursos/' . $item['public_id'])) ?>"><?= e($item['title']) ?></a> ›
            <span>Citar</span>
        </nav>
        <h1>Citar este recurso — <?= e($result['format']) ?></h1>

        <?php if ($result['warnings'] !== []): ?>
            <div class="alert alert-warning">
                <strong>La cita puede estar incompleta:</strong>
                <ul class="mt-1"><?php foreach ($result['warnings'] as $w): ?><li><?= e($w) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <label class="visually-hidden" for="cite">Cita en formato <?= e($result['format']) ?></label>
            <textarea id="cite" readonly rows="7" class="small"><?= e($result['content']) ?></textarea>
            <div class="flex mt-2">
                <button class="btn" type="button" data-copy="#cite">Copiar cita</button>
                <a class="btn btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=apa')) ?>">APA</a>
                <a class="btn btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=bibtex')) ?>">BibTeX</a>
                <a class="btn btn-secondary" href="<?= e(url('/recursos/' . $item['public_id'] . '/cita?formato=ris')) ?>">RIS</a>
            </div>
        </div>
        <p class="mt-3 small muted">
            Con carácter público: las exportaciones solo incluyen campos de metadatos públicos.
            La copia usa el portapapeles del navegador; si su navegador lo bloquea, seleccione y copie manualmente.
        </p>
    </div>
</section>
