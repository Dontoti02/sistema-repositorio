<?php

declare(strict_types=1);

/**
 * Seeder idempotente de catálogos globales:
 * permisos, licencias, campos de metadatos y vocabulario de idiomas.
 *
 * Uso: php bin/console db:seed --class=BaseCatalogsSeeder
 */

use App\Core\Database;

return static function (): string {
    $pdo = Database::connection();

    // 1) Permisos.
    $perms = require base_path('config/permissions.php');
    $existing = [];
    foreach (Database::select('SELECT id, permission_key FROM permissions') as $row) {
        $existing[$row['permission_key']] = (int) $row['id'];
    }
    $inserted = 0;
    $stmt = $pdo->prepare('INSERT INTO permissions (permission_key, label, grp) VALUES (?, ?, ?)');
    foreach ($perms as $key => $meta) {
        if (isset($existing[$key])) {
            $pdo->prepare('UPDATE permissions SET label = ?, grp = ? WHERE id = ?')
                ->execute([$meta['label'], $meta['group'], $existing[$key]]);
            continue;
        }
        $stmt->execute([$key, $meta['label'], $meta['group']]);
        $inserted++;
    }

    // 2) Licencias globales.
    $licenses = [
        ['CC-BY-4.0', 'Creative Commons Atribución 4.0 Internacional', 'https://creativecommons.org/licenses/by/4.0/deed.es', 1, 0, 1],
        ['CC-BY-NC-4.0', 'Creative Commons Atribución-NoComercial 4.0 Internacional', 'https://creativecommons.org/licenses/by-nc/4.0/deed.es', 1, 0, 2],
        ['CC-BY-NC-ND-4.0', 'Creative Commons Atribución-NoComercial-SinDerivadas 4.0 Internacional', 'https://creativecommons.org/licenses/by-nc-nd/4.0/deed.es', 1, 0, 3],
        ['CC-BY-NC-SA-4.0', 'Creative Commons Atribución-NoComercial-CompartirIgual 4.0 Internacional', 'https://creativecommons.org/licenses/by-nc-sa/4.0/deed.es', 1, 0, 4],
        ['CC-BY-SA-4.0', 'Creative Commons Atribución-CompartirIgual 4.0 Internacional', 'https://creativecommons.org/licenses/by-sa/4.0/deed.es', 1, 0, 5],
        ['ALL-RIGHTS', 'Derechos reservados', '', 0, 1, 6],
        ['PUBLIC-DOMAIN', 'Dominio público', 'https://creativecommons.org/publicdomain/zero/1.0/deed.es', 0, 0, 7],
    ];
    $stmt = $pdo->prepare('INSERT INTO licenses (institution_id, code, name, url, description, requires_attribution, is_default, is_active, sort_order)
        VALUES (NULL, ?, ?, ?, ?, ?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE name = VALUES(name), url = VALUES(url)');
    foreach ($licenses as [$code, $name, $url, $attr, $default, $order]) {
        $stmt->execute([$code, $name, $url, 'Licencia documentada para publicación académica.', $attr, $default, $order]);
    }

    // 3) Vocabulario de idiomas.
    $pdo->prepare('INSERT INTO controlled_vocabularies (institution_id, slug, name, description, version)
        VALUES (NULL, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)')
        ->execute(['languages', 'Idiomas', 'Vocabulario controlado de idiomas', '1.0']);
    $vocabId = (int) Database::selectOne("SELECT id FROM controlled_vocabularies WHERE slug = 'languages' AND institution_id IS NULL")['id'];
    $languages = [
        ['es', 'Español'], ['en', 'Inglés'], ['qu', 'Quechua'], ['ay', 'Aimara'],
        ['pt', 'Portugués'], ['fr', 'Francés'], ['de', 'Alemán'], ['it', 'Italiano'],
    ];
    $stmt = $pdo->prepare('INSERT INTO vocabulary_terms (vocabulary_id, term, term_value, sort_order)
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE term = VALUES(term)');
    foreach ($languages as $i => [$value, $term]) {
        $stmt->execute([$vocabId, $term, $value, $i]);
    }

    // 4) Diccionario de campos de metadatos (modelo canónico + mapeos DC).
    $langVocab = $vocabId;
    $fields = [
        // key, label, datatype, cardinality, validation, vocabulary, public, sort, dc mapping
        ['title', 'Título', 'text', 'required-single', 'max:500', null, 1, 1, 'title'],
        ['alternative_title', 'Título alternativo', 'text', 'multi', 'max:500', null, 1, 2, 'alternative'],
        ['abstract', 'Resumen', 'textarea', 'single', 'max:5000', null, 1, 3, 'description'],
        ['language', 'Idioma', 'select', 'single', 'in:es,en,qu,ay,pt,fr,de,it', $langVocab, 1, 4, 'language'],
        ['date_issued', 'Fecha de publicación', 'date', 'single', 'date', null, 1, 5, 'date'],
        ['publisher', 'Editorial / entidad publicadora', 'text', 'single', 'max:190', null, 1, 6, 'publisher'],
        ['contributors', 'Otros contribuyentes (asesores, jurados, editores)', 'text', 'multi', 'max:190', null, 1, 7, 'contributor'],
        ['subject', 'Materias / palabras clave', 'text', 'multi', 'max:140', null, 1, 8, 'subject'],
        ['genre', 'Tipo de documento', 'text', 'single', 'max:140', null, 1, 9, 'type'],
        ['format', 'Formato', 'text', 'single', 'max:80', null, 1, 10, 'format'],
        ['extent', 'Extensión (n° de páginas, duración)', 'text', 'single', 'max:80', null, 1, 11, 'extent'],
        ['doi', 'DOI', 'text', 'single', 'max:100', null, 1, 12, 'identifier'],
        ['isbn', 'ISBN', 'text', 'single', 'max:40', null, 1, 13, 'identifier'],
        ['issn', 'ISSN', 'text', 'single', 'max:40', null, 1, 14, 'identifier'],
        ['local_uri', 'URI local permanente', 'url', 'single', 'url', null, 1, 15, 'identifier'],
        ['degree_name', 'Grado o título que conduce (si corresponde)', 'text', 'single', 'max:140', null, 1, 16, 'title'],
        ['institution_granting', 'Institución que otorga el grado (si corresponde)', 'text', 'single', 'max:190', null, 1, 17, 'publisher'],
        ['sponsor', 'Patrocinador / financiador', 'text', 'multi', 'max:190', null, 1, 18, 'sponsor'],
        ['source', 'Fuente / publicación de origen', 'text', 'single', 'max:255', null, 1, 19, 'source'],
        ['relation', 'Relación con otros recursos', 'text', 'multi', 'max:255', null, 1, 20, 'relation'],
        ['spatial', 'Ámbito geográfico', 'text', 'single', 'max:140', null, 1, 21, 'spatial'],
        ['notes', 'Notas', 'textarea', 'single', 'max:2000', null, 1, 22, 'description'],
        ['rights_holder', 'Titular de los derechos', 'text', 'single', 'max:190', null, 1, 23, 'rights'],
        ['research_line', 'Línea de investigación', 'text', 'single', 'max:190', null, 1, 24, 'subject'],
        ['internal_review_notes', 'Notas internas de revisión', 'textarea', 'single', 'max:4000', null, 0, 90, null],
        ['internal_dni_reference', 'Referencia interna de documento de identidad', 'text', 'single', 'max:30', null, 0, 91, null],
    ];
    $stmt = $pdo->prepare('INSERT INTO metadata_fields
        (field_key, label, datatype, cardinality, validation_rule, vocabulary_id, is_public, sort_order, namespace, help_text)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE label = VALUES(label), datatype = VALUES(datatype),
            cardinality = VALUES(cardinality), validation_rule = VALUES(validation_rule),
            vocabulary_id = VALUES(vocabulary_id), is_public = VALUES(is_public), sort_order = VALUES(sort_order)');
    $mapStmt = $pdo->prepare('INSERT INTO metadata_mappings (field_id, standard, target_element)
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE target_element = VALUES(target_element)');
    $help = 'Campo del perfil de metadatos. Consulte la ayuda contextual para completarlo.';
    foreach ($fields as [$key, $label, $type, $card, $validation, $vocab, $public, $sort, $dc]) {
        $cardinality = str_contains($card, 'multi') ? 'multi' : 'single';
        $stmt->execute([$key, $label, $type, $cardinality, $validation, $vocab, $public, $sort, 'local', $help]);
        if ($dc !== null) {
            $field = Database::selectOne('SELECT id FROM metadata_fields WHERE field_key = ?', [$key]);
            if ($field !== null) {
                $mapStmt->execute([(int) $field['id'], 'dc', $dc]);
            }
        }
    }

    return 'Permisos: ' . count($perms) . ', licencias: ' . count($licenses) . ', campos de metadatos: ' . count($fields);
};
