<?php

declare(strict_types=1);

$faq = [
    ['¿Necesito una cuenta para consultar el repositorio?', 'No. Los recursos de acceso abierto se pueden buscar, consultar y descargar sin registro. Solo el personal autorizado necesita cuenta para depositar o revisar documentos.'],
    ['¿Quién puede depositar documentos?', 'Docentes, investigadores y personal autorizado según los roles asignados por la administración del instituto. Todo depósito se somete a revisión antes de publicarse.'],
    ['¿Por qué un recurso no está disponible para descarga?', 'Puede estar en nivel «solo metadatos», bajo embargo o con acceso restringido. En la ficha se indica el nivel de acceso y, si aplica, la fecha de levante del embargo.'],
    ['¿Cómo cito un recurso del repositorio?', 'En la ficha de cada recurso encontrará el botón «Citar» con formatos APA, BibTeX y RIS. El identificador estable de la ficha no cambia aunque se modifique el título.'],
    ['¿El repositorio registra DOI o Handle?', 'Solo si la institución configura un proveedor autorizado con credenciales válidas. Mientras tanto, cada recurso expone su URI local permanente y no se generan identificadores ficticios.'],
    ['¿Puedo corregir un error en un recurso publicado?', 'Sí. El personal autorizado puede editar el recurso; los cambios sustantivos generan una nueva versión con trazabilidad y, según la política, vuelven a revisión.'],
    ['¿Cómo se protegen mis datos personales?', 'El repositorio aplica minimización de datos conforme a la Ley N.º 29733 y su reglamento. No se publican documentos de identidad ni datos de contacto personales sin autorización.'],
];
?>
<section class="section">
    <div class="container" style="max-width:820px">
        <h1>Preguntas frecuentes</h1>
        <div class="mt-3">
            <?php foreach ($faq as $i => [$q, $a]): ?>
                <details class="card mb-2"<?= $i === 0 ? ' open' : '' ?>>
                    <summary style="font-weight:700;cursor:pointer"><?= e($q) ?></summary>
                    <p class="mt-2" style="white-space:pre-wrap"><?= e($a) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
        <p class="mt-3"><a class="btn" href="<?= e(url('/contacto')) ?>">Contactar al equipo del repositorio</a></p>
    </div>
</section>
