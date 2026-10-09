<?php

declare(strict_types=1);

/**
 * Seeder idempotente por institución: roles y permisos, tipos documentales,
 * flujo de revisión por defecto y perfil de metadatos inicial.
 *
 * Uso: php bin/console db:seed --class=InstitutionSeeder  (requiere instituto activo)
 */

use App\Core\Database;
use App\Core\TenantContext;

return static function (?int $institutionId = null): string {
    $institutionId ??= TenantContext::institutionId();
    $pdo = Database::connection();

    $permIds = [];
    foreach (Database::select('SELECT id, permission_key FROM permissions') as $row) {
        $permIds[$row['permission_key']] = (int) $row['id'];
    }

    // 1) Roles semilla (agrupaciones de permisos).
    $all = array_keys($permIds);
    $reviewish = ['dashboard.view', 'items.read_all', 'items.review', 'items.approve', 'reports.view', 'audit.view'];
    $roles = [
        ['institution_admin', 'Administrador institucional', 'Administra usuarios, catálogos, branding y políticas del instituto.', $all],
        ['repository_manager', 'Responsable del repositorio', 'Gestión editorial: flujos, metadatos, publicaciones, embargos e integridad.',
            ['dashboard.view', 'institution.read', 'users.view', 'programs.manage', 'communities.manage', 'collections.manage',
                'items.read_all', 'items.edit_all', 'items.submit', 'items.review', 'items.approve', 'items.publish', 'items.unpublish',
                'items.archive', 'items.delete', 'items.restore', 'files.upload', 'files.download_restricted', 'files.replace',
                'metadata.manage', 'embargo.manage', 'imports.manage', 'exports.manage', 'reports.view', 'audit.view']],
        ['cataloger', 'Bibliotecario / catalogador', 'Revisa, corrige y normaliza metadatos de las colecciones asignadas.',
            ['dashboard.view', 'communities.manage', 'collections.manage', 'items.read_all', 'items.edit_all', 'items.review',
                'files.download_restricted', 'metadata.manage', 'exports.manage', 'reports.view']],
        ['reviewer', 'Revisor / comité evaluador', 'Revisa contenido, registra observaciones y aprueba o devuelve según etapa.',
            $reviewish],
        ['program_coordinator', 'Coordinador de programa', 'Supervisa el material de su programa y emite revisiones.',
            ['dashboard.view', 'items.read_all', 'items.review', 'programs.manage', 'reports.view']],
        ['author', 'Docente / investigador', 'Deposita sus documentos, edita borradores propios y sigue su estado.',
            ['dashboard.view', 'items.create', 'items.read_own', 'items.edit_own', 'items.submit', 'files.upload']],
        ['student', 'Estudiante / egresado', 'Consulta y, si está habilitado, propone depósitos sujetos a revisión.',
            ['dashboard.view', 'items.create', 'items.read_own', 'items.submit', 'files.upload']],
        ['auditor', 'Auditor', 'Consulta reportes y bitácoras autorizadas sin alterarlas.',
            ['dashboard.view', 'reports.view', 'audit.view', 'items.read_all']],
    ];

    $stmtRole = $pdo->prepare('INSERT INTO roles (institution_id, slug, name, description, is_system, sort_order)
        VALUES (?, ?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');
    $stmtDup = $pdo->prepare('SELECT id FROM roles WHERE institution_id = ? AND slug = ?');
    $stmtPerm = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');

    $roleIds = [];
    foreach ($roles as $i => [$slug, $name, $desc, $perms]) {
        $stmtRole->execute([$institutionId, $slug, $name, $desc, $i]);
        $stmtDup->execute([$institutionId, $slug]);
        $roleId = (int) (Database::selectOne('SELECT id FROM roles WHERE institution_id = ? AND slug = ?', [$institutionId, $slug])['id'] ?? 0);
        $roleIds[$slug] = $roleId;
        foreach ($perms as $permKey) {
            if (isset($permIds[$permKey])) {
                $stmtPerm->execute([$roleId, $permIds[$permKey]]);
            }
        }
    }

    // 2) Flujo de revisión por defecto.
    $pdo->prepare('INSERT INTO review_workflows (institution_id, name, slug, description, is_default)
        VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name)')
        ->execute([$institutionId, 'Flujo estándar', 'estandar', 'Revisión simple: revisor aprueba y responsable publica.']);
    $workflow = Database::selectOne('SELECT id FROM review_workflows WHERE institution_id = ? AND slug = ?', [$institutionId, 'estandar']);
    $workflowId = (int) $workflow['id'];
    $steps = [
        ['Revisión editorial', 'review', 1, 'review', 'items.review'],
        ['Aprobación', 'approve', 2, 'approve', 'items.approve'],
        ['Publicación', 'publish', 3, 'publish', 'items.publish'],
    ];
    $stmtStep = $pdo->prepare('INSERT INTO workflow_steps (workflow_id, step_name, step_slug, order_no, action, required_permission)
        VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE step_name = VALUES(step_name), order_no = VALUES(order_no)');
    foreach ($steps as [$sName, $sSlug, $order, $action, $perm]) {
        $stmtStep->execute([$workflowId, $sName, $sSlug, $order, $action, $perm]);
    }

    // 3) Tipos documentales configurables (no fija grados ni títulos).
    $types = [
        ['Trabajo de investigación', 'trabajo-investigacion', 0],
        ['Proyecto de innovación', 'proyecto-innovacion', 0],
        ['Informe técnico', 'informe-tecnico', 0],
        ['Artículo científico', 'articulo-cientifico', 0],
        ['Ponencia / evento', 'ponencia', 0],
        ['Material didáctico', 'material-didactico', 0],
        ['Manual / guía', 'manual', 0],
        ['Libro o capítulo', 'libro-capitulo', 0],
        ['Dataset / datos', 'dataset', 0],
        ['Recurso audiovisual', 'recurso-audiovisual', 0],
        ['Documento institucional', 'documento-institucional', 0],
        ['Tesis (solo si la institución otorga grado)', 'tesis', 1],
    ];
    $stmtType = $pdo->prepare('INSERT INTO document_types (institution_id, name, slug, description, requires_degree_fields, is_active, sort_order)
        VALUES (?, ?, ?, ?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE name = VALUES(name), requires_degree_fields = VALUES(requires_degree_fields)');
    foreach ($types as $i => [$name, $slug, $degree]) {
        $stmtType->execute([$institutionId, $name, $slug, 'Tipo documental configurable por la institución.', $degree, $i]);
    }

    // 4) Perfil de metadatos inicial con campos obligatorios base.
    $pdo->prepare('INSERT INTO metadata_profiles (institution_id, name, slug, version, is_default, is_active)
        VALUES (?, ?, ?, ?, 1, 1) ON DUPLICATE KEY UPDATE name = VALUES(name)')
        ->execute([$institutionId, 'Perfil general', 'general', '1.0']);
    $profile = Database::selectOne('SELECT id FROM metadata_profiles WHERE institution_id = ? AND slug = ?', [$institutionId, 'general']);
    $profileId = (int) $profile['id'];

    $required = ['title', 'abstract', 'language', 'date_issued'];
    $always = ['title', 'abstract', 'language', 'date_issued', 'alternative_title', 'contributors', 'subject',
        'publisher', 'sponsor', 'source', 'relation', 'spatial', 'notes', 'research_line', 'extent', 'format',
        'rights_holder', 'degree_name', 'institution_granting', 'local_uri', 'notes'];
    $sort = 1;
    $stmtProfileField = $pdo->prepare('INSERT IGNORE INTO metadata_profile_fields
        (profile_id, field_id, is_required, show_in_public, sort_order) VALUES (?, ?, ?, 1, ?)');
    foreach ($always as $fieldKey) {
        $field = Database::selectOne('SELECT id FROM metadata_fields WHERE field_key = ?', [$fieldKey]);
        if ($field !== null) {
            $stmtProfileField->execute([$profileId, (int) $field['id'], in_array($fieldKey, $required, true) ? 1 : 0, $sort++]);
        }
    }

    return 'Roles: ' . count($roles) . ', tipos documentales: ' . count($types) . ', flujo y perfil instalados para institución #' . $institutionId;
};
