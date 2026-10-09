<?php

declare(strict_types=1);

/** @var App\Core\Router $router */

use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CommunityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\ItemAdminController;
use App\Http\Controllers\Admin\MetadataController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\QualityController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewQueueController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;

// ---------- Panel administrativo ----------
// Todas las rutas exigen sesion; el permiso especifico se verifica en cada accion.

// Escritorio (6.1).
$router->get('/admin', [DashboardController::class, 'index'], ['auth']);

// Usuarios (6.3).
$router->get('/admin/usuarios', [UserController::class, 'index'], ['auth']);
$router->get('/admin/usuarios/nuevo', [UserController::class, 'create'], ['auth']);
$router->post('/admin/usuarios', [UserController::class, 'store'], ['auth', 'csrf']);
$router->get('/admin/usuarios/{id:\d+}/editar', [UserController::class, 'edit'], ['auth']);
$router->post('/admin/usuarios/{id:\d+}', [UserController::class, 'update'], ['auth', 'csrf']);
$router->post('/admin/usuarios/{id:\d+}/suspender', [UserController::class, 'toggle'], ['auth', 'csrf']);
$router->post('/admin/usuarios/{id:\d+}/restablecer-contrasena', [UserController::class, 'resetPassword'], ['auth', 'csrf']);

// Roles y permisos (6.4).
$router->get('/admin/roles', [RoleController::class, 'index'], ['auth']);
$router->get('/admin/roles/{id:\d+}/permisos', [RoleController::class, 'edit'], ['auth']);
$router->post('/admin/roles/{id:\d+}/permisos', [RoleController::class, 'update'], ['auth', 'csrf']);

// Programas y dependencias (6.5).
$router->get('/admin/programas', [ProgramController::class, 'index'], ['auth']);
$router->get('/admin/programas/nuevo', [ProgramController::class, 'create'], ['auth']);
$router->post('/admin/programas', [ProgramController::class, 'store'], ['auth', 'csrf']);
$router->get('/admin/programas/{id:\d+}/editar', [ProgramController::class, 'edit'], ['auth']);
$router->post('/admin/programas/{id:\d+}', [ProgramController::class, 'update'], ['auth', 'csrf']);

// Comunidades y colecciones (6.6).
$router->get('/admin/comunidades', [CommunityController::class, 'index'], ['auth']);
$router->get('/admin/comunidades/nuevo', [CommunityController::class, 'create'], ['auth']);
$router->post('/admin/comunidades', [CommunityController::class, 'store'], ['auth', 'csrf']);
$router->get('/admin/comunidades/{id:\d+}/editar', [CommunityController::class, 'edit'], ['auth']);
$router->post('/admin/comunidades/{id:\d+}', [CommunityController::class, 'update'], ['auth', 'csrf']);
$router->post('/admin/comunidades/{id:\d+}/eliminar', [CommunityController::class, 'destroy'], ['auth', 'csrf']);
$router->get('/admin/comunidades/colecciones/nueva', [CommunityController::class, 'createCollection'], ['auth']);
$router->post('/admin/comunidades/colecciones', [CommunityController::class, 'storeCollection'], ['auth', 'csrf']);
$router->get('/admin/comunidades/colecciones/{id:\d+}/editar', [CommunityController::class, 'editCollection'], ['auth']);
$router->post('/admin/comunidades/colecciones/{id:\d+}', [CommunityController::class, 'updateCollection'], ['auth', 'csrf']);
$router->post('/admin/comunidades/colecciones/{id:\d+}/eliminar', [CommunityController::class, 'destroyCollection'], ['auth', 'csrf']);

// Tipos documentales (6.7).
$router->get('/admin/tipos-documentales', [DocumentTypeController::class, 'index'], ['auth']);
$router->get('/admin/tipos-documentales/nuevo', [DocumentTypeController::class, 'create'], ['auth']);
$router->post('/admin/tipos-documentales', [DocumentTypeController::class, 'store'], ['auth', 'csrf']);
$router->get('/admin/tipos-documentales/{id:\d+}/editar', [DocumentTypeController::class, 'edit'], ['auth']);
$router->post('/admin/tipos-documentales/{id:\d+}', [DocumentTypeController::class, 'update'], ['auth', 'csrf']);

