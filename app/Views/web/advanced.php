<?php

declare(strict_types=1);

/**
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $communities
 * @var array<int,array<string,mixed>> $programs
 */
?>
<section class="section">
    <div class="container">
        <h1>Búsqueda avanzada</h1>
        <p class="muted">Combine los campos disponibles. Los resultados se muestran con todos los filtros aplicados.</p>

        <div class="card mt-3">
            <form method="get" action="<?= e(url('/buscar')) ?>">
                <div class="form-grid">
                    <div class="field field-full">
                        <label for="q">Palabras en título, resumen o metadatos</label>
                        <input type="search" id="q" name="q" maxlength="200" placeholder="Ej.: gestión de residuos sólidos">
                    </div>
                    <div class="field">
                        <label for="autor">Autor</label>
                        <input type="text" id="autor" name="autor" maxlength="190">
                    </div>
                    <div class="field">
                        <label for="palabra_clave">Palabra clave (vocabulario)</label>
                        <input type="text" id="palabra_clave" name="palabra_clave" maxlength="140" placeholder="slug de la palabra clave">
                    </div>
                    <div class="field">
                        <label for="tipo">Tipo documental</label>
                        <select id="tipo" name="tipo">
                            <option value="">Cualquiera</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= e($t['slug']) ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="comunidad">Comunidad</label>
                        <select id="comunidad" name="comunidad">
                            <option value="">Cualquiera</option>
                            <?php foreach ($communities as $c): ?>
                                <option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="programa">Programa</label>
                        <select id="programa" name="programa">
                            <option value="">Cualquiera</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= e($p['slug']) ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="idioma">Idioma</label>
                        <select id="idioma" name="idioma">
                            <option value="">Cualquiera</option>
                            <option value="es">Español</option>
                            <option value="en">Inglés</option>
                            <option value="qu">Quechua</option>
                            <option value="ay">Aimara</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="acceso">Nivel de acceso</label>
                        <select id="acceso" name="acceso">
                            <option value="">Cualquiera</option>
                            <option value="open">Acceso abierto</option>
                            <option value="metadata_only">Solo metadatos</option>
                            <option value="embargoed">Bajo embargo</option>
                            <option value="restricted">Restringido</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="anio_desde">Año desde</label>
                        <input type="number" id="anio_desde" name="anio_desde" min="1900" max="2100" placeholder="1900">
                    </div>
                    <div class="field">
                        <label for="anio_hasta">Año hasta</label>
                        <input type="number" id="anio_hasta" name="anio_hasta" min="1900" max="2100" placeholder="2100">
                    </div>
                    <div class="field">
                        <label for="orden">Ordenar por</label>
                        <select id="orden" name="orden">
                            <option value="relevancia">Relevancia</option>
                            <option value="fecha">Fecha (más recientes)</option>
                            <option value="titulo">Título (A-Z)</option>
                            <option value="consultas">Más consultados</option>
                        </select>
                    </div>
                </div>
                <div class="flex mt-3">
                    <button class="btn btn-lg" type="submit">Buscar</button>
                    <a class="btn btn-secondary" href="<?= e(url('/buscar')) ?>">Búsqueda simple</a>
                </div>
            </form>
        </div>
    </div>
</section>
