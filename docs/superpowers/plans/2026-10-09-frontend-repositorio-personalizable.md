# Plan de Implementación — Frontend Premium, Responsive y Personalizable para el Repositorio Institucional

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir el frontend real, accesible (WCAG 2.2 AA), responsive (320px–1920px) y 100% personalizable por cada instituto público del Perú para el repositorio digital institucional en PHP 8.2+ MVC, con editor visual de identidad, tokens CSS dinámicos, portal público editorial, panel administrativo y documentación completa.

**Architecture:** Arquitectura modular PHP MVC existente con inyección de temas por `institution_id` mediante `ThemeService` y `BrandAssetService`. El sistema compila y sirve variables CSS validadas y tipadas (`tokens.css`) y gestiona temas en tres estados (`draft`, `published`, `archived`) en MySQL. La interfaz cliente utiliza JavaScript nativo modular (ES modules) y CSS3 modular sin dependencias externas obligatorias ni Node.js.

**Tech Stack:** PHP 8.2+ nativo, MySQL / MariaDB (InnoDB, utf8mb4), PDO, HTML5 semántico, CSS3 con Design Tokens, Vanilla JavaScript (ES modules), PHPUnit / StandaloneRunner CLI.

## Global Constraints

- Tecnologías innegociables: PHP nativo 8.2+ · MVC del proyecto existente · vistas PHP · HTML5 semántico · CSS3 modular · JavaScript nativo · MySQL · PDO.
- Idioma de toda la interfaz: español del Perú (`es-PE`).
- Zona horaria: `America/Lima`, almacenamiento UTC.
- Cero dependencias Node.js en producción; desplegable en hosting cPanel/WHM o VPS.
- Aislamiento estricto multi-institución: todo token, logo, favicon y configuración visual está vinculado a `institution_id`. Jamás filtrar borradores ni configuraciones entre instituciones.
- Seguridad estricta: No permitir ingreso de código CSS/JS/HTML libre desde el configurador administrativo. Toda personalización se basa en tokens y opciones tipadas y validadas con regex y listas blancas.
- Accesibilidad: WCAG 2.2 AA (contraste mínimo 4.5:1 para texto normal, navegación por teclado completa, semántica accesible, `prefers-reduced-motion`).
- Resiliencia: Si un instituto no tiene logo, favicon o banner cargados, la interfaz debe operar con iniciales y componentes visuales neutros sin imágenes rotas.

---

### Task 1: Migración de BD para Activos de Marca y Runner de Pruebas Autónomo

**Files:**
- Create: `database/migrations/014_create_brand_assets.php`
- Create: `tests/StandaloneRunner.php`
- Create: `tests/Unit/ThemeServiceTest.php`
- Modify: `app/Console/Kernel.php`

**Interfaces:**
- Consumes: `App\Core\Database`, `App\Core\TenantContext`
- Produces: Tabla `institution_brand_assets` en MySQL y comando CLI funcional `php bin/console test`

- [ ] **Step 1: Crear la migración `014_create_brand_assets.php`**
Crear la tabla `institution_brand_assets` con los campos `id`, `institution_id`, `kind`, `storage_key`, `mime_type`, `width`, `height`, `size_bytes`, `sha256`, `created_by`, `created_at`.
- [ ] **Step 2: Ejecutar migración y comprobar estado**
Run: `php bin/console migrate`
Expected: `[OK] 014_create_brand_assets`
- [ ] **Step 3: Crear `tests/StandaloneRunner.php`**
Implementar el test runner autónomo para que `php bin/console test` pueda descubrir y ejecutar pruebas unitarias en `tests/Unit/` y `tests/Integration/` sin necesidad de binario externo.
- [ ] **Step 4: Crear test inicial `tests/Unit/ThemeServiceTest.php`**
Crear prueba unitaria que valide sanitización de colores HEX, contraste y generación de tokens CSS por defecto.
- [ ] **Step 5: Ejecutar `php bin/console test` y verificar que pasa**
Run: `php bin/console test`
Expected: Test runner ejecuta y pasa las pruebas.

---

### Task 2: Servicios de Dominio — `ThemeService` y `BrandAssetService`

**Files:**
- Create: `app/Services/ThemeService.php`
- Create: `app/Services/BrandAssetService.php`
- Modify: `tests/Unit/ThemeServiceTest.php`

