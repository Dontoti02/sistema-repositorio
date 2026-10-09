<?php

declare(strict_types=1);

/** @var array<string,mixed> $inst */
?>
<section class="section">
    <div class="container" style="max-width:860px">
        <h1>Acerca del repositorio</h1>
        <?php if (!empty($inst['description'])): ?>
            <p style="white-space:pre-wrap"><?= e($inst['description']) ?></p>
        <?php else: ?>
            <p class="muted">Este repositorio digital organiza, preserva y difunde la producción académica, científica, tecnológica e institucional de la institución.</p>
        <?php endif; ?>

        <div class="card mt-3">
            <h2 style="font-size:1.15rem">Política de depósito</h2>
            <?php if (!empty($inst['deposit_policy'])): ?>
                <p style="white-space:pre-wrap"><?= e($inst['deposit_policy']) ?></p>
            <?php else: ?>
                <p>El personal docente, investigador y administrativo autorizado puede depositar documentos mediante el panel del repositorio.
                Todo depósito pasa por un proceso de revisión editorial antes de su publicación. Los archivos se almacenan de forma
                privada hasta que la publicación sea aprobada.</p>
            <?php endif; ?>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Derechos de autor y licencias</h2>
            <p>Cada recurso indica su nivel de acceso y la licencia aplicable. Una licencia no sustituye la acreditación de
            titularidad ni el permiso de distribución del depositante. Los recursos de acceso abierto pueden copiarse y
            distribuirse citando la fuente, según la licencia indicada en la ficha.</p>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Equipo responsable</h2>
            <?php if (!empty($inst['coordinator_name'])): ?>
                <p><strong>Coordinación del repositorio:</strong> <?= e($inst['coordinator_name']) ?>
                <?php if (!empty($inst['coordinator_email'])): ?>
                    · <a href="mailto:<?= e($inst['coordinator_email']) ?>"><?= e($inst['coordinator_email']) ?></a>
                <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="muted">Consulte la página de <a href="<?= e(url('/contacto')) ?>">contacto</a> para comunicarse con el equipo del repositorio.</p>
            <?php endif; ?>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Interoperabilidad</h2>
            <p>El repositorio expone metadatos en <strong>Dublin Core</strong> y un proveedor <strong>OAI-PMH 2.0</strong> para
            cosecha por terceros (<code><?= e(url('/oai')) ?></code>). La disponibilidad de estos servicios no implica
            inscripción ni certificación automática ante CONCYTEC ni otra entidad.</p>
        </div>

        <p class="mt-3"><a class="btn" href="<?= e(url('/preguntas-frecuentes')) ?>">Preguntas frecuentes</a>
        <a class="btn btn-secondary" href="<?= e(url('/contacto')) ?>">Contacto</a></p>
    </div>
</section>