// Metadatos: diccionario y perfiles (7.3).
$router->get('/admin/metadatos', [MetadataController::class, 'index'], ['auth']);
$router->get('/admin/metadatos/campos/nuevo', [MetadataController::class, 'createField'], ['auth']);
$router->post('/admin/metadatos/campos', [MetadataController::class, 'storeField'], ['auth', 'csrf']);
$router->get('/admin/metadatos/campos/{id:\d+}/editar', [MetadataController::class, 'editField'], ['auth']);
$router->post('/admin/metadatos/campos/{id:\d+}', [MetadataController::class, 'updateField'], ['auth', 'csrf']);
$router->post('/admin/metadatos/campos/{id:\d+}/eliminar', [MetadataController::class, 'deleteField'], ['auth', 'csrf']);
$router->post('/admin/metadatos/perfiles', [MetadataController::class, 'storeProfile'], ['auth', 'csrf']);
$router->get('/admin/metadatos/perfiles/{id:\d+}', [MetadataController::class, 'editProfile'], ['auth']);
$router->post('/admin/metadatos/perfiles/{id:\d+}', [MetadataController::class, 'updateProfile'], ['auth', 'csrf']);
$router->post('/admin/metadatos/perfiles/{id:\d+}/duplicar', [MetadataController::class, 'duplicateProfile'], ['auth', 'csrf']);
$router->post('/admin/metadatos/perfiles/{id:\d+}/campos', [MetadataController::class, 'saveProfileFields'], ['auth', 'csrf']);

// Calidad de recursos y metadatos (6.17).
$router->get('/admin/calidad', [QualityController::class, 'index'], ['auth']);
$router->post('/admin/calidad/recalcular', [QualityController::class, 'recalculate'], ['auth', 'csrf']);

// Ayuda y documentacion (6.20).
$router->get('/admin/ayuda', [HelpController::class, 'index'], ['auth']);

// Recursos: deposito, revision, archivos, versiones (6.8-6.13).
$router->get('/admin/recursos', [ItemAdminController::class, 'index'], ['auth']);
$router->get('/admin/deposito', [ItemAdminController::class, 'create'], ['auth']);
$router->post('/admin/recursos', [ItemAdminController::class, 'store'], ['auth', 'csrf']);
$router->get('/admin/recursos/{id:\d+}', [ItemAdminController::class, 'show'], ['auth']);
$router->get('/admin/recursos/{id:\d+}/editar', [ItemAdminController::class, 'edit'], ['auth']);
$router->post('/admin/recursos/{id:\d+}', [ItemAdminController::class, 'update'], ['auth', 'csrf']);
$router->post('/admin/recursos/{id:\d+}/enviar', [ItemAdminController::class, 'submit'], ['auth', 'csrf']);
$router->post('/admin/recursos/{id:\d+}/transicion', [ItemAdminController::class, 'transition'], ['auth', 'csrf']);
$router->post('/admin/recursos/{id:\d+}/nueva-version', [ItemAdminController::class, 'newVersion'], ['auth', 'csrf']);
$router->post('/admin/recursos/{id:\d+}/archivos', [ItemAdminController::class, 'uploadFile'], ['auth', 'csrf']);
$router->post('/admin/recursos/{id:\d+}/archivos/{fileId:\d+}/eliminar', [ItemAdminController::class, 'deleteFile'], ['auth', 'csrf']);

// Buscador interno de administracion (6.13): alias al gestor de recursos.
$router->get('/admin/buscar', static fn ($request) => \App\Core\Response::redirect(
    '/admin/recursos' . ((string) $request->queryString() !== '' ? '?' . $request->queryString() : '')
), ['auth']);

// Revision editorial (6.10).
$router->get('/admin/revision', [ReviewQueueController::class, 'index'], ['auth']);
$router->post('/admin/revision/asignar', [ReviewQueueController::class, 'assign'], ['auth', 'csrf']);
$router->post('/admin/revision/{id:\d+}/observaciones', [ReviewQueueController::class, 'comment'], ['auth', 'csrf']);

