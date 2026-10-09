# Mapa Integral de Pantallas y Rutas — Repositorio Institucional

> **Versión del Sistema:** 1.0.0  
> **Fecha de Documentación:** Octubre de 2026  
> **Arquitectura:** PHP Nativo 8.2+ MVC con enrutamiento declarativo y vistas desacopladas.

---

## 1. Visión General de la Navegación

El sistema cuenta con tres áreas claramente delimitadas:
1. **Portal Público:** Diseñado para la comunidad académica, investigadores y público general, con indexación optimizada para motores de búsqueda y directrices OAI-PMH.
2. **Área de Acceso y Autenticación:** Mecanismos de inicio de sesión con protección contra ataques de fuerza bruta y CSRF.
3. **Panel de Gestión Administrativa y Flujo Editorial:** Módulos por perfiles de usuario (Superadmin, Administrador Institucional, Revisor/Curador, Depositante/Docente).

---

## 2. Matriz de Rutas y Pantallas

### 2.1 Portal Público

| Ruta Web | Método | Controlador / Acción | Vista PHP | Propósito y Componentes Clave |
|---|---|---|---|---|
| `/` | `GET` | `HomeController@index` | `app/Views/web/home.php` | **Página de Inicio Institucional:** Hero adaptable (color, gradiente o foto institucional), buscador principal centrado, accesos directos, estadísticas académicas reales, publicaciones recientes y programas formativos. |
| `/buscar` | `GET` | `SearchController@index` | `app/Views/web/search/index.php` | **Buscador Académico Avanzado:** Filtros facetados por tipo de documento, año, programa de estudios y autoría. Soporte de búsqueda sin JavaScript vía query strings estándar. |
| `/recursos/{id}` | `GET` | `ItemController@show` | `app/Views/web/items/show.php` | **Ficha Académica de Documento:** Resumen, metadatos estructurados (Dublin Core / Open Graph), autores con enlaces a perfil, citas en formatos APA/IEEE/ISO 690, y visor de archivos adjuntos. |
| `/archivos/{id}/descargar` | `GET` | `FileController@download` | *N/A (Stream)* | **Descarga Controlada de Documento:** Verificación de acceso público vs. embargado, validación de cuotas, cabeceras `Content-Disposition` y registro de métricas de descarga. |
| `/archivos/{id}/ver` | `GET` | `FileController@view` | `app/Views/web/items/viewer.php` | **Visor Integrado de PDF:** Lectura directa en el navegador con interfaz accesible y controles de zoom y navegación. |
| `/comunidades` | `GET` | `CommunityController@index` | `app/Views/web/communities/index.php` | **Árbol de Comunidades y Colecciones:** Exploración jerárquica de la estructura académica del instituto. |
| `/colecciones/{id}` | `GET` | `CollectionController@show` | `app/Views/web/collections/show.php` | **Listado de Colección:** Obras indexadas dentro de un área específica. |
| `/programas` | `GET` | `ProgramController@index` | `app/Views/web/programs/index.php` | **Explorador por Programas de Estudios:** Filtrado por carreras técnicas o profesionales de la institución. |
| `/autores` | `GET` | `AuthorController@index` | `app/Views/web/authors/index.php` | **Índice Alfabético de Autores y Asesores.** |
| `/acerca` | `GET` | `PageController@about` | `app/Views/web/pages/about.php` | **Acerca del Repositorio:** Información institucional, marco legal de licenciamiento y objetivos institucionales. |
| `/politicas` | `GET` | `PageController@policies` | `app/Views/web/pages/policies.php` | **Políticas de Depósito y Acceso Abierto:** Directrices de preservación digital y derechos de autor. |

---

### 2.2 Autenticación y Control de Sesión

| Ruta Web | Método | Controlador / Acción | Vista PHP | Propósito y Componentes Clave |
|---|---|---|---|---|
| `/login` | `GET` | `AuthController@showLoginForm` | `app/Views/auth/login.php` | **Formulario de Ingreso Institucional:** Validación de credenciales, protección contra CSRF, rate-limiting contra fuerza bruta y enlaces accesibles. |
| `/login` | `POST` | `AuthController@login` | *N/A (Redirect)* | **Procesamiento de Autenticación:** Apertura de sesión segura, regeneración de Session ID y registro en auditoría. |
| `/logout` | `POST` | `AuthController@logout` | *N/A (Redirect)* | **Cierre Seguro de Sesión:** Invalidación de cookies de sesión y limpieza de contexto. |

---

### 2.3 Panel de Administración

