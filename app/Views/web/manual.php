<?php

declare(strict_types=1);

$manual = [
    ['Consultar y buscar', 'Use la búsqueda principal o la <a href="' . url('/busqueda-avanzada') . '">búsqueda avanzada</a> para combinar título, autor, fecha, comunidad y tipo documental. Los filtros de la izquierda estrechan los resultados; el enlace «Limpiar filtros» restablece la búsqueda.'],
    ['Fichas de recursos', 'Cada recurso muestra metadatos, resumen, autores, palabras clave, archivos disponibles y nivel de acceso. El identificador público (por ejemplo, RI-2026-000001) es estable y no cambia con ediciones posteriores.'],
    ['Descargar archivos', 'Los archivos con icono de descarga se descargan directamente. Si el recurso está bajo embargo o en nivel «solo metadatos», la descarga no estará disponible hasta la fecha indicada.'],
    ['Citar', 'En la ficha, el botón «Citar» ofrece formatos APA, BibTeX y RIS listos para copiar. Incluya siempre la fecha de consulta y la URI del recurso.'],
    ['Depositar un documento', 'Con cuenta autorizada, ingrese al panel y use «Depósito» o «Recursos → Nuevo recurso». Complete título, resumen, idioma, fecha, tipo documental y adjunte el archivo. El sistema guarda borradores automáticamente; al terminar, envíe a revisión.'],
    ['Seguir su depósito', 'En «Recursos» verá el estado: borrador, en revisión, cambios solicitados, aprobado o publicado. Los comentarios del revisor aparecen en la ficha del recurso y como notificación.'],
    ['Correcciones', 'Los cambios en recursos publicados generan nueva versión con historial. Según la política institucional, los cambios sustantivos vuelven a revisión antes de republicarse.'],
    ['Accesibilidad y ayudas', 'El repositorio es navegable con teclado, tiene modo de alto contraste nativo del navegador y enlace «Saltar al contenido». Consulte también las <a href="' . url('/preguntas-frecuentes') . '">preguntas frecuentes</a> y las <a href="' . url('/politicas') . '">políticas</a>.'],
];
?>
<section class="section">
    <div class="container" style="max-width:820px">
        <h1>Manual de uso</h1>
        <p class="muted mt-1">Guía breve para consultar, citar y depositar en el repositorio institucional.</p>

        <div class="mt-3">
            <?php foreach ($manual as $i => [$t, $d]): ?>
                <details class="card mb-2"<?= $i === 0 ? ' open' : '' ?>>
                    <summary style="font-weight:700;cursor:pointer"><?= $i + 1 ?>. <?= e($t) ?></summary>
                    <p class="mt-2"><?= $d ?></p>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="card mt-3">
            <h3>¿Necesita más ayuda?</h3>
            <p class="mt-1">Consulte las <a href="<?= e(url('/preguntas-frecuentes')) ?>">preguntas frecuentes</a>, el <a href="<?= e(url('/acerca-de')) ?>">acerca del repositorio</a> o escriba al equipo desde el <a href="<?= e(url('/contacto')) ?>">formulario de contacto</a>.</p>
        </div>
    </div>
</section>