// Reportes y estadisticas (6.14).
$router->get('/admin/reportes', [ReportController::class, 'index'], ['auth']);
$router->get('/admin/reportes/exportar', [ReportController::class, 'export'], ['auth']);

// Bitacora y auditoria (6.18).
$router->get('/admin/auditoria', [AuditController::class, 'index'], ['auth']);
$router->get('/admin/auditoria/exportar', [AuditController::class, 'export'], ['auth']);

// Institucion: ficha, licencias y embargos (6.12).
$router->get('/admin/institucion', [InstitutionController::class, 'index'], ['auth']);
$router->post('/admin/institucion', [InstitutionController::class, 'update'], ['auth', 'csrf']);
$router->post('/admin/institucion/licencias', [InstitutionController::class, 'storeLicense'], ['auth', 'csrf']);
$router->post('/admin/institucion/licencias/{id:\d+}', [InstitutionController::class, 'updateLicense'], ['auth', 'csrf']);
$router->post('/admin/institucion/licencias/{id:\d+}/alternar', [InstitutionController::class, 'toggleLicense'], ['auth', 'csrf']);
$router->post('/admin/institucion/embargos/{id:\d+}/liberar', [InstitutionController::class, 'releaseEmbargo'], ['auth', 'csrf']);

// Notificaciones (6.15).
$router->get('/admin/notificaciones', [NotificationController::class, 'index'], ['auth']);
$router->get('/admin/notificaciones/preferencias', [NotificationController::class, 'preferences'], ['auth']);
$router->post('/admin/notificaciones/preferencias', [NotificationController::class, 'savePreferences'], ['auth', 'csrf']);
$router->post('/admin/notificaciones/leer-todas', [NotificationController::class, 'readAll'], ['auth', 'csrf']);
$router->post('/admin/notificaciones/{id:\d+}/leer', [NotificationController::class, 'read'], ['auth', 'csrf']);

// Sistema: salud, cola, tareas y respaldos (6.19).
$router->get('/admin/sistema', [SystemController::class, 'index'], ['auth']);
$router->post('/admin/sistema/tarea', [SystemController::class, 'runTask'], ['auth', 'csrf']);

// Importacion y exportacion (6.16).
$router->get('/admin/importar-exportar', [ImportExportController::class, 'index'], ['auth']);
$router->post('/admin/importar-exportar/subir', [ImportExportController::class, 'upload'], ['auth', 'csrf']);
$router->post('/admin/importar-exportar/exportar', [ImportExportController::class, 'export'], ['auth', 'csrf']);
$router->get('/admin/importar-exportar/exportar/{id:\d+}', [ImportExportController::class, 'download'], ['auth']);
$router->get('/admin/importar-exportar/{id:\d+}', [ImportExportController::class, 'show'], ['auth']);
$router->post('/admin/importar-exportar/{id:\d+}/preparar', [ImportExportController::class, 'prepare'], ['auth', 'csrf']);
$router->post('/admin/importar-exportar/{id:\d+}/importar', [ImportExportController::class, 'runImport'], ['auth', 'csrf']);
$router->post('/admin/importar-exportar/{id:\d+}/revertir', [ImportExportController::class, 'revert'], ['auth', 'csrf']);

// Apariencia e identidad institucional (Secciones 2 y 3).
$router->get('/admin/apariencia', [AppearanceController::class, 'index'], ['auth']);
$router->post('/admin/apariencia/borrador', [AppearanceController::class, 'saveDraft'], ['auth', 'csrf']);
$router->get('/admin/apariencia/vista-previa', [AppearanceController::class, 'preview'], ['auth']);
$router->post('/admin/apariencia/publicar', [AppearanceController::class, 'publish'], ['auth', 'csrf']);
$router->post('/admin/apariencia/restaurar', [AppearanceController::class, 'restore'], ['auth', 'csrf']);
$router->post('/admin/apariencia/activos', [AppearanceController::class, 'uploadAsset'], ['auth', 'csrf']);
$router->post('/admin/apariencia/activos/eliminar', [AppearanceController::class, 'deleteAsset'], ['auth', 'csrf']);
$router->post('/admin/apariencia/restablecer-paleta', [AppearanceController::class, 'resetPalette'], ['auth', 'csrf']);