**Interfaces:**
- Consumes: `Database`, `TenantContext`, `FileStorageService`, `AuditLogger`
- Produces:
  - `ThemeService::getPublishedTheme(int $institutionId): array`
  - `ThemeService::getDraftTheme(int $institutionId): array`
  - `ThemeService::saveDraft(int $institutionId, array $tokens, array $layout, array $texts, ?int $userId): array`
  - `ThemeService::publish(int $institutionId, ?int $userId): array`
  - `ThemeService::restoreVersion(int $institutionId, int $versionId, ?int $userId): array`
  - `ThemeService::getHistory(int $institutionId): array`
  - `ThemeService::compileCss(array $tokens): string`
  - `ThemeService::getDefaults(): array`
  - `BrandAssetService::uploadAsset(int $institutionId, string $kind, array $file, ?int $userId): array`
  - `BrandAssetService::deleteAsset(int $institutionId, string $kind, ?int $userId): bool`
  - `BrandAssetService::getAsset(int $institutionId, string $kind): ?array`

- [ ] **Step 1: Implementar `ThemeService.php`**
Con soporte para validación estricta de HEX (`^#[0-9a-fA-F]{6}$`), fuentes permitidas, radios de borde, cálculo de contraste y generación de variables CSS.
- [ ] **Step 2: Implementar `BrandAssetService.php`**
Validación MIME con `finfo` (solo PNG, JPEG, WebP, ICO), validación de dimensiones, nombres aleatorios seguros en `storage/private/brands/{institutionId}/`, y generación de clave pública.
- [ ] **Step 3: Agregar pruebas en `ThemeServiceTest.php` para guardar borrador, publicar y verificar aislamiento**
- [ ] **Step 4: Ejecutar `php bin/console test`**
Run: `php bin/console test`
Expected: Todas las pruebas de `ThemeService` pasan.

---

### Task 3: Controlador de Apariencia, Rutas Administrativas y Entrega de Assets

**Files:**
- Create: `app/Http/Controllers/Admin/AppearanceController.php`
- Modify: `routes/admin.php`
- Modify: `routes/web.php`
- Modify: `app/Views/layouts/admin.php`

**Interfaces:**
- Consumes: `ThemeService`, `BrandAssetService`, `Auth`, `Authorization`, `Csrf`, `TenantContext`
- Produces:
  - `GET /admin/apariencia`: Interfaz del editor
  - `POST /admin/apariencia/borrador`: Guardar borrador JSON
  - `GET /admin/apariencia/vista-previa`: Vista previa protegida en iframe
  - `POST /admin/apariencia/publicar`: Publicar tema
  - `POST /admin/apariencia/restaurar`: Restaurar versión anterior
  - `POST /admin/apariencia/activos`: Subir logo/favicon/hero
  - `POST /admin/apariencia/activos/eliminar`: Eliminar activo
  - `GET /assets/temas/{institution_id}/{version}.css`: Servir CSS seguro y cacheable
  - `GET /assets/marca/{institution_id}/{kind}`: Servir imagen de marca autorizada

- [ ] **Step 1: Implementar `AppearanceController.php`**
Acciones autorizadas con verificación de permisos (`institution.update` o `settings.manage`), protección CSRF y respuesta JSON/HTML según corresponda.
- [ ] **Step 2: Registrar rutas en `routes/admin.php` y `routes/web.php`**
- [ ] **Step 3: Agregar enlace "Apariencia e identidad" en `app/Views/layouts/admin.php`**
- [ ] **Step 4: Probar endpoints y autorización mediante test unitario/funcional**
Run: `php bin/console test`
Expected: PASS.

---

### Task 4: Design System Modular (CSS & Componentes)

**Files:**
- Create: `public/assets/css/tokens.css`
- Create: `public/assets/css/base.css`
- Create: `public/assets/css/components.css`
- Create: `public/assets/css/layouts.css`
- Modify: `public/assets/css/app.css`
- Create: `app/Views/components/ui/button.php`
- Create: `app/Views/components/ui/badge.php`
- Create: `app/Views/components/ui/stat_card.php`
- Create: `app/Views/components/ui/stepper.php`
- Create: `app/Views/components/ui/empty_state.php`

**Interfaces:**
- Consumes: Variables CSS maestras (`--brand-primary`, `--brand-secondary`, etc.)
- Produces: Biblioteca visual completa para portal público y panel administrativo.

