# Informe de Pruebas Visuales, Responsive y Accesibilidad

> **Referencia:** Sección 16 del Prompt Maestro de Frontend  
> **Fecha de Validación:** Octubre de 2026  
> **Ambiente:** MariaDB 11.8 / PHP 8.2+ / Servidor de Pruebas Local  
> **Herramientas de Verificación:** Suite automatizada de pruebas (`php bin/console test`), emulación de viewports (320px–1920px) y validación de ratios de contraste W3C WCAG 2.2 AA.

---

## 1. Resumen de Ejecución de Pruebas Automatizadas

El proyecto cuenta con un motor de pruebas autónomo integrado en la consola del sistema (`php bin/console test`). Se ejecutaron las suites de pruebas unitarias, de integración y de seguridad con el siguiente resultado:

```text
=== BATERÍA DE PRUEBAS — REPOSITORIO INSTITUCIONAL ===

  [PASS] AppearanceWorkflowTest::testCompleteThemeWorkflowDraftPublishAndRestore
  [PASS] ThemeIsolationTest::testSanitizeHexPreventsCssInjectionAndXss
  [PASS] ThemeIsolationTest::testCompiledCssContainsOnlySafeVariables
  [PASS] ThemeIsolationTest::testMultiTenantIsolationBetweenInstitutions
  [PASS] ThemeIsolationTest::testInstitutionWithoutAssetsGracefullyReturnsDefaults
  [PASS] BrandAssetServiceTest::testAllowedKindsConstants
  [PASS] BrandAssetServiceTest::testInvalidKindThrowsException
  [PASS] ThemeServiceTest::testGetDefaultsContainsRequiredTokensAndLayout
  [PASS] ThemeServiceTest::testSanitizeHexNormalizesValidHexAndRejectsInvalid
  [PASS] ThemeServiceTest::testCalculateContrastRatio
  [PASS] ThemeServiceTest::testDeriveOnColorReturnsHighContrastText
  [PASS] ThemeServiceTest::testCompileCssGeneratesValidEscapedRootVariables

------------------------------------------------------
RESULTADO: Todas las pruebas pasaron (12 pruebas, 63 aserciones) en 0.029 seg.
```

---

## 2. Registro Detallado de Pruebas Específicas (Sección 16)

### 2.1 Prueba A: Instituto sin Activos Gráficos Previos
* **Objetivo:** Verificar que una institución recién creada, sin logotipo, favicon ni portada cargada, mantenga una presentación profesional sin imágenes rotas ni huecos vacíos.
* **Comportamiento Observado:**
  1. La cabecera institucional despliega un distintivo heráldico con las iniciales del instituto (`.brand-badge`) utilizando el color de fondo primario y texto blanco de alto contraste.
  2. El favicon del navegador adopta el favicon seguro predeterminado del sistema.
  3. La sección hero del inicio utiliza el degradado o color institucional puro (`hero_mode = 'solid'` o `'gradient'`), mostrando el buscador de forma legible y nítida.
  4. Los botones y formularios se representan con los tokens calculados del tema por defecto.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.2 Prueba B: Dos Temas Contrastantes y Aislamiento Multi-Tenant
* **Objetivo:** Verificar que dos instituciones en la misma base de datos tengan identidades completamente independientes y que editar una no altere a la otra.
* **Condición de Prueba:**
  - Institución A (ID 1): Paleta Azul Institucional (`#113B70`, `#2873BD`, `#E4AE35`).
  - Institución B (ID 2): Paleta Granate Académico (`#702C44`, `#9E4B67`, `#DCA64F`).
* **Comportamiento Observado:**
  1. Se modificó el borrador de la Institución B estableciendo el color primario en granate.
  2. La consulta del tema publicado de la Institución A demostró total invariabilidad (`#113B70` preservado).
  3. Los archivos de estilos compilados `/assets/temas/1/v1.css` y `/assets/temas/2/v1.css` se sirven con identificadores aislados y claves de almacenamiento independientes.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.3 Prueba C: Carga Segura y Cambio de Logo / Favicon
* **Objetivo:** Validar la seguridad y persistencia de subida de archivos gráficos.
* **Vectores de Ataque y Casos Anómalos Probados:**
  1. **Intento de subir archivo con extensión falsa (.png que en realidad es un script PHP):** Rechazado por `BrandAssetService` mediante inspección de cabeceras binarias con `finfo(FILEINFO_MIME_TYPE)`.
  2. **Intento de subir archivo ejecutable o script SVG:** Rechazado de inmediato por política de exclusión de formatos no sanitizables.
  3. **Subida de archivo PNG legítimo (300x90 px):** Aceptado, almacenado en ruta privada protegida y servido a través de la ruta `/assets/marca/{id}/logo_primary`.
  4. **Eliminación del activo:** Al solicitar la eliminación, el registro se retira de la base de datos y la interfaz retorna de inmediato al escudo heráldico con iniciales sin errores.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.4 Prueba D: Paletas Extremas y Derivación Automática de Textos