| Ruta Web | Método | Controlador / Acción | Vista PHP | Propósito y Componentes Clave |
|---|---|---|---|---|
| `/admin` | `GET` | `AdminDashboardController@index` | `app/Views/admin/dashboard.php` | **Dashboard de Gestión:** Indicadores clave de rendimiento (KPIs), estados de depósitos pendientes, últimos documentos enviados y alertas de revisión. |
| `/admin/documentos` | `GET` | `ItemAdminController@index` | `app/Views/admin/items/index.php` | **Gestión de Acervo Académico:** Tabla avanzada con ordenamiento, filtrado por estado editorial (`borrador`, `en_revision`, `aprobado`, `rechazado`), búsqueda rápida y acciones por lote. |
| `/admin/documentos/nuevo` o `/admin/deposito` | `GET` | `ItemAdminController@create` | `app/Views/admin/items/form.php` | **Asistente de Depósito Guiado (6 Pasos):** Stepper accesible, guardado progresivo de borradores, reordenación accesible de autores y resumen final antes del envío. |
| `/admin/documentos/{id}/editar` | `GET` | `ItemAdminController@edit` | `app/Views/admin/items/form.php` | **Edición Integral de Recurso y Metadatos.** |
| `/admin/revision` | `GET` | `ReviewController@index` | `app/Views/admin/review/index.php` | **Bandeja de Revisión Curatorial:** Control de calidad de metadatos, envío de observaciones al depositante y aprobación de publicación. |
| `/admin/comunidades` | `GET` | `CommunityAdminController@index` | `app/Views/admin/communities/index.php` | **Gestión de Comunidades y Subcomunidades.** |
| `/admin/usuarios` | `GET` | `UserAdminController@index` | `app/Views/admin/users/index.php` | **Administración de Cuentas y Roles:** Asignación de permisos institucionales. |
| `/admin/auditoria` | `GET` | `AuditController@index` | `app/Views/admin/audit/index.php` | **Pistas de Auditoría Inmutables:** Consulta de eventos del sistema (quién, cuándo, qué modificó y desde qué IP). |

---

### 2.4 Módulo de Apariencia e Identidad Institucional

| Ruta Web | Método | Controlador / Acción | Vista PHP | Propósito y Componentes Clave |
|---|---|---|---|---|
| `/admin/apariencia` | `GET` | `AppearanceController@index` | `app/Views/admin/appearance/index.php` | **Centro de Control de Identidad:** 6 pestañas funcionales, selectores HEX en vivo, subida de logos/favicons/portadas, paletas sugeridas y panel de previsualización responsive. |
| `/admin/apariencia/preview` | `GET` | `AppearanceController@preview` | `app/Views/admin/appearance/preview.php` | **Marco de Previsualización Iframe:** Renderiza el portal en tiempo real y recibe tokens dinámicos vía `postMessage` sin recargas de página. |
| `/admin/apariencia/borrador` | `POST` | `AppearanceController@saveDraft` | *JSON API* | **Guardado de Borrador:** Persistencia en BD sin impactar el portal público. |
| `/admin/apariencia/publicar` | `POST` | `AppearanceController@publish` | *JSON API* | **Publicación Transaccional:** Incremento de versión, compilación atómica de CSS estático e impacto en toda la plataforma. |
| `/admin/apariencia/restaurar` | `POST` | `AppearanceController@restore` | *JSON API* | **Restauración de Versión:** Recuperación inmediata de cualquier tema histórico. |
| `/admin/apariencia/activos/subir` | `POST` | `AppearanceController@uploadAsset` | *JSON API* | **Carga Segura de Logos/Favicon:** Validación MIME con `finfo`, control de resolución y almacenamiento protegido. |
| `/admin/apariencia/activos/eliminar` | `POST` | `AppearanceController@deleteAsset` | *JSON API* | **Eliminación Controlada de Activo:** Retorno al fallback accesible automático. |
| `/admin/apariencia/paleta/restablecer` | `POST` | `AppearanceController@resetPalette` | *JSON API* | **Restablecimiento Completo de Paleta** a valores de fábrica seguros. |

---

### 2.5 Rutas de Entrega de Activos Estáticos y CSS Dinámico

| Ruta Web | Método | Controlador / Acción | Tipo de Contenido | Propósito |
|---|---|---|---|---|
| `/assets/temas/{id}/{version}.css` | `GET` | `AppearanceController@serveCss` | `text/css; charset=UTF-8` | Entrega de la hoja de tokens compilados para la institución especificada, con encabezados de caché inmutable (`Cache-Control: public, max-age=31536000, immutable`). |
| `/assets/marca/{id}/{kind}` | `GET` | `AppearanceController@serveAsset` | `image/png`, `image/webp`, etc. | Entrega protegida de logotipos, escudos y favicon institucional con validación de ETag y respuesta `304 Not Modified`. |