- [ ] **Step 1: Crear `public/assets/css/tokens.css`**
Definición de tokens CSS maestros (colores, superficies, textos, bordes, radios, sombras, espaciados y transiciones).
- [ ] **Step 2: Crear `public/assets/css/base.css` y `layouts.css`**
Reset semántico, tipografía con escala fluida, accesibilidad (`focus-visible`, `skip-link`, `prefers-reduced-motion`), contenedor responsive de 320px a 1920px.
- [ ] **Step 3: Crear `public/assets/css/components.css`**
Estilos para botones, formularios, tarjetas académicas, tarjetas KPI, pestañas, modales, toasts, badges y tablas responsive.
- [ ] **Step 4: Unificar carga en `public/assets/css/app.css`**
Garantizar compatibilidad hacia atrás manteniendo todas las clases existentes e integrando la nueva arquitectura modular.
- [ ] **Step 5: Crear vistas parciales de componentes PHP en `app/Views/components/ui/`**

---

### Task 5: Constructor Visual de Apariencia e Identidad Institucional

**Files:**
- Create: `app/Views/admin/appearance/index.php`
- Create: `app/Views/admin/appearance/preview.php`
- Create: `public/assets/css/pages/appearance.css`
- Create: `public/assets/js/modules/theme-editor.js`
- Modify: `public/assets/js/app.js`

**Interfaces:**
- Consumes: `ThemeService`, `BrandAssetService`, API endpoints de `/admin/apariencia/*`
- Produces: Experiencia administrativa premium tipo customizer con split-screen, previsualización en vivo (escritorio/tablet/móvil), selector HEX sincronizado, paletas predefinidas, carga de imágenes y publicación.

- [ ] **Step 1: Crear la vista principal `app/Views/admin/appearance/index.php`**
Panel lateral organizado en pestañas accesibles:
1. Identidad institucional (Nombre, sigla, lema, pie legal).
2. Logotipos (Principal, alternativo para fondo oscuro, compacto, previsualizaciones).
3. Favicon (PNG/ICO con vista previa de pestaña del navegador).
4. Colores (Selectores de color interactivos sincronizados con HEX, alertas de contraste WCAG, paletas predefinidas y reset).
5. Tipografía y escala.
6. Cabecera y navegación.
7. Portada e Inicio (Modo hero sólido / gradiente / imagen, orden de bloques con subir/bajar).
8. Pie de página y redes de contacto.
9. Previsualizar y Publicar (Historial de versiones y botón de restaurar).
- [ ] **Step 2: Crear la vista de previsualización `app/Views/admin/appearance/preview.php`**
Contenedor aislado para el iframe de preview con tokens inyectados dinámicamente.
- [ ] **Step 3: Implementar `public/assets/js/modules/theme-editor.js`**
Módulo JavaScript nativo para sincronizar colores, calcular ratio de contraste en vivo, cargar paletas predefinidas, cambiar vista de dispositivo (desktop/tablet/mobile en el iframe) y gestionar guardado/publicación por AJAX.
- [ ] **Step 4: Estilar con `public/assets/css/pages/appearance.css`**

---

### Task 6: Modernización y Conexión Dinámica de Layouts Públicos y Panel

**Files:**
- Modify: `app/Views/layouts/web.php`
- Modify: `app/Views/layouts/admin.php`
- Modify: `app/Views/layouts/auth.php` (o crear si no existe)
- Modify: `app/Views/web/home.php`
- Modify: `app/Views/web/search.php`
- Modify: `app/Views/web/item.php`

**Interfaces:**
- Consumes: `ThemeService::getPublishedTheme()`, `BrandAssetService`
- Produces: Inyección automática de tokens del tema activo, logotipos dinámicos, favicon institucional, fallback visual elegante si no hay logo, hero configurable, y visor seguro de PDF.

- [ ] **Step 1: Actualizar `app/Views/layouts/web.php`**
Cargar la hoja de estilos dinámica de la institución (`/assets/temas/{id}/{version}.css` o inyección de tokens validados), favicon institucional real, logos con `object-fit: contain`, fallback con iniciales y footer dinámico.
- [ ] **Step 2: Actualizar `app/Views/layouts/admin.php`**
Sincronizar variables de marca del tema con la interfaz administrativa, sidebar responsive con drawer para móvil y selector institucional.
- [ ] **Step 3: Actualizar vista de Login institucional**
Diseño de alto nivel en 2 columnas en desktop, marca institucional activa y formulario accesible.
- [ ] **Step 4: Mejorar `app/Views/web/home.php`**
Hero con modo configurable (sólido / gradiente / imagen de fondo institucional con superposición accesible), buscador central prominente, tarjetas estadísticas reales y bloques dinámicos.
- [ ] **Step 5: Enriquecer `app/Views/web/search.php` y `app/Views/web/item.php`**
Drawer de filtros móviles a 320px, ficha académica con citador en un clic (APA/BibTeX/RIS) y visor PDF protegido.