* **Objetivo:** Evaluar la respuesta del sistema ante elecciones de color con luminosidad extrema.
* **Casos Evaluados:**
  1. **Primario casi blanco (`#f0f4f8`):** El algoritmo de luminancia relativa detecta baja relación de contraste contra texto blanco; el sistema deriva automáticamente `--brand-on-primary: #152438` (texto oscuro de alto contraste) y emite advertencia de contraste en el configurador.
  2. **Primario oscuro profundo (`#0b1a2d`):** El sistema deriva automáticamente `--brand-on-primary: #ffffff` con un ratio de contraste superior a **13.5:1** (superando holgadamente el estándar 4.5:1 exigido por WCAG 2.2 AA).
  3. **Inyección maliciosa en código HEX (`#113b70; } body { display:none }`):** La función `ThemeService::sanitizeHex()` neutraliza la inyección y retorna el valor seguro de fallback.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.5 Prueba E: Validación Responsive en Múltiples Viewports

| Viewport | Dispositivo de Referencia | Hallazgos y Soluciones Implementadas | Estado |
|---|---|---|---|
| **320px** | Móviles ultra-compactos | Los títulos hero ajustan su escala mediante `clamp(1.75rem, 5vw, 2.5rem)`. El formulario del buscador apila el botón de búsqueda debajo del campo de texto sin desbordar el ancho de pantalla. El stepper del asistente de depósito se compacta mostrando únicamente el número del paso activo. | **Aprobado** |
| **375px** | iPhone SE / Android estándar | Menú de navegación accesible mediante botón de hamburguesa y drawer deslizable. Tarjetas académicas con lectura vertical cómoda y botones de acción con área táctil mínima de 44x44 px. | **Aprobado** |
| **768px** | Tablets / iPad Portrait | La cuadrícula de publicaciones adopta 2 columnas. En el configurador de apariencia, el panel de controles y el preview se organizan en pestañas verticales para no asfixiar el visor. | **Aprobado** |
| **1024px** | iPad Pro / Laptops compactas | El configurador de apariencia activa el modo de pantalla dividida (*split-screen*), permitiendo ver los controles a la izquierda y el lienzo de previsualización interactivo a la derecha. | **Aprobado** |
| **1440px** | Pantallas de escritorio estándar | Cuadrícula de 3 columnas para colecciones y documentos destacados. Barra superior sticky con sombra sutil y alineación perfecta dentro de `--container-max: 1240px`. | **Aprobado** |
| **1920px** | Monitores Full HD / Panorámicos | El contenido centralizado preserva los márgenes seguros (`margin: 0 auto`) sin estirar excesivamente las líneas de lectura (máximo 75-80 caracteres por línea en resúmenes). | **Aprobado** |

---

### 2.6 Prueba F: Control de Acceso, Roles y Transaccionalidad
* **Visitante no autenticado:** Intentar acceder a `/admin/apariencia` redirige inmediatamente al formulario de `/login` con código de estado HTTP adecuado.
* **Usuario autenticado con rol de estudiante o docente sin permisos administrativos:** Recibe respuesta HTTP `403 Forbidden`.
* **Petición sin token CSRF:** El controlador rechaza la solicitud de publicación o borrador con código de error `419` / `403`.
* **Aislamiento de sesión:** La variable de sesión `institution_id` vincula de forma estricta todas las operaciones de guardado y publicación a la entidad del usuario autenticado.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.7 Prueba G: Comportamiento y Resiliencia sin JavaScript
* **Navegación del Portal Público:** Todas las URLs (inicio, búsqueda mediante formulario con método `GET`, fichas de tesis y visualizador) funcionan de forma autónoma sin requerir JavaScript.
* **Búsqueda Avanzada:** El formulario tradicional envía los parámetros `?q=...&tipo=...&anio=...` y el controlador PHP responde con el renderizado completo desde el servidor.
* **Seguridad en Formularios Administrativos:** Aunque las funciones de previsualización en tiempo real y reordenación de autores en el depósito utilizan JavaScript para enriquecer la experiencia de usuario, las validaciones de datos, almacenamiento de archivos y confirmaciones críticas se ejecutan de manera estricta y vinculante en el backend PHP.
* **Veredicto:** **CONFORME (Aprobado).**

---

### 2.8 Prueba H: Regresión de Módulos Preexistentes
* Se verificó la integridad del flujo de depósito y revisión: la transformación a un asistente de 6 pasos preserva todos los nombres de campos requeridos por el modelo de base de datos (`title`, `abstract`, `collection_id`, `item_type_id`, `authors`, `advisors`, `keywords`, etc.).
* La consulta de documentos, el cálculo de métricas en el dashboard administrativo y las descargas controladas de archivos continúan operando con total estabilidad.
* **Veredicto:** **CONFORME (Aprobado).**
