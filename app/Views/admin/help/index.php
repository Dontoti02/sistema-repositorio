<?php

declare(strict_types=1);

use App\Core\Authorization;

/** @var array<string,string> $sections */
/** @var array<string,string> $tips */
/** @var string $current */
/** @var string|null $currentTitle */
?>
<h1>Ayuda y documentación</h1>
<p class="muted">Guías del panel, manual por rol, preguntas frecuentes y políticas de soporte.</p>

<?php if ($currentTitle !== null): ?>
    <div class="card mt-3" id="contextual">
        <h3>Ayuda de esta sección: <?= e($currentTitle) ?></h3>
        <p class="mt-1"><?= e($tips[$current]) ?></p>
        <p class="small muted">Abra esta página desde el botón «Ayuda» de la barra superior para llegar directo a su sección.</p>
    </div>
<?php endif; ?>

<div class="card mt-3" id="primeros-pasos">
    <h3>Primeros pasos</h3>
    <ol class="mt-1">
        <li>Revise su perfil y contraseña en la barra superior; consulte los avisos en «Notificaciones».</li>
        <li>Configure la ficha institucional, colores y licencias en «Institución».</li>
        <li>Defina comunidades, colecciones, tipos documentales, programas y el perfil de metadatos.</li>
        <li>Cree usuarios y asigne roles: administrador, catalogador, revisor o depositante.</li>
        <li>Deposite el primer recurso desde «Recursos» y póngalo en revisión.</li>
        <li>Consulte «Calidad» para detectar metadatos incompletos y «Reportes» para el seguimiento.</li>
    </ol>
</div>

<div class="grid grid-2 mt-3">
    <div class="card">
        <h3>Índice de ayuda</h3>
        <ul class="mt-1">
            <?php foreach ($sections as $slug => $title): ?>
                <li><a href="<?= e(url('/admin/ayuda?seccion=' . rawurlencode($slug))) ?>"><?= e($title) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card" id="soporte">
        <h3>Políticas de soporte</h3>
        <ul class="mt-1">
            <li><strong>Canal:</strong> mesa de ayuda institucional (<a href="mailto:soporte@repositorio.gob.pe">soporte@repositorio.gob.pe</a>) y formulario de <a href="<?= e(url('/contacto')) ?>">contacto</a>.</li>
            <li><strong>Horario:</strong> lunes a viernes de 8:00 a 18:00 (hora de Lima, GMT-5).</li>
            <li><strong>Tiempo de respuesta:</strong> 1 día hábil para incidencias críticas; 3 días hábil para consultas generales.</li>
            <li><strong>Incluye:</strong> ayuda con uso del panel, dudas de metadatos y reporte de errores.</li>
            <li><strong>No incluye:</strong> desarrollo de funcionalidades a medida ni carga masiva de contenido por terceros.</li>
            <li><strong>Respaldos:</strong> diarios automáticos; los incidentes se atienden según prioridad registrada en «Sistema».</li>
        </ul>
    </div>
</div>

<h2 class="mt-3">Manual por rol</h2>
<div class="grid grid-2 mt-2">
    <details class="card" open>
        <summary><strong>Administrador</strong></summary>
        <ol class="mt-2">
            <li>Gestione usuarios, roles y permisos.</li>
            <li>Mantenga actualizada la ficha institucional, licencias y embargos.</li>
            <li>Configure catálogos: comunidades, colecciones, tipos documentales y programas.</li>
            <li>Administre el diccionario de metadatos y los perfiles de captura.</li>
            <li>Supervise calidad, reportes, auditoría y respaldos del sistema.</li>
            <li>Use importación/exportación para cargas masivas con prevalidación.</li>
        </ol>
    </details>
    <details class="card">
        <summary><strong>Catalogador</strong></summary>
        <ol class="mt-2">
            <li>Busque y edite recursos en «Recursos».</li>
            <li>Complete metadatos según el perfil de captura vigente.</li>
            <li>Adjunte archivos principales y verifique el análisis antivirus.</li>
            <li>Revise la puntuación de «Calidad» antes de enviar a revisión.</li>
            <li>Prepare exportaciones para intercambio con otras instituciones.</li>
        </ol>
    </details>
    <details class="card">
        <summary><strong>Revisor</strong></summary>
        <ol class="mt-2">
            <li>Abra «Revisión» y filtre la bandeja de asignados.</li>
            <li>Verifique metadatos, archivos y derechos de autor.</li>
            <li>Deje comentarios visibles para el depositante.</li>
            <li>Decida: solicitar cambios, aprobar o rechazar con justificación.</li>
            <li>Seguimiento del estado en «Reportes».</li>
        </ol>
    </details>
    <details class="card">
        <summary><strong>Depositante</strong></summary>
        <ol class="mt-2">
            <li>Cree un recurso desde «Depósito» o «Recursos».</li>
            <li>Complete los campos obligatorios del perfil; el sistema guarda el borrador.</li>
            <li>Adjunte el archivo y envíe a revisión.</li>
            <li>Responda los comentarios solicitados y vuelva a enviar.</li>
            <li>Consulte el estado: borrador, en revisión, cambios, aprobado o publicado.</li>
        </ol>
    </details>
</div>

<h2 class="mt-3">Preguntas frecuentes</h2>
<div class="mt-2">
    <details class="card">
        <summary>¿Cómo recupero mi contraseña?</summary>
        <p class="mt-2">En la página de inicio de sesión use «Recuperar contraseña»; recibirá un enlace temporal por correo válido por una hora.</p>
    </details>
    <details class="card">
        <summary>¿Por qué no veo alguna sección del menú?</summary>
        <p class="mt-2">Cada sección requiere un permiso. Pida a un administrador que revise su rol en «Roles y permisos».</p>
    </details>
    <details class="card">
        <summary>¿Cómo corrijo metadatos de muchos recursos a la vez?</summary>
        <p class="mt-2">Use «Importar y exportar»: exporte, corrija en hoja de cálculo y reimporte el CSV con prevalidación fila por fila.</p>
    </details>
    <details class="card">
        <summary>¿Qué significan los estados del flujo editorial?</summary>
        <p class="mt-2">Borrador → En revisión → (Cambios solicitados | Aprobado) → Publicado; además Rechazado, Retirado y Archivado.</p>
    </details>
    <details class="card">
        <summary>¿Cómo activo un embargo de acceso?</summary>
        <p class="mt-2">En la ficha del recurso indique la fecha de embargo; la liberación es automática al vencer y queda registrada en auditoría.</p>
    </details>
    <p class="mt-2">Consulte también la <a href="<?= e(url('/preguntas-frecuentes')) ?>">pregunta frecuentes públicas</a> y el <a href="<?= e(url('/manual')) ?>">manual de uso</a>.</p>
</div>