---

### Task 7: Asistente de Depósito Guiado de 6 Pasos en Panel Administrativo

**Files:**
- Create: `public/assets/js/modules/deposit-wizard.js`
- Modify: `app/Views/admin/items/create.php` (o crear vista guiada si es necesario)
- Modify: `app/Http/Controllers/Admin/ItemAdminController.php`

**Interfaces:**
- Consumes: `ItemAdminController`, `ItemWorkflowService`
- Produces: Asistente guiado de depósito en 6 pasos según sección 7.4 del prompt maestro:
  1. Comunidad, colección y tipo de documento.
  2. Información general, título y autores (reordenables con botones accesibles).
  3. Metadatos académicos.
  4. Modalidad de acceso y licencias (embargo).
  5. Subida de archivos con progreso y validación MIME.
  6. Resumen y envío a revisión o borrador.

- [ ] **Step 1: Diseñar el stepper visual accesible y formulario en 6 pasos**
- [ ] **Step 2: Implementar la interactividad cliente en `deposit-wizard.js` con autoguardado de borrador y validación paso a paso**
- [ ] **Step 3: Conectar con el backend en `ItemAdminController.php`**
- [ ] **Step 4: Probar el flujo completo de depósito**

---

### Task 8: Pruebas Exhaustivas (Suite A a H) y QA Automatizado

**Files:**
- Create: `tests/Unit/BrandAssetServiceTest.php`
- Create: `tests/Integration/AppearanceWorkflowTest.php`
- Create: `tests/Security/ThemeIsolationTest.php`

**Interfaces:**
- Consumes: Toda la suite de servicios y base de datos
- Produces: Ejecución de las 8 pruebas obligatorias especificadas en la sección 16 del prompt maestro:
  - Prueba A: Instituto sin activos (fallback visual limpio).
  - Prueba B: Dos instituciones con temas contrastantes (aislamiento total multi-tenant).
  - Prueba C: Cambio de logo/favicon y rechazo de archivos inválidos/maliciosos.
  - Prueba D: Paletas de bajo contraste o extremos (validación y contraste legible).
  - Prueba E: Viewports móviles (320px, 375px, 430px, 768px, 1024px, 1440px).
  - Prueba F: Roles y permisos (403 para usuarios sin permiso, CSRF obligatorio).
  - Prueba G: Accesibilidad sin JS (formulario GET, ficha y navegación operativas sin JS).
  - Prueba H: Regresión de flujos existentes (búsqueda, citas, descargas autorizadas).

- [ ] **Step 1: Escribir y ejecutar las pruebas en `tests/`**
- [ ] **Step 2: Correr `php bin/console test`**
Run: `php bin/console test`
Expected: 100% pruebas aprobadas sin errores.

---

### Task 9: Documentación Técnica de Frontend y Entrega Final

**Files:**
- Create: `docs/FRONTEND_DESIGN_SYSTEM.md`
- Create: `docs/FRONTEND_PERSONALIZACION.md`
- Create: `docs/FRONTEND_MAPA_PANTALLAS.md`
- Create: `docs/FRONTEND_PRUEBAS_VISUALES.md`

**Interfaces:**
- Produces: Los 4 documentos obligatorios exigidos en la Sección 18 del prompt maestro, con tablas de tokens, guía del usuario administrador, matriz de pantallas y registro de pruebas visuales/responsive.

- [ ] **Step 1: Redactar `docs/FRONTEND_DESIGN_SYSTEM.md`**
- [ ] **Step 2: Redactar `docs/FRONTEND_PERSONALIZACION.md`**
- [ ] **Step 3: Redactar `docs/FRONTEND_MAPA_PANTALLAS.md`**
- [ ] **Step 4: Redactar `docs/FRONTEND_PRUEBAS_VISUALES.md`**
- [ ] **Step 5: Verificación final completa con navegador/CLI y reporte al usuario**
