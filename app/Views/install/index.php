<?php

declare(strict_types=1);
/** @var array<string,array{ok:bool,detail:string}> $requirements */
/** @var bool $dbStep */

use App\Core\Csrf;

$allOk = true;
foreach ($requirements as $check) {
    if (!$check['ok']) {
        $allOk = false;
        break;
    }
}
?>
<h1>Instalación del repositorio institucional</h1>
<p class="muted">Asistente de una sola ejecución. Al finalizar, este instalador quedará bloqueado de forma permanente.</p>

<div class="card mb-3">
    <h2>1. Requisitos del entorno</h2>
    <?php if ($allOk): ?>
        <div class="alert alert-success">Todos los requisitos están satisfechos.</div>
    <?php else: ?>
        <div class="alert alert-danger">Hay requisitos sin cumplir. Corríjalos antes de continuar.</div>
    <?php endif; ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Requisito</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($requirements as $name => $check): ?>
                <tr>
                    <td><?= e($name) ?></td>
                    <td>
                        <?php if ($check['ok']): ?>
                            <span class="badge badge-published">Satisfecho</span>
                        <?php else: ?>
                            <span class="badge badge-changes_requested">Pendiente</span>
                        <?php endif; ?>
                        <span class="small muted"><?= e($check['detail']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($allOk): ?>
<div class="card mb-3">
    <h2>2. Conexión a base de datos y sitio</h2>
    <form method="post" action="<?= e(url('/instalar/configurar')) ?>">
        <?= Csrf::field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="app_name">Nombre del repositorio</label>
                <input type="text" id="app_name" name="app_name" value="Repositorio Institucional" required maxlength="120">
            </div>
            <div class="field">
                <label for="app_url">URL pública (sin barra final)</label>
                <input type="url" id="app_url" name="app_url" value="http://localhost:8080" required maxlength="255">
                <span class="help">Ej.: https://repositorio.instituto.edu.pe</span>
            </div>
            <div class="field">
                <label for="db_host">Servidor MySQL</label>
                <input type="text" id="db_host" name="db_host" value="127.0.0.1" required>
            </div>
            <div class="field">
                <label for="db_port">Puerto</label>
                <input type="number" id="db_port" name="db_port" value="3306" required min="1" max="65535">
            </div>
            <div class="field">
                <label for="db_database">Base de datos</label>
                <input type="text" id="db_database" name="db_database" value="repositorio_institucional" required maxlength="64">
                <span class="help">Se creará si no existe.</span>
            </div>
            <div class="field">
                <label for="db_username">Usuario</label>
                <input type="text" id="db_username" name="db_username" value="root" required maxlength="100">
            </div>
            <div class="field">
                <label for="db_password">Contraseña</label>
                <input type="password" id="db_password" name="db_password" maxlength="190" autocomplete="new-password">
            </div>
        </div>
        <button class="btn mt-3" type="submit"<?= $dbStep ? '' : '' ?>>Configurar base de datos y ejecutar migraciones</button>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($steps['db'])): ?>
<div class="card">
    <h2>3. Institución y cuenta administradora</h2>
    <div class="alert alert-info">
        La base de datos está configurada (<?= (int) ($steps['migrations'] ?? 0) ?> migraciones aplicadas).
        Complete la ficha institucional y la primera cuenta administradora.
    </div>
    <form method="post" action="<?= e(url('/instalar/finalizar')) ?>">
        <?= Csrf::field() ?>
        <fieldset>
            <legend>Ficha institucional</legend>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Nombre oficial *</label>
                    <input type="text" id="name" name="name" required maxlength="200" placeholder="Instituto Tecnológico Público «...»">
                </div>
                <div class="field">
                    <label for="short_name">Nombre corto</label>
                    <input type="text" id="short_name" name="short_name" maxlength="120">
                </div>
                <div class="field">
                    <label for="institution_type">Tipo de institución</label>
                    <select id="institution_type" name="institution_type">
                        <option value="">Seleccionar…</option>
                        <option value="IESTP">IESTP — Instituto de Educación Superior Tecnológico Público</option>
                        <option value="IEST">IEST — Instituto de Educación Superior</option>
                        <option value="EEST">EEST — Escuela de Educación Superior Tecnológica</option>
                        <option value="IESP">IESP — Instituto de Educación Superior Pedagógico Público</option>
                        <option value="EESP">EESP — Escuela de Educación Superior Pedagógica</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>
                <div class="field">
                    <label for="slug">Identificador web *</label>
                    <input type="text" id="slug" name="slug" required maxlength="120" pattern="[a-z0-9\-]+" placeholder="instituto-ejemplo">
                    <span class="help">Minúsculas, números y guiones. No se puede cambiar después.</span>
                </div>
                <div class="field">
                    <label for="email">Correo institucional *</label>
                    <input type="email" id="email" name="email" required maxlength="190">
                </div>
                <div class="field">
                    <label for="website">Sitio web</label>
                    <input type="url" id="website" name="website" maxlength="255" placeholder="https://www.instituto.edu.pe">
                </div>
                <div class="field field-full">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" maxlength="4000" rows="3"></textarea>
                </div>
            </div>
        </fieldset>

        <fieldset class="mt-3">
            <legend>Cuenta administradora</legend>
            <div class="form-grid">
                <div class="field">
                    <label for="admin_name">Nombre completo *</label>
                    <input type="text" id="admin_name" name="admin_name" required maxlength="190">
                </div>
                <div class="field">
                    <label for="admin_email">Correo *</label>
                    <input type="email" id="admin_email" name="admin_email" required maxlength="190">
                </div>
                <div class="field">
                    <label for="admin_password">Contraseña * (mínimo 10 caracteres)</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="10" maxlength="100" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="admin_password_confirmation">Confirmar contraseña *</label>
                    <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required minlength="10" maxlength="100" autocomplete="new-password">
                </div>
            </div>
        </fieldset>

        <button class="btn btn-success mt-3" type="submit">Finalizar instalación y bloquear instalador</button>
    </form>
</div>
<?php endif; ?>
