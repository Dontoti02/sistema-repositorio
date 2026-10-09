<?php

declare(strict_types=1);

/** @var array<string,mixed> $inst */
?>
<section class="section">
    <div class="container" style="max-width:860px">
        <h1>Políticas y privacidad</h1>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Política de conservación y preservación</h2>
            <p>Los archivos originales se conservan en almacenamiento privado con registro de integridad (SHA-256). Las versiones
            publicadas se mantienen con trazabilidad; los cambios sustantivos generan nuevas versiones sujetas a revisión.
            Se programan verificaciones periódicas de integridad con informe de alteraciones.</p>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Niveles de acceso</h2>
            <ul>
                <li><strong>Acceso abierto:</strong> metadatos y archivo disponibles públicamente.</li>
                <li><strong>Solo metadatos:</strong> los metadatos son públicos; el archivo no está disponible.</li>
                <li><strong>Embargo:</strong> los metadatos pueden ser públicos; el archivo se desbloquea en la fecha indicada.</li>
                <li><strong>Restringido:</strong> acceso limitado a personas o roles autorizados.</li>
                <li><strong>Privado:</strong> visible solo para el personal autorizado.</li>
            </ul>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Protección de datos personales</h2>
            <p>El tratamiento de datos personales se rige por la <strong>Ley N.º 29733</strong> y su Reglamento
            (D. S. N.º 016-2024-JUS). El repositorio aplica minimización de datos: no publica documentos de identidad,
            teléfonos ni correos personales de estudiantes, asesores o jurados salvo autorización expresa y necesidad
            acreditada. Las direcciones IP en registros de auditoría se almacenan enmascaradas.</p>
            <p>Para ejercer sus derechos de acceso, rectificación o supresión de datos personales, presente su solicitud
            a través de la página de <a href="<?= e(url('/contacto')) ?>">contacto</a>.</p>
        </div>

        <div class="card mt-2">
            <h2 style="font-size:1.15rem">Política de privacidad del sitio</h2>
            <p>No se instalan cookies de analítica ni servicios de terceros por defecto. La sesión técnica se usa únicamente
            para el acceso al panel. No se comparten datos con terceros.</p>
            <?php if (!empty($inst['privacy_policy'])): ?>
                <div class="mt-2" style="white-space:pre-wrap"><?= e($inst['privacy_policy']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
