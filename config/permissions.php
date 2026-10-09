<?php

declare(strict_types=1);

/**
 * Permisos semilla del sistema. Agrupados por dominio.
 * Un rol agrupa permisos; la autorización se verifica por permiso + alcance.
 */
return [
    'dashboard.view' => ['label' => 'Ver escritorio', 'group' => 'General'],
    'institution.read' => ['label' => 'Consultar ficha institucional', 'group' => 'Institución'],
    'institution.update' => ['label' => 'Modificar ficha institucional', 'group' => 'Institución'],
    'branding.manage' => ['label' => 'Gestionar imagen institucional', 'group' => 'Institución'],
    'users.view' => ['label' => 'Consultar usuarios', 'group' => 'Usuarios'],
    'users.create' => ['label' => 'Crear usuarios', 'group' => 'Usuarios'],
    'users.update' => ['label' => 'Modificar usuarios', 'group' => 'Usuarios'],
    'users.disable' => ['label' => 'Suspender usuarios', 'group' => 'Usuarios'],
    'roles.manage' => ['label' => 'Gestionar roles y permisos', 'group' => 'Usuarios'],
    'programs.manage' => ['label' => 'Gestionar programas y dependencias', 'group' => 'Catálogos'],
    'communities.manage' => ['label' => 'Gestionar comunidades y colecciones', 'group' => 'Repositorio'],
    'collections.manage' => ['label' => 'Gestionar colecciones', 'group' => 'Repositorio'],
    'items.create' => ['label' => 'Crear recursos', 'group' => 'Recursos'],
    'items.read_own' => ['label' => 'Consultar recursos propios', 'group' => 'Recursos'],
    'items.read_all' => ['label' => 'Consultar todos los recursos', 'group' => 'Recursos'],
    'items.edit_own' => ['label' => 'Editar recursos propios', 'group' => 'Recursos'],
    'items.edit_all' => ['label' => 'Editar cualquier recurso', 'group' => 'Recursos'],
    'items.submit' => ['label' => 'Enviar a revisión', 'group' => 'Recursos'],
    'items.review' => ['label' => 'Revisar recursos', 'group' => 'Flujo editorial'],
    'items.approve' => ['label' => 'Aprobar recursos', 'group' => 'Flujo editorial'],
    'items.publish' => ['label' => 'Publicar recursos', 'group' => 'Flujo editorial'],
    'items.unpublish' => ['label' => 'Retirar publicación', 'group' => 'Flujo editorial'],
    'items.archive' => ['label' => 'Archivar recursos', 'group' => 'Flujo editorial'],
    'items.delete' => ['label' => 'Eliminar recursos', 'group' => 'Recursos'],
    'items.restore' => ['label' => 'Restaurar recursos', 'group' => 'Recursos'],
    'files.upload' => ['label' => 'Cargar archivos', 'group' => 'Archivos'],
    'files.download_restricted' => ['label' => 'Descargar archivos restringidos', 'group' => 'Archivos'],
    'files.replace' => ['label' => 'Reemplazar archivos', 'group' => 'Archivos'],
    'metadata.manage' => ['label' => 'Gestionar metadatos y plantillas', 'group' => 'Metadatos'],
    'embargo.manage' => ['label' => 'Gestionar embargos', 'group' => 'Acceso'],
    'imports.manage' => ['label' => 'Importar registros', 'group' => 'Datos'],
    'exports.manage' => ['label' => 'Exportar registros', 'group' => 'Datos'],
    'reports.view' => ['label' => 'Consultar reportes', 'group' => 'Reportes'],
    'audit.view' => ['label' => 'Consultar bitácora', 'group' => 'Auditoría'],
    'settings.manage' => ['label' => 'Gestionar configuración', 'group' => 'Sistema'],
    'integrations.manage' => ['label' => 'Gestionar integraciones', 'group' => 'Sistema'],
    'backups.manage' => ['label' => 'Gestionar respaldos', 'group' => 'Sistema'],
];
