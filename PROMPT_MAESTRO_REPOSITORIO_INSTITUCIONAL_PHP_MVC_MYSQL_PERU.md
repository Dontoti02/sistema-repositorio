# PROMPT MAESTRO — SISTEMA DE REPOSITORIO DIGITAL INSTITUCIONAL PARA INSTITUTOS PÚBLICOS DEL PERÚ

> **Versión del prompt:** 1.0  
> **Fecha de referencia:** 08 de octubre de 2026  
> **Tecnologías obligatorias:** PHP nativo orientado a objetos · arquitectura MVC · MySQL · HTML5 · CSS3 · JavaScript nativo  
> **Propósito:** entregar a una IA de programación (OpenCode, Claude Code, Codex, etc.) este documento completo para diseñar, implementar, documentar, probar y desplegar una aplicación real, reutilizable y mantenible.  
> **Idioma del producto, comentarios esenciales, interfaz, manuales y respuestas:** español del Perú.

---

## 0. INSTRUCCIÓN DE EJECUCIÓN PARA LA IA DESARROLLADORA

Actúa como un **arquitecto de software sénior, ingeniero PHP/MySQL, especialista en repositorios institucionales académicos, analista de seguridad, diseñador UX/UI, DBA y responsable de QA/DevOps**. Tu misión es **construir una aplicación operativa**, no limitarte a proponer pantallas, producir pseudocódigo, crear maquetas desconectadas o escribir documentación sin implementación.

**Lee este archivo de principio a fin** y úsalo como contrato técnico y funcional. Inspecciona primero el proyecto si ya existe; conserva los componentes sanos y adapta la solución. Si estás empezando de cero, crea la estructura completa. Desarrolla en fases verificables. Evita pedir aclaraciones por decisiones menores: utiliza valores por defecto sensatos y registra las suposiciones en `docs/DECISIONES.md`. Si una decisión impide la interoperabilidad o supone un riesgo jurídico, explica el supuesto y configura la característica en vez de imponerla.

### Reglas innegociables

1. **Backend en PHP nativo**, programación orientada a objetos, MVC propio, controladores delgados, servicios de negocio, repositorios de persistencia, validadores y vistas PHP. **No utilizar Laravel, Symfony, CodeIgniter ni otro framework PHP completo.** Composer está permitido exclusivamente para autoload PSR-4 y librerías puntuales justificadas.
2. **Base de datos MySQL 8.x** (preferible 8.4 LTS cuando el hosting lo permita), InnoDB, `utf8mb4`, consultas con **PDO y parámetros enlazados**, transacciones para operaciones compuestas y migraciones versionadas.
3. Desarrollar una **solución reutilizable para cualquier instituto público peruano**, sin nombres, carreras, logos, colores, autoridades o dominios fijos. Identidad institucional, tipos de documentos, programas, dependencias, catálogos y políticas son configurables.
4. Soportar **instalación independiente para un solo instituto** y arquitectura preparada para **múltiples institutos aislados**. Un único modelo de dominio con `institution_id` en toda entidad institucional; en modo individual existe un instituto. La administración central multientidad solo se habilita cuando corresponde.
5. **No exponer documentos restringidos por enlaces directos**. Los archivos originales se guardan fuera del directorio público y se sirven mediante un controlador que valida permisos en cada solicitud.
6. El repositorio debe ofrecer **sitio público completo** y **panel administrativo completo**, con diseños coherentes, responsive y accesibles. El visitante no necesita cuenta para consultar materiales de acceso abierto.
7. Crear código **real, completo, ejecutable y comprobable**. Nunca entregar métodos vacíos, botones sin acción, datos falsos presentados como reales, rutas sin controladores, credenciales codificadas, consultas SQL concatenadas con entrada del usuario o `TODO` que oculte funcionalidad esencial.
8. Todas las funcionalidades avanzadas sujetas a disponibilidad externa (DOI, Handle, ORCID autenticado, antivirus, correo SMTP, OCR, SSO) deben implementar una **interfaz de integración y una alternativa funcional/estado explícito**. Nunca simular que un identificador oficial fue registrado.
9. Para aspectos regulatorios, consultar las **fuentes oficiales vigentes** en el momento de implementar. La aplicación facilitará el cumplimiento, pero no afirmará certificación automática ante CONCYTEC, SUNEDU, MINEDU ni otra entidad.
10. Antes de considerar concluido un módulo, verificar código, migraciones, validaciones, autorizaciones, pruebas, accesibilidad básica, interfaz, registros y documentación.

### Resultado perseguido

Entregar una plataforma similar en alcance funcional a un **repositorio académico institucional moderno**, adecuada a institutos tecnológicos y pedagógicos del Perú, con comunidades, colecciones, documentos académicos, búsqueda avanzada, metadatos normalizados, vistas y descargas, licencias, embargos, aprobación editorial, preservación, métricas, exportaciones e interoperabilidad.

---

## 1. CONTEXTO Y OBJETIVOS

### 1.1 Problema que resuelve

Los institutos públicos necesitan organizar, preservar, difundir y recuperar su producción académica, científica, tecnológica e institucional: trabajos de investigación, proyectos de innovación, tesis cuando correspondan, informes, publicaciones docentes, artículos, libros, recursos de enseñanza, material de estudio autorizado, memorias y documentos institucionales publicables. El sistema debe reemplazar carpetas desordenadas, páginas con listados manuales y archivos sin metadatos ni trazabilidad.

### 1.2 Objetivos verificables

- Centralizar recursos digitales con metadatos fiables, clasificación coherente e identificador público estable.
- Publicar colecciones abiertas y, separadamente, administrar material privado, restringido o embargado.
- Permitir depósito por personal autorizado y revisión antes de la publicación.
- Mejorar la recuperación mediante búsqueda, filtros, facetas, autores, materias, programas, años y tipos de material.
- Preservar archivos originales, controlar sus versiones y registrar integridad con SHA-256.
- Configurarse para cualquier instituto sin modificar el código fuente.
- Proporcionar herramientas de interoperabilidad académica y exportación de metadatos.
- Generar indicadores auditables sobre publicaciones, consultas, descargas y productividad de las colecciones.
- Mantener seguridad, disponibilidad, trazabilidad y posibilidad de restauración.

### 1.3 Audiencia

Visitantes públicos; estudiantes; egresados; docentes; investigadores; bibliotecarios; responsables del repositorio; coordinadores de programas; jefes de investigación; equipo de TI; dirección institucional; auditores y administradores de una eventual red multientidad.

### 1.4 Alcance y límites

El producto es un **repositorio digital**, no un sistema de matrícula, notas, trámite documentario, gestión de expedientes administrativos o biblioteca de préstamos físicos. Puede **integrarse** con esos sistemas, pero no debe fusionar sus bases sin autorización. Los expedientes con datos personales sensibles o información reservada no deben hacerse públicos por defecto. Los instrumentos de gestión institucional podrán publicarse si la política aplicable lo permite.

---

## 2. MODELOS DE INSTALACIÓN Y MULTIINSTITUCIÓN

Implementa desde el principio una separación estricta entre datos **globales** (catálogos del producto y configuración de red) e **institucionales** (documentos, colecciones, usuarios vinculados, estadísticas, archivos, flujos, branding).

### Modo A — Instituto independiente (predeterminado)

- Una instalación PHP/MySQL y una institución activa.
- URL ejemplo: `https://repositorio.instituto-ejemplo.edu.pe/`.
- Asistente de instalación inicial: conexión de BD, verificación de extensiones, cuenta administradora, ficha institucional, creación de tablas, generación de claves y bloqueo del instalador.
- No mostrar opciones de administración de red que no correspondan.
- Aplicable en hosting cPanel/WHM o VPS con Apache/Nginx.

### Modo B — Plataforma para varios institutos

- Una instancia de software con sitios institucionales separados por dominios o subdominios autorizados; catálogos compartidos cuando sea seguro.
- Ficha propia, portal propio, logo, correo, programas, repositorios, usuarios, permisos, archivos, estadísticas y políticas por instituto.
- `institution_id` **obligatorio** en entidades de alcance institucional; filtros por institución en *toda* operación del servidor, incluyendo consultas, colas, cache, exportaciones y descarga de archivos.
- Resolución de instituto mediante **tabla de dominios verificados/permitidos**, jamás confiando ciegamente en encabezados `Host` sin validar. Proporcionar mecanismo seguro de selección del instituto cuando corresponda.
- Usuarios que pertenezcan a más de un instituto mediante tabla de membresía con roles por institución; cambios de contexto siempre revalidan autorización.
- Administrador de red únicamente administra la plataforma; sus accesos cruzados a contenido requieren permisos explícitos y quedan auditados.
- Exportación, eliminación lógica, suspensión y respaldo por instituto sin afectar a los demás.
- El portal agregado de red es **opcional**, y solo muestra documentos autorizados y públicos.

### Aislamiento comprobable

Crear pruebas automáticas que intenten obtener documentos, adjuntos, usuarios, configuraciones, estadísticas o colecciones del instituto B con credenciales/URL del A. Todas deben fallar sin filtrar información (incluidas búsquedas por ID y archivos antiguos). Cuando proceda, utilizar llaves compuestas `(institution_id, id)` y claves foráneas/controles coherentes para reducir referencias entre institutos.

---

## 3. IDENTIDAD, CATÁLOGOS Y PERSONALIZACIÓN

### Ficha institucional

Nombre oficial y abreviado; tipo de institución (IEST, IESTP, EEST, IESP, EESP u otro configurable); código institucional cuando aplique; RUC opcional; DRE/GRE; departamento, provincia, distrito y ubigeo; dirección de contacto, teléfonos, correo institucional, web, redes sociales, coordinador; año; descripción; misión/visión opcionales; estado; dominio; zona horaria `America/Lima`; idioma predeterminado `es-PE`.

### Imagen institucional

Logo principal, favicon, portada/hero, banner, imagen por defecto, colores primario/secundario/acento, tipografías web seguras, tamaño de logos y ubicación en cabecera. Controles de contraste y vista previa. Configurar menú, pie de página, correo de soporte, textos del inicio, enlaces, redes, políticas y aviso de privacidad sin alterar archivos PHP/CSS.

### Catálogos configurables

- Programas de estudios (nombre, código si existe, dependencia, estado, orden), incluyendo carreras que cambian entre institutos.
- Áreas, oficinas, unidades académicas, grupos de investigación y departamentos.
- Comunidades y colecciones anidadas, con slug y responsables.
- Tipos documentales; subtipos; líneas de investigación; áreas temáticas; palabras clave; formatos; idiomas; grados/títulos **solo cuando correspondan**.
- Condiciones de acceso; licencias; motivos de rechazo; políticas de conservación; plantillas de metadatos.
- Ámbitos geográficos usando catálogo de ubigeo peruano importable, sin codificar lugares en controladores.
- Vocabularios controlados y sus versiones, con capacidad de equivalencias y etiquetas.

Los catálogos se editan con formularios validados, búsqueda, filtros, importación controlada, activación/desactivación, orden y validación de uso antes de eliminar.

---

## 4. ROLES, PERMISOS Y ALCANCES

Implementar **RBAC** con permisos granulares y posibilidad de aplicar alcance (`global`, `institution`, `community`, `collection`, `program`, `own`). Un rol es una agrupación de permisos, no una condición `if ($role == 'admin')` distribuida por todo el código. Verificar autorización en rutas, servicios, consultas y descarga de adjuntos.

| Rol inicial | Responsabilidades principales |
|---|---|
| Administrador general de plataforma | Configuración técnica de la red, instituciones, dominios, operación y monitoreo; solo en modo multientidad |
| Administrador institucional | Usuarios, permisos, branding, catálogos, colecciones, políticas y reportes de su instituto |
| Responsable del repositorio | Gestión editorial, flujos, metadatos, publicaciones, embargos, integridad y calidad |
| Bibliotecario / catalogador | Revisar, corregir, normalizar metadatos y gestionar colecciones asignadas |
| Revisor / comité evaluador | Revisar contenido, registrar observaciones y aprobar o devolver según etapa |
| Coordinador de programa | Supervisar material de su programa y emitir revisiones según permisos |
| Docente / investigador | Depositar sus documentos, editar borradores propios y seguir su estado |
| Estudiante / egresado | Consultar y, si está habilitado, proponer depósitos sujetos a revisión |
| Auditor | Consultar reportes y bitácoras autorizadas sin alterarlas |
| Visitante público | Buscar, leer metadatos, visualizar y descargar exclusivamente recursos abiertos |

### Permisos semilla (expandibles)

`dashboard.view`, `institution.read`, `institution.update`, `branding.manage`, `users.view`, `users.create`, `users.update`, `users.disable`, `roles.manage`, `programs.manage`, `communities.manage`, `collections.manage`, `items.create`, `items.read_own`, `items.read_all`, `items.edit_own`, `items.edit_all`, `items.submit`, `items.review`, `items.approve`, `items.publish`, `items.unpublish`, `items.archive`, `items.delete`, `items.restore`, `files.upload`, `files.download_restricted`, `files.replace`, `metadata.manage`, `embargo.manage`, `imports.manage`, `exports.manage`, `reports.view`, `audit.view`, `settings.manage`, `integrations.manage`, `backups.manage`.

**Restricciones:** quien deposita no podrá aprobar su propio envío cuando se configure separación de funciones; no basta ocultar botones. El revisor asignado solo ve expedientes dentro de su alcance. Un usuario suspendido pierde acceso inmediatamente, también a sesiones activas cuando sea viable.

---

## 5. PORTAL PÚBLICO — EXPERIENCIA COMPLETA

Construir un sitio institucional atractivo, profesional, ligero y responsive (escritorio, tablet y móvil):

1. **Inicio:** cabecera con marca institucional, menú, buscador destacado, estadísticas públicas, colecciones destacadas, documentos recientes, publicaciones más consultadas y accesos directos. Mostrar datos reales o estados vacíos honestos.
2. **Buscar:** búsqueda general por título, autores, resumen, palabras clave, año, identificador, colección y programas. Sugerencias y corrección simple cuando sea implementable. Ordenar por relevancia, fecha, título y consultas; paginar; conservar parámetros en URL.
3. **Búsqueda avanzada:** combinar campos y operadores razonables; filtros por institución (solo portal agregado), comunidad, colección, programa, autor, tema, idioma, tipo, año desde/hasta, licencia y acceso.
4. **Explorar:** por comunidades, colecciones, autores, programas, años, materias y tipos de documento. Slugs amigables, breadcrumb, contador público de elementos por categoría.
5. **Ficha de un registro:** título, autores en orden, filiaciones, resumen, idioma, palabras clave, fecha, tipo, programa, comunidad, colección, identificadores, cita sugerida, licencia, nivel de acceso, metadatos detallados, archivos visibles, versiones públicas, forma de citar, registro de publicación y sugerencias relacionadas.
6. **Lector integrado:** vista PDF en navegador cuando el recurso sea visible; descarga sujeta a autorización; aviso de incompatibilidad; no intentar presentar como legibles formatos que no soporta el navegador.
7. **Exportar citas:** copiar cita APA (con advertencia si faltan campos), BibTeX y RIS cuando el documento tenga metadatos suficientes; escapar correctamente el contenido. Otros formatos opcionales.
8. **Estadísticas públicas:** total de recursos publicados, comunidades, colecciones y visitas/descargas agregadas, sin exposición de visitantes individuales.
9. **Institución / acerca del repositorio:** presentación, equipo, política de depósito, lineamientos de acceso, derechos de autor, contacto, preguntas frecuentes y privacidad.
10. **Accesibilidad y navegación:** atajos de teclado estándar, etiquetas legibles, menús funcionales, contraste, estados de foco, alertas comprensibles, formularios con errores junto al campo, animaciones discretas y diseño móvil sin desbordamientos.
11. **SEO:** HTML semántico, metadatos Open Graph, URL canónicas, títulos y descripciones por registro, `sitemap.xml` que incluya solo recursos públicos, `robots.txt`, datos estructurados Schema.org adecuados cuando sea pertinente.
12. **Sin registro obligatorio** para acceder a contenidos abiertos. Un recurso restringido puede mostrar únicamente los metadatos autorizados y una leyenda clara; no ofrecer un botón de descarga funcional para lo privado.

### Rutas públicas propuestas

```text
/
/buscar?q=&tipo=&anio_desde=&anio_hasta=&programa=&coleccion=&pagina=
/busqueda-avanzada
/comunidades
/comunidades/{slug}
/colecciones/{slug}
/programas/{slug}
/autores/{slug-o-id}
/recursos
/recursos/{identificador-estable}/{slug-opcional}
/recursos/{identificador-estable}/cita?formato=apa|bibtex|ris
/recursos/{identificador-estable}/archivos/{id}/ver
/recursos/{identificador-estable}/archivos/{id}/descargar
/estadisticas
/acerca-de
/politicas
/contacto
/login
```

Las rutas finales deben ser coherentes, seguras y documentadas. El identificador estable **no** debe depender del título editable.

---

## 6. PANEL ADMINISTRATIVO — MÓDULOS FUNCIONALES

### 6.1 Escritorio general

Indicadores de borradores, enviados, en revisión, observados, aprobados, publicados, embargados y archivados; depósitos recientes; pendientes por usuario/área; alertas de almacenamiento e integridad; tendencias de publicaciones y descargas; accesos rápidos según permisos.

### 6.2 Administración de la institución

Editar identidad, datos, ubicación, dominio, logotipos, estilos, contacto, enlaces, textos legales, email y configuración de repositorio. Vista previa antes de publicar cambios; historial de modificaciones relevantes.

### 6.3 Usuarios y autenticación

Altas, invitaciones por correo si SMTP está disponible, edición, bloqueo, búsqueda, cambio de contraseña seguro, recuperación por token de un solo uso, perfiles, verificación de email cuando aplique, membresías por instituto, auditoría de accesos, gestión de sesiones. No enviar contraseñas en texto plano.

### 6.4 Roles y permisos

Interfaz matricial para crear/editar roles institucionales, asignar permisos y ámbitos. Roles de sistema protegidos contra eliminación accidental; registro de cambios de privilegios.

### 6.5 Programas y dependencias

CRUD completo, catálogos de programas, líneas de investigación y unidades organizativas; asignación de coordinadores y revisores; control de visibilidad.

### 6.6 Comunidades y colecciones

Jerarquía configurable: institución → comunidad (área/programa/unidad) → colección → ítems. Soportar subcomunidades si la configuración lo requiere; orden, descripción, banner, políticas, revisores responsables y reglas de depósito por colección. Una colección no debe cambiar de instituto.

### 6.7 Tipos de documentos y formularios dinámicos

Plantillas por tipo: tesis, trabajo de suficiencia profesional (si procede), proyecto de investigación, proyecto de innovación, informe técnico, informe de prácticas autorizado, artículo, libro/capítulo, revista, ponencia, material didáctico, manual, boletín, recurso audiovisual, dataset y documento institucional publicable. Cada plantilla configura campos, etiquetas, ayuda, obligatoriedad condicional, repetición, orden, catálogos y reglas de publicación.

**No fijar** que todas las instituciones emiten las mismas clases de grado o título. Campos RENATI y grados se habilitan solo según tipo de obra, competencia de la institución y requisitos aplicables.

### 6.8 Depósito guiado de recursos

Asistente en pasos con guardado de borradores:

1. Selección de comunidad, colección y tipo documental.
2. Metadatos principales y autores/coautores con orden controlado.
3. Metadatos complementarios y académicos según plantilla.
4. Licencia, titularidad de derechos, política de acceso y embargo.
5. Carga de archivo(s) y selección de archivo principal.
6. Previsualización de ficha, comprobación automática y envío a revisión.

Debe soportar ediciones posteriores, reanudación de borrador, validación por paso y confirmación final. Los archivos se almacenan **de forma privada hasta ser autorizados**. Detectar posibles duplicados por identificador, título/autor/año y hash de archivo con confirmación humana; no deduplicar automáticamente obras distintas.

### 6.9 Ingesta de archivos

Subidas mediante `multipart/form-data`, límite configurable de tamaño y cantidad, verificación real de tipo MIME, extensión, tamaño, estructura y cabecera, nombres generados por servidor, checksum SHA-256, restricciones por colección, barra de progreso, limpieza de archivos temporales. PDF prioritario; otros formatos habilitables (DOCX, XLSX, PPTX, CSV, imágenes, audio, video, ZIP) con reglas independientes y sin ejecución. Antivirus ClamAV u otro con interfaz opcional; si no está disponible, señalización clara y política de bloqueo para categorías de riesgo. Archivos potencialmente peligrosos jamás se interpretan/ejecutan.

Para archivos grandes usar almacenamiento de disco escalable y streaming con manejo de rangos si se necesita. Miniaturas y extracción de texto como trabajos asíncronos opcionales controlados por cron/CLI; no bloquear la publicación si las herramientas están ausentes, excepto si la política institucional exige inspección.

### 6.10 Revisión y publicación editorial

Bandejas: asignados, pendientes, observados, aprobados, publicados, por vencer. Comparación de metadatos, visor de adjuntos autorizados, observaciones de campo, histórico y comentarios internos. Asignación a revisores y reglas de aprobación por colección. Rechazar implica conservar justificación y permitir corrección según política; publicación genera identificador interno persistente, fecha y evento auditable.

### 6.11 Control de versiones y preservación

Versión de contenido y metadatos, estado, motivo de cambio, editor, fecha, archivos asociados y registro de integridad. Un recurso publicado modificado sustantivamente vuelve a revisión según política; la versión pública anterior continúa o se retira explícitamente. No sobreescribir físicamente el original sin conservar versiones requeridas. Verificaciones periódicas de checksum con informe de alteraciones y procedimiento de restauración.

### 6.12 Políticas de acceso, embargo y licencias

Distinguir **visibilidad del registro** y **autorización del archivo**:

- `open`: metadatos públicos y archivo accesible.
- `metadata_only`: metadatos públicos; sin acceso al archivo.
- `embargoed`: metadatos públicos si la política lo permite; archivo bloqueado hasta fecha/hora definida.
- `restricted`: acceso limitado a roles/grupos autorizados; metadatos públicos o privados según configuración.
- `private`: registro y archivos no publicados, visibles solo a autorizados.

Licencias: derechos reservados, Creative Commons seleccionable y otras licencias documentadas; vínculo con el archivo/versión. Una licencia **no sustituye** la acreditación de titularidad ni permiso de distribución. Registrar consentimiento/declaración de depósito cuando aplique. Caducidad de embargo por tarea programada idempotente, con actualización del acceso y auditoría.

### 6.13 Buscador interno de administración

Búsqueda por estado, responsable, revisor, institución, área, colección, fecha, archivo, hash, tipo y validez de metadatos. Acciones masivas **solo para usuarios autorizados** con confirmación, controles anti-duplicación y resumen antes de ejecutar.

### 6.14 Reportes y estadísticas

Filtros por fecha, año, programa, tipo, comunidad, colección, usuario y estado. Indicadores: producción, volumen almacenado, pendientes, rechazos, tiempos de revisión, visitas a fichas y descargas de archivos públicos, búsquedas agregadas, documentos por año y por autor. Exportar CSV y, si se incorpora librería, XLSX/PDF. Diferenciar métricas de vistas reales y descargas completas; deduplicación razonable anti-bot y privacidad. Gráficos con alternativa tabular.

### 6.15 Notificaciones

Eventos: envío, asignación, observación, aprobación, rechazo, publicación, embargo próximo a vencer, expiración de embargo, cuota de almacenamiento, integridad fallida y errores de integración. Bandeja interna + emails opcionales por SMTP. Preferencias por usuario. Cola ligera en BD con reintentos, límites y registros de fallo, procesada mediante cron o comando CLI.

### 6.16 Importación y exportación

Importar registros con **CSV UTF-8** mediante mapeo de columnas y prevalidación; vista previa, informe de errores por fila, detección de conflictos, importación transaccional por lotes y posibilidad de revertir un lote cuando técnicamente sea seguro. Importación estructurada XML/JSON opcional. Exportar registros, metadatos, respaldos institucionales autorizados y paquetes de preservación. Incluir herramientas de migración desde hojas de cálculo y sitios antiguos con trazabilidad.

### 6.17 Moderación y calidad

Validadores de obligatoriedad por perfil documental, fechas coherentes, autores duplicados, licencias incompatibles, título vacío, idioma no normalizado, ORCID con checksum, DOI bien formado cuando aplique, identificadores sin inventarlos, archivo principal ausente, embargo vencido o inconsistente. Panel de calidad con incidencias, prioridad y propuesta de corrección.

### 6.18 Bitácora y auditoría

Guardar quién, qué, cuándo y sobre qué recurso cambió información, institución, resultado y contexto no sensible. Proteger integridad, restringir lectura y aplicar retención configurada. Registrar inicios de sesión, cambios de roles, aprobaciones, publicaciones, restricciones, exportaciones sensibles, descargas restringidas y restauraciones. No guardar contraseñas, tokens ni contenido secreto.

### 6.19 Configuración técnica y salud

Estado de requisitos, directorio de almacenamiento, consumo, cron, cola, email, antivirus, OCR, conexiones externas, cache, backups, último control de integridad, versión de esquema y mantenimiento. La UI **no** permitirá revelar secretos `.env` ni ejecutar comandos arbitrarios.

### 6.20 Documentación y asistencia

Ayuda contextual, manual público, manual por rol (administrador, catalogador, revisor, depositante), preguntas frecuentes y políticas de soporte. Incorporar pantallas de “sin resultados” y guías de primer uso.

---

## 7. METADATOS ACADÉMICOS E INTEROPERABILIDAD

Diseña **un registro canónico de metadatos**, independiente de las columnas de visualización, que permita múltiples valores ordenados, identificadores tipados, relaciones, vocabularios controlados, procedencia, historial y mapeos a estándares. No guardes toda la semántica únicamente en un JSON imposible de validar o consultar. Se permiten campos base indexados + tabla normalizada `item_metadata_values` para atributos extensibles, apoyados por diccionario de campos y perfiles.

### 7.1 Metadatos principales

- Título original; títulos alternativos; resumen y resumen alternativo; idioma.
- Autor(es), coautores y entidad autora, orden, filiación, ORCID si se conoce.
- Otros contribuyentes: asesores, editores, jurados cuando sea aplicable.
- Fecha de publicación/edición, fecha de ingreso, fecha de disponibilidad, periodo/año.
- Tipo de recurso, subtipo, formato, extensión física/digital, versión, estado editorial.
- Materias, descriptores y palabras clave, clasificación temática; programa, comunidad, colección.
- Editor/editorial; patrocinador; fuente; relación con otros recursos; publicación de origen.
- Identificadores: identificador local estable, URI pública, DOI real, ISBN, ISSN, Handle **real** y otros si existen.
- Titularidad de derechos, licencia, nivel de acceso, embargo, declaración de originalidad cuando proceda.
- País, ámbito geográfico, notas, financiador, información de preservación.
- Campos adicionales por trabajo conducente a grado o título **solo si corresponde y según normativa aplicable**.

### 7.2 Estándares y perfiles

1. **Dublin Core (DCMI)**: implementar mapeos desde el modelo canónico a elementos DC básicos y términos calificados requeridos.
2. **OAI-PMH 2.0**: exponer proveedor de datos compatible, al menos `oai_dc` funcional; preservar jerarquía de conjuntos y reglas de cosecha incremental. Si se exige un perfil adicional, implementarlo y validarlo por separado.
3. **Guía ALICIA/RENARE de CONCYTEC**: preparar campos, catálogos, validaciones y exportaciones conforme a la **versión oficial vigente** al realizar la implementación. A fecha de referencia del prompt, consultar específicamente **Guía ALICIA 2.1.0** (fuentes al final). No asumir que el sistema queda incorporado a ALICIA por el simple hecho de emitir XML.
4. Compatibilidad evolutiva con perfiles OpenAIRE/LA Referencia y requisitos específicos de repositorios de CTI **cuando realmente sean aplicables**.
5. Interfaz de asignación de **Handle o DOI mediante proveedor autorizado**, opcional y solo con credenciales institucionales válidas. Mientras no exista integración, utilizar URI propia persistente y etiquetarla como identificador local; jamás generar DOI/Handle ficticios.
6. **ORCID**: verificar sintaxis y dígito de control; enlazar perfiles públicos. OAuth/identidad verificada solo como integración opcional con registro real de la autorización.
7. **RENATI/SUNEDU**: no presentar como integración universal ni requisito para todos los institutos; habilitar campos específicos únicamente para supuestos en que sean jurídicamente pertinentes y exigir revisión de norma vigente.

### 7.3 Perfil de metadatos administrable

Implementar tablas que definan atributos (`field_key`, namespace, cardinalidad, datatype, validación, vocabulario, obligatoriedad general/condicional, ejemplos, orden y ayudas). Asociar perfiles a tipo documental y colección. Versionar los perfiles para no invalidar masivamente registros anteriores sin un proceso de migración y notificación.

### 7.4 Endpoint OAI-PMH real

```text
GET /oai?verb=Identify
GET /oai?verb=ListMetadataFormats
GET /oai?verb=ListSets
GET /oai?verb=ListIdentifiers&metadataPrefix=oai_dc
GET /oai?verb=ListRecords&metadataPrefix=oai_dc
GET /oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=...
```

Cumplir el **protocolo**, no solo crear endpoints con esos nombres:

- Respuestas XML válidas, namespaces y esquema adecuados, codificación UTF-8, cabeceras correctas y escaping de caracteres.
- Soporte `from`, `until`, `set`, paginación con `resumptionToken` opaco, temporal y validado, errores OAI estandarizados, fechas en UTC y `datestamp` de modificaciones que afecten los metadatos cosechados.
- Manejo consistente de eliminación lógica y política declarada de `deletedRecord` según el protocolo.
- Identificadores OAI estables, sin mezclar institutos.
- Publicación solo de registros que la política habilita para cosecha. Nunca exponer bitstreams protegidos ni datos privados.
- Pruebas de verbos, token vencido, fechas inválidas, namespaces, registros actualizados, retirados y colecciones vacías.
- Documentar URL base y ejemplos reales; verificar con un validador externo cuando exista acceso.

### 7.5 API JSON para integraciones

Ofrecer **API REST versión 1** inicialmente de solo lectura pública (`/api/v1/...`) con paginación, filtros, rate limiting, JSON con errores consistentes y documentación. Endpoints autenticados de integración únicamente si se implementan tokens restringidos, permisos y auditoría. Las acciones administrativas internas pueden permanecer en MVC tradicional: no inventar una API adicional sin necesidad.

### 7.6 Exportaciones académicas

Dublin Core XML, CSV UTF-8, JSON, BibTeX/RIS según recursos disponibles, exportación de colecciones y paquetes de metadatos. La exportación nunca debe incluir campos privados en perfiles públicos. Documentar mapeos y transformaciones con casos de prueba.

---

## 8. CICLO DE VIDA DEL DOCUMENTO Y REGLAS DE NEGOCIO

### 8.1 Estados editoriales

```text
DRAFT -> SUBMITTED -> UNDER_REVIEW -> APPROVED -> PUBLISHED
                         |             |
                         v             v
                    CHANGES_REQUESTED  REJECTED
                         |
                         +----> SUBMITTED

PUBLISHED -> WITHDRAWN / ARCHIVED (con trazabilidad)
PUBLISHED -> nueva versión -> UNDER_REVIEW -> PUBLISHED
```

`EMBARGOED`, `RESTRICTED` y `OPEN` **son modalidades de acceso**, no sustituyen estados editoriales. El documento puede estar publicado con metadatos visibles y adjunto embargado.

### 8.2 Matriz de transición

- Borrador: solo depositante o editor autorizado puede modificar y adjuntar.
- Enviado: se congela envío para evaluación; solicitar cambios lo devuelve al depositante con observaciones.
- Revisión: revisor asignado puede observar o aprobar; la acción de publicación exige permiso `items.publish`.
- Rechazo: conservar historial, motivo y decisiones; la política define si el depositante puede reenviar.
- Publicado: URL estable; cambios relevantes son nueva versión; retiradas preservan rastro interno y política de visibilidad pública.
- Archivo con embargo: descargas denegadas incluso si se conoce el URL o ID del archivo; la fecha de apertura se calcula en servidor con zona horaria documentada.
- Eliminación: preferir eliminación lógica y políticas de retención; eliminación irreversible solo con privilegios especiales, doble confirmación, registro y evaluación legal.

### 8.3 Invariantes

- Ningún recurso publicado puede carecer de los metadatos obligatorios de su perfil vigente en su publicación.
- Todo archivo pertenece a un registro e institución válidos; no pueden existir referencias cruzadas.
- Toda transición registra actor, fecha, estado anterior/nuevo, motivo y versión.
- No hay descarga pública de recursos privados, observados o no publicados.
- Un usuario no puede aprobar recursos fuera de su ámbito de permisos.
- Las URLs públicas de ítems no cambian cuando cambian títulos/slug.
- Una descarga de archivo restringido no se habilita por estar el PDF incrustado en un visor.
- La consulta de historial y bitácoras obedece permisos y minimización de datos.
- Nunca eliminar un vocabulario o colección en uso sin migración o confirmación controlada.

---

## 9. BASE DE DATOS MYSQL — DISEÑO EXIGIDO

Diseñar modelo entidad-relación y **migraciones ejecutables**. Tablas en `snake_case`, PK `BIGINT UNSIGNED` o identificadores UUID/ULID adoptados consistentemente, FK explícitas, fechas UTC, índices adecuados, constraints `UNIQUE`, soft delete según caso y convenciones uniformes. Utilizar JSON solo para configuraciones o snapshots cuando sea razonable, no para sustituir íntegramente relaciones críticas.

### 9.1 Tablas mínimas por dominio

| Dominio | Tablas sugeridas (ajustar sin perder capacidad) |
|---|---|
| Instituciones | `institutions`, `institution_domains`, `institution_settings`, `institution_media`, `institution_social_links` |
| Usuarios y control de acceso | `users`, `user_institution_memberships`, `roles`, `permissions`, `role_permissions`, `membership_roles`, `permission_scopes`, `password_reset_tokens`, `user_sessions` |
| Organización académica | `regions`, `provinces`, `districts`, `programs`, `academic_units`, `research_lines` |
| Estructura del repositorio | `communities`, `collections`, `collection_members`, `collection_policies`, `document_types` |
| Contenido y depósito | `items`, `item_versions`, `deposits`, `item_authors`, `persons`, `person_identifiers`, `item_keywords`, `keywords`, `item_relations` |
| Metadatos | `metadata_fields`, `metadata_profiles`, `metadata_profile_fields`, `metadata_values`, `controlled_vocabularies`, `vocabulary_terms`, `metadata_mappings` |
| Archivos | `files`, `file_versions`, `file_integrity_checks`, `storage_disks`, `file_access_policies`, `virus_scan_results` |
| Acceso y propiedad intelectual | `licenses`, `item_licenses`, `embargoes`, `rights_declarations`, `access_grants` |
| Revisión | `review_workflows`, `workflow_steps`, `review_assignments`, `review_decisions`, `item_status_history`, `review_comments` |
| Identificadores | `item_identifiers`, `identifier_providers`, `identifier_events` |
| Actividad y estadísticas | `item_view_events`, `download_events`, `aggregate_daily_stats`, `search_aggregates`, `audit_logs` |
| Operación | `notifications`, `notification_preferences`, `email_outbox`, `jobs`, `failed_jobs`, `import_batches`, `import_rows`, `export_jobs`, `backups`, `settings`, `schema_migrations`, `scheduled_task_runs` |

> Esta es una propuesta de modelado, no una excusa para crear tablas sin uso. Justifica fusiones/separaciones en `docs/ARQUITECTURA.md`; conserva todas las reglas y capacidades. Implementa primero las tablas necesarias para cada fase, mediante migraciones sucesivas.

### 9.2 Columnas clave (ejemplo contractual)

- `institutions`: `id`, `uuid`, `name`, `short_name`, `institution_type`, `institution_code`, `slug`, `status`, `timezone`, `region_id`, `province_id`, `district_id`, `created_at`, `updated_at`.
- `users`: `id`, `uuid`, `full_name`, `email`, `password_hash`, `email_verified_at`, `status`, `last_login_at`, `created_at`, `updated_at`.
- `user_institution_memberships`: `id`, `institution_id`, `user_id`, `status`, `created_at`, `updated_at`.
- `communities`: `id`, `institution_id`, `parent_id`, `name`, `slug`, `description`, `sort_order`, `is_public`, `created_at`, `updated_at`.
- `collections`: `id`, `institution_id`, `community_id`, `name`, `slug`, `description`, `deposit_enabled`, `is_public`, `workflow_id`, `created_at`, `updated_at`.
- `items`: `id`, `uuid`, `institution_id`, `collection_id`, `document_type_id`, `public_id`, `current_version_id`, `status`, `access_level`, `published_at`, `withdrawn_at`, `created_by`, `created_at`, `updated_at`, `deleted_at`.
- `item_versions`: `id`, `institution_id`, `item_id`, `version_number`, `title`, `abstract`, `issued_at`, `language_code`, `change_reason`, `created_by`, `created_at`.
- `metadata_values`: `id`, `institution_id`, `item_version_id`, `field_id`, `value_text`, `value_uri`, `language_code`, `sort_order`, `created_at`.
- `files`: `id`, `uuid`, `institution_id`, `item_id`, `item_version_id`, `original_filename`, `storage_key`, `disk`, `mime_type`, `file_size`, `sha256`, `visibility`, `scan_status`, `created_by`, `created_at`.
- `item_identifiers`: `id`, `institution_id`, `item_id`, `identifier_type`, `identifier_value`, `provider`, `is_verified`, `created_at`.
- `audit_logs`: `id`, `institution_id`, `actor_id`, `action`, `entity_type`, `entity_id`, `before_summary`, `after_summary`, `ip_hash_or_masked`, `created_at`.

### 9.3 Índices e integridad

- Únicos por contexto: `(institution_id, slug)` donde corresponda; dominios globales únicos; `(institution_id, public_id)` para registros; UUID únicos.
- Indexar `(institution_id, status, published_at)`, `(institution_id, collection_id, status)`, y campos de filtrado.
- Buscar con **MySQL FULLTEXT** sobre campos adecuados donde sea compatible con español, complementado por índices convencionales y consultas seguras; diseñar una tabla de índice de búsqueda si es necesario. No afirmar relevancia perfecta sin medirla.
- Considerar índices en fechas y claves externas, no indexar indiscriminadamente cada columna.
- Definir transacciones para publicación, asociación de archivos, asignación de identificadores y cambios de permisos.
- Evitar FK que acepten IDs de otra institución; documentar la estrategia de referencias compuestas y validaciones de servicio.
- Usar `DECIMAL` para cantidades exactas cuando aplique; tamaño de archivos en enteros de 64 bits; timestamps consistentes.
- Añadir migraciones y seeders **idempotentes**, nunca borrar automáticamente datos reales al ejecutar `seed`.

### 9.4 Archivos y BD

Guardar en BD **metadatos, referencias y hashes**; binarios en almacenamiento privado, no BLOB por defecto. Política de cuotas por instituto, tipos permitidos, retención, limpieza de huérfanos verificada, versiones y respaldos coherentes entre BD y directorios.

### 9.5 Datos de demostración

Seeder opcional para un instituto ficticio claramente rotulado, con 3 programas, 3 comunidades, varias colecciones, usuarios de prueba, registros públicos y un flujo completo. Prohibido usar datos reales inventados atribuidos a institutos existentes; nunca habilitar credenciales de demo en producción.

---

## 10. ARQUITECTURA PHP NATIVO MVC

### 10.1 Requisitos tecnológicos

- PHP 8.3+ o versión compatible con el hosting objetivo y **en soporte de seguridad**, extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`, `intl` recomendada, `gd` o `imagick` opcional, `zip` opcional.
- MySQL 8.x InnoDB, colación `utf8mb4` consistente.
- Apache (`mod_rewrite`) o Nginx, documentar ambas configuraciones; HTTPS en producción.
- JavaScript **vanilla** con `fetch`, `FormData`, módulos ES nativos; sin requerir Node.js en el servidor para ejecutar la app. Librerías JS ligeras solo si son indispensables y justificadas.
- CSS propio mediante variables, componentes y layout responsive; no dependencia obligatoria de CDN para funcionalidad.
- Composer para PSR-4 y paquetes pequeños autorizados (ej. PHPMailer para correo); prohibido convertir el proyecto silenciosamente a otro framework.
- Evitar `shell_exec` con valores del usuario, paquetes abandonados y dependencias innecesarias.

### 10.2 Estructura de carpetas orientativa

```text
repositorio-institucional/
├── app/
│   ├── Core/
│   │   ├── Application.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Controller.php
│   │   ├── View.php
│   │   ├── Database.php
│   │   ├── Session.php
│   │   ├── Validator.php
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Middleware.php
│   │   ├── TenantContext.php
│   │   ├── Authorization.php
│   │   └── ExceptionHandler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   ├── Admin/
│   │   │   ├── Api/
│   │   │   └── Oai/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Domain/
│   │   ├── Institutions/
│   │   ├── Identity/
│   │   ├── Repository/
│   │   ├── Metadata/
│   │   ├── Review/
│   │   ├── Preservation/
│   │   ├── Integrations/
│   │   ├── Statistics/
│   │   └── Audit/
│   ├── Services/
│   ├── Repositories/
│   ├── Policies/
│   ├── Jobs/
│   ├── Console/
│   ├── Support/
│   └── Views/
│       ├── layouts/
│       ├── components/
│       ├── web/
│       ├── admin/
│       └── errors/
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   ├── storage.php
│   ├── repository.php
│   └── permissions.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── fixtures/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── robots.txt
├── routes/
│   ├── web.php
│   ├── admin.php
│   ├── api.php
│   └── oai.php
├── storage/
│   ├── private/
│   │   └── institutions/
│   ├── logs/
│   ├── cache/
│   ├── tmp/
│   ├── exports/
│   └── backups/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Security/
│   └── Fixtures/
├── bin/
│   └── console
├── scripts/
├── docs/
│   ├── ARQUITECTURA.md
│   ├── MODELO_DATOS.md
│   ├── INSTALACION.md
│   ├── DESPLIEGUE_CPANEL.md
│   ├── DESPLIEGUE_VPS.md
│   ├── SEGURIDAD.md
│   ├── API.md
│   ├── OAI_PMH.md
│   ├── MANUAL_ADMINISTRADOR.md
│   ├── MANUAL_REVISOR.md
│   ├── MANUAL_DEPOSITANTE.md
│   ├── RESPALDO_RESTAURACION.md
│   ├── DECISIONES.md
│   └── MATRIZ_TRAZABILIDAD.md
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
├── phpunit.xml
├── README.md
└── CHANGELOG.md
```

La estructura se puede adaptar, pero preservar separación de responsabilidades y directorio público aislado.

### 10.3 Patrón de ejecución

`public/index.php` → bootstrap → router → middleware (dominio/institución, mantenimiento, sesión, CSRF cuando corresponde, autenticación, autorización, rate limit) → controlador → servicio → repositorio PDO → vista o JSON/XML. Usar un contenedor de dependencias sencillo o inyección explícita; **no crear objetos PDO o leer `$_POST` directamente en cada vista**.

### 10.4 Reglas de implementación

- PDO con sentencias preparadas; manejo explícito de errores y excepciones.
- Escapar HTML de salida con helper seguro; permitir HTML enriquecido solo cuando exista sanitización robusta.
- Router con métodos HTTP, parámetros tipados, grupos, middleware y respuestas 404/405.
- Requests/DTO validados antes de llegar a servicios.
- Servicios concentran reglas de negocio y transacciones; repositorios encapsulan SQL.
- Views PHP con layouts y componentes reutilizables, sin SQL y sin lógica de seguridad.
- Manejo de errores con identificador de incidente; mensajes públicos discretos y detalles en logs privados.
- Separar configuración, secretos y variables de entorno; `APP_DEBUG=false` en producción.
- `composer install --no-dev --optimize-autoloader` en despliegue cuando corresponda.
- CLI para migraciones, seeds, colas, cron, indexado, preservación, backups y tareas de mantenimiento.

### 10.5 Rendimiento y escalabilidad

Paginación con límites máximos, `SELECT` específico, índices y consultas optimizadas, cache por institución, invalidación al publicar/retirar/editar, streaming de archivos, recursos frontend minificados, imágenes dimensionadas, tareas pesadas fuera de request, rotación de logs y cuotas configurables. Documentar objetivos iniciales medibles con una carga de prueba representativa en lugar de prometer millones de usuarios sin medición.

---

## 11. DISEÑO UX/UI — REQUERIMIENTOS VISUALES

Estilo **institucional, moderno, sobrio y profesional**. No copiar íntegramente la identidad de un instituto concreto. Variables CSS de marca: `--brand-primary`, `--brand-secondary`, `--brand-accent`, `--surface`, `--text`, `--muted`, `--border`. Fondo predominantemente claro con buen contraste; componentes ordenados, tarjetas de recursos, filtros de fácil uso y panel administrativo con navegación lateral.

### Diseño del sitio público

- Barra institucional y menú responsive.
- Hero con título, breve descripción y buscador principal.
- Estadísticas en tarjetas.
- Grilla de comunidades/colecciones y recursos destacados.
- Tarjeta de recurso con título, tipo, autores, año, acceso y enlace a ficha.
- Filtros laterales en escritorio y desplegables accesibles en móvil.
- Ficha con área de metadatos y bloque de archivos/citación, adaptable a pantallas pequeñas.
- Pie con identidad institucional, contacto y políticas.

### Diseño del panel

- Sidebar colapsable por rol, topbar con usuario e institución, breadcrumbs y buscador contextual.
- Dashboard con KPIs, gráficos y tabla de últimos movimientos.
- Formularios en pasos para depósito, autoguardado de borradores sin pérdida de datos.
- Tablas con paginación real, filtros, acciones de fila, selección múltiple autorizada y exportación.
- Etiquetas de estado comprensibles con color **y texto**; confirmación en operaciones destructivas.
- Toast y alertas accesibles, estados de espera/carga/error/vacío, indicadores de subida de archivo.
- Responsive real para 360 px y superior; tablet tratada con layout adaptado, no menús desbordados.

**Accesibilidad:** objetivo WCAG 2.2 nivel AA para funciones principales; formularios con `label`, uso de `aria-*` solo si corresponde, foco visible, navegación por teclado, contraste adecuado, iconos acompañados de etiquetas cuando la acción no sea evidente, respeto a `prefers-reduced-motion` y tablas accesibles.

---

## 12. SEGURIDAD, PRIVACIDAD Y PROTECCIÓN DE DATOS

Implementar seguridad **desde la primera fase**, no como parche final.

### 12.1 Seguridad de autenticación y sesiones

- `password_hash` con Argon2id cuando el entorno lo soporte o bcrypt configurado correctamente; `password_verify`; política de longitud, cambio y restablecimiento.
- Sesiones con `Secure`, `HttpOnly`, `SameSite` apropiado, regeneración de ID al iniciar sesión y elevar privilegios; vencimiento por inactividad, destrucción real al cerrar sesión.
- CSRF token en todas las operaciones con estado, incluida API con cookies; protección consistente para acciones Ajax.
- Rate limiting y protección contra fuerza bruta por cuenta y origen con umbrales configurables; registro de eventos.
- MFA TOTP opcional para roles privilegiados; plan de recuperación seguro y auditado.
- Recuperación de contraseña con tokens aleatorios de un solo uso, hash en BD y caducidad.

### 12.2 Autorización y multientidad

- Política de denegación predeterminada; validar permiso y alcance en **cada acción**, no confiar en controles visuales.
- Prevenir IDOR por acceso con IDs de otra institución, adjuntos privados y relaciones entre entidades.
- Nunca aceptar `institution_id` del formulario como criterio de confianza; derivar el contexto autenticado/autorizado y confirmar pertenencia.
- Separar sesión de superadministración, permisos de administrador institucional y usuarios depositantes.

### 12.3 Aplicación y subida de archivos

- SQL injection: PDO preparado siempre; identificadores SQL seleccionados solo de lista permitida.
- XSS: escape de salida, sanitización si hay campos enriquecidos, CSP compatible con las dependencias.
- Path traversal: claves de almacenamiento generadas y nunca rutas enviadas por cliente.
- Subidas: tamaño, tipo real, whitelist, antivirus cuando disponible, no ejecutar archivos, almacenar fuera de `public/`, servir con `Content-Disposition`, `Content-Type` y `X-Content-Type-Options: nosniff` apropiados.
- No aceptar rutas externas de usuario para importar archivos sin salvaguardias SSRF.
- Cabeceras HTTP de seguridad, HSTS al desplegar HTTPS, CORS restringido y política de enlaces externos.
- Protección ante CSRF, XSS, clickjacking, SSRF, IDOR, escalación de privilegios, enumeración de usuarios, inyección XML (XXE deshabilitado) y abuso de recursos.
- API y exportaciones con límites de consultas y archivos; tamaño máximo de paginación.

### 12.4 Protección de datos Perú

Tomar como referencia la **Ley N.º 29733** y su Reglamento aprobado por **D. S. N.º 016-2024-JUS**, vigente desde el 31 de marzo de 2025, así como modificaciones posteriores pertinentes. Diseñar para **minimización de datos, finalidad, transparencia, control de acceso, registros de tratamiento, conservación y atención de derechos**, según evaluación legal institucional.

- No publicar DNI, teléfono privado, correo privado, firmas, documentos de identidad de asesores/jurados, datos personales de estudiantes ni otros datos protegidos solo porque forman parte de un formulario interno.
- Separar campos internos de campos cosechables/exportables; revisión explícita antes de hacer pública información de personas.
- Metadatos específicos que exija un estándar pueden requerir registro interno sin exposición pública indiscriminada; validar finalidad y base jurídica.
- Política de privacidad institucional configurable y procedimiento de solicitudes del titular de datos.
- Logs y métricas con IP truncada, anonimizada o tratada de modo proporcional, según necesidad documentada y obligaciones aplicables.
- No habilitar analítica invasiva ni servicios de terceros por defecto.

### 12.5 Copias y secretos

- `.env`, respaldo, archivos privados, logs, archivos de configuración y claves **nunca** bajo ruta pública.
- Backups cifrados cuando su destino lo exija; acceso restringido; pruebas de restauración; procedimiento de rotación de claves.
- Secretos omitidos en capturas, logs, exportaciones y errores.
- Actualización de dependencias, revisión de vulnerabilidades y checklist de despliegue.

---

## 13. INTEGRACIONES EXTERNAS Y CARACTERÍSTICAS OPCIONALES

Implementarlas con contratos (interfaces/adaptadores), configuración, comprobación de conexión y manejo de errores. **No** fingir funcionamiento cuando no existen credenciales ni servicio.

| Integración | Prioridad | Comportamiento esperado |
|---|---|---|
| SMTP institucional | Alta | Notificaciones, recuperación de cuenta y pruebas de envío; cola y bitácora |
| OAI-PMH 2.0 | Alta | Servidor cosechable con estándares reales; público según política |
| Dublin Core / Guía ALICIA | Alta | Metadatos, normalización, validación y exportación |
| Lector PDF | Alta | Visualización solo si el archivo es accesible |
| Antivirus ClamAV | Media/alta | Análisis configurable y política explícita si no está disponible |
| Generación de miniaturas | Media | Imágenes previa autorización, colas y fallback |
| Extracción de texto / OCR | Media | Indexado opcional si existen herramientas; tareas reintentables |
| ORCID | Media | Validación/enlaces; autenticación oficial solo con integración autorizada |
| DOI / Handle | Condicional | Registro/consulta únicamente con proveedor habilitado |
| SSO OIDC/SAML | Futura | Inicio institucional opcional con validación segura de tokens |
| Almacenamiento S3 compatible | Futura | Adaptador para objetos privados y URLs temporales controladas |
| Sistemas académicos existentes | Futura | APIs autenticadas, alcance mínimo y sincronización segura |
| Exportaciones XLSX/PDF | Media | Librería pequeña reconocida y versiones compatibles |

No afirmar que la compatibilidad OAI-PMH o ALICIA constituye inscripción o certificación por CONCYTEC: el proceso real depende de verificación, requisitos y decisiones externas.

---

## 14. REPORTERÍA, TRAZABILIDAD Y MÉTRICAS

1. **Indicadores operativos:** documentos ingresados, aprobados, publicados, observados, rechazados, archivados; tiempo promedio entre depósito y publicación; pendientes por revisor.
2. **Indicadores académicos:** por programa, comunidad, colección, tipo documental, año, autor y línea de investigación, según los metadatos disponibles.
3. **Impacto público:** visitas a registros, descargas por archivo, documentos más consultados, procedencia general cuando sea proporcional, evolución temporal.
4. **Calidad:** registros incompletos, identificadores mal formados, adjuntos sin comprobar, metadatos desactualizados, duplicados candidatos, embargos pendientes.
5. **Capacidad:** consumo de almacenamiento, cuotas, crecimiento mensual, verificación de archivos, trabajos fallidos.
6. **Auditoría:** cambios de roles, publicaciones, retiros, revisiones, descargas restringidas, exportaciones sensibles, modificaciones de catálogos.

Ofrecer rangos de fechas, exportación, vistas imprimibles cuando corresponda y un aviso de que las estadísticas no equivalen automáticamente a impacto científico. El panel de red solo agrega datos permitidos, sin filtrar información privada de instituciones.

---

## 15. INSTALACIÓN, DESPLIEGUE Y OPERACIÓN

### 15.1 Instalador inicial

Proveer instrucciones para instalación manual y, de ser seguro y viable, asistente web limitado a instancia no configurada:

1. Comprobación de PHP, extensiones, directorios y permisos.
2. Configuración MySQL mediante secretos no mostrados después de guardar.
3. Ejecución de migraciones con reporte y manejo seguro de errores.
4. Creación de institución y superusuario local.
5. Configuración de dominio, HTTPS, SMTP opcional y almacenamiento privado.
6. Instalación de catálogos y permisos base.
7. Verificación final, **bloqueo irreversible del instalador por bandera de configuración y autorización administrativa**.

### 15.2 cPanel/WHM

Describir estructura cuando el document root es `public/`, alternativa segura si el hosting no lo permite, configuración de PHP, `open_basedir` cuando aplique, SSL, `.htaccess`, variables de entorno, MySQL, permisos mínimos de escritura, tareas cron y copia de seguridad. No recomendar mover `.env` o `storage/private` dentro de `public_html` sin controles sólidos equivalentes.

### 15.3 VPS Apache/Nginx

Virtual host, raíz `/public`, HTTPS, PHP-FPM, pool no privilegiado, límites de subida y ejecución, directorios de almacenamiento, propietario/grupo, cron, rotación de logs y parámetros de MySQL. Proveer ejemplos revisables de Apache y Nginx.

### 15.4 Operaciones CLI

Comandos reales y documentados, por ejemplo:

```bash
php bin/console migrate
php bin/console db:seed --class=BaseCatalogsSeeder
php bin/console queue:work --once
php bin/console scheduled:run
php bin/console repository:reindex
php bin/console repository:check-integrity
php bin/console backups:create
php bin/console backups:verify
php bin/console health:check
```

Adaptar a los comandos finalmente implementados; **no documentar como ejecutable un comando inexistente**. El cron debe impedir ejecuciones simultáneas incompatibles, registrar fallos y permitir reintentos.

### 15.5 Respaldo y restauración

Exportación transaccional/coherente de BD + archivos privados + configuración no secreta + manifiesto de hashes + versión de aplicación/esquema. Restauración probada en ambiente separado, verificación de integridad y registro de operador. Política configurable de retención y almacenamiento fuera del servidor principal cuando exista infraestructura. Nunca borrar respaldos válidos basándose solo en una supuesta ejecución exitosa.

### 15.6 Mantenimiento

Modo mantenimiento con exclusión de salud interna; herramientas de diagnóstico; logs estructurados; monitoreo de cron, correo, OAI, espacio y seguridad. Plan de actualizaciones con migraciones reversibles cuando sea posible y backup previo.

---

## 16. PRUEBAS OBLIGATORIAS Y CRITERIOS DE ACEPTACIÓN

Utilizar PHPUnit (se permite como dependencia **de desarrollo**) y pruebas de integración con MySQL. Si hay entorno disponible, añadir pruebas funcionales del navegador; usar un entorno de test aislado, nunca la base de producción.

### 16.1 Pruebas por niveles

- **Unitarias:** validadores, normalización de metadatos, políticas de acceso, transiciones, generador de slugs/identificadores, ORCID, licencias y embargo.
- **Integración:** PDO, migraciones/seeders, repositorios, transacciones, registros en colecciones, consistencia de versiones, índices y filtros.
- **Funcionales:** registro de administrador, login, CRUD, depósito completo, asignación/revisión, aprobación, publicación, búsqueda, citas, visor, descarga, retiro y recuperación.
- **Seguridad:** CSRF, XSS, SQLi, IDOR, elevación de permisos, archivos peligrosos, cookies, rate limits, dominios falsos y aislamiento entre institutos.
- **Interoperabilidad:** XML OAI-PMH, seis verbos, `resumptionToken`, cosecha por fecha, metadatos y exclusión de material privado.
- **Rendimiento:** búsqueda paginada, importación por lotes, descarga concurrente y consultas indexadas bajo volumen de prueba razonable.
- **Accesibilidad/responsive:** teclado, foco, formularios, lectura móvil, contraste, estados y orientación.
- **Restauración:** respaldar y recuperar institución de prueba, comparar recuentos e integridad de archivos.

### 16.2 Escenarios de aceptación mínimos

1. Una instalación vacía crea el instituto, catálogos y primer administrador sin pasos secretos ni SQL manual improvisado.
2. El administrador crea un programa, comunidad, colección y asigna revisores.
3. Un docente autorizado crea un borrador, completa metadatos y adjunta un PDF válido.
4. El envío incompleto muestra campos faltantes y no pasa a revisión.
5. Un revisor observa el documento; el docente corrige; el revisor aprueba y el responsable publica.
6. La ficha publicada aparece en búsqueda y muestra metadatos y archivo abierto autorizado.
7. El sistema deniega descargar un borrador, un archivo restringido o uno embargado desde URL directa, incluso con conocimiento del ID.
8. Al expirar el embargo, una tarea programada actualiza autorización sin duplicar eventos.
9. Los cambios de versión respetan políticas y mantienen trazabilidad y URL estable.
10. OAI-PMH devuelve registros públicos válidos y no filtra documentos privados o restringidos fuera de política.
11. En modo multientidad, usuario, revisor y administrador del instituto A no obtienen registros ni adjuntos de B.
12. Se genera reporte real y se exporta sin mostrar datos ajenos.
13. Una exportación bibliográfica con caracteres españoles conserva tildes, ñ y signos válidos.
14. Un PDF corrupto o formato falsificado se rechaza según política; se almacena fuera del directorio público.
15. Un backup del instituto de prueba se restaura en un entorno separado y los checksums concuerdan.
16. Un usuario suspendido ya no puede ejecutar acciones protegidas y los cambios de rol se auditan.
17. Una colección sin documentos muestra un estado vacío correcto, sin estadísticas inventadas.
18. Un usuario sin permiso no puede publicar ni aprobar invocando directamente la ruta HTTP.
19. La interfaz funciona con teclado, en móvil y en escritorio con errores de formulario accesibles.
20. Una instalación de producción no muestra stack traces, contraseñas, tokens, rutas internas ni detalles de SQL.

### 16.3 Definition of Done (por módulo)

Un módulo está terminado únicamente cuando cuenta con rutas, controles de acceso, validadores, controlador, servicio, acceso a BD, migración si requiere, vistas responsive, mensajes de error, pruebas relevantes y documentación mínima. Mostrar comprobación de que se ejecutó, registrar errores y corregirlos; no declarar éxito basándose solo en lectura visual del código.

---

## 17. PLAN DE CONSTRUCCIÓN POR FASES

**Trabaja en incrementos verticales funcionando de extremo a extremo**. No crear 60 tablas vacías y declarar el proyecto completo. Cada fase debe poder probarse antes de pasar a la siguiente.

| Fase | Prioridad | Entregables verificables |
|---|---|---|
| 0. Descubrimiento | Crítica | Revisión del repositorio, decisiones técnicas, mapa de requisitos, riesgos, modelo de dominio y roadmap |
| 1. Fundación | Crítica | Bootstrap, Router, Request/Response, vistas, autoload, `.env.example`, PDO, migraciones y errores |
| 2. Identidad | Crítica | Auth, sesiones, CSRF, roles/permisos, institución, aislamiento y administración inicial |
| 3. Organización | Crítica | Programas, comunidades, colecciones, catálogos, templates de metadatos y administración |
| 4. Depósito | Crítica | CRUD de ítems, autores, metadatos, archivos privados, borradores y validadores |
| 5. Workflow | Crítica | Envío, revisión, observación, aprobación, publicación, embargo y versiones |
| 6. Portal público | Crítica | Home, explorar, búsqueda, ficha, lector, descargas autorizadas, SEO básico |
| 7. Interoperabilidad | Alta | Dublin Core, validación de perfil ALICIA aplicable, OAI-PMH 2.0 y exportación bibliográfica |
| 8. Operación | Alta | Reportes, auditoría, notificaciones, cron, salud, backup y restauración |
| 9. Migración y calidad | Alta | Importación CSV, control de duplicados, integridad, pruebas de seguridad, accesibilidad |
| 10. Lanzamiento | Crítica | Documentación, instalador, despliegue cPanel/VPS, test de aceptación y hardening |
| 11. Extensiones | Opcional | OCR, antivirus externo, DOI/Handle oficial, SSO, S3, integraciones académicas avanzadas |

**Regla de prioridad:** construir primero un MVP real con institución + usuarios + permisos + colecciones + depósito + revisión + publicación + portal + archivos seguros. Luego completar compatibilidad académica, reporting y operación. No relegar permisos/privacidad al final.

### Formato de respuesta de la IA al finalizar cada fase

1. Resumen de funcionalidades implementadas (no promesas).
2. Árbol de archivos creados/modificados.
3. Migraciones/comandos y variables nuevas.
4. Pruebas ejecutadas, salida resumida y resultados; si no pudieron ejecutarse, explicar la limitación.
5. Instrucciones concretas para probar en navegador.
6. Riesgos pendientes, deuda técnica y siguiente fase.

---

## 18. PRODUCTOS Y DOCUMENTOS A ENTREGAR

Al término del proyecto deben existir, como mínimo:

- Código fuente completo, versionado, con historial comprensible y sin secretos.
- MySQL: migraciones ordenadas, seeders de roles/catálogos y diagrama entidad-relación.
- Panel administrativo y sitio público con todos los módulos principales.
- Modelo de permisos y alcance institucional documentado.
- Especificación de metadatos, diccionario de datos y mapeos DC/ALICIA.
- Endpoint OAI-PMH funcional y guía de cosecha, sin promesa de registro oficial.
- Documentación de rutas/API, PHPDoc en componentes complejos y manual de instalación.
- `.env.example` completo sin secretos y checklist de configuración.
- Manual del administrador, responsable del repositorio, revisor, docente/depositante y uso público.
- Guías cPanel y VPS con Apache/Nginx, SSL y cron.
- Plan de respaldo/restauración, logs y mantenimiento.
- Batería de pruebas reproducible, fixtures y reportes de verificación.
- `README.md` con instalación rápida, prerequisitos, comandos que realmente existen y capturas opcionales auténticas.
- `CHANGELOG.md`, `docs/DECISIONES.md`, `docs/MATRIZ_TRAZABILIDAD.md` (requisito → componente → prueba → estado).

---

## 19. CASOS DIFÍCILES QUE DEBES RESOLVER EXPLÍCITAMENTE

- Dos autores con mismo nombre: no fusionar identidades automáticamente; usar ID interno y ORCID validado si existe.
- Un mismo autor presenta obras distintas con idéntico título: no bloquear por título sin revisión.
- El depositante cambia de instituto: preservar autoría e historial; controlar membresías.
- Institución cambia de dominio: mantener identificadores y redirecciones 301 si está bajo control institucional.
- Se cambia el título: el permalink principal se mantiene.
- Documento publicado requiere corrección: nueva versión con política editorial y rastro auditable.
- Archivo restringido con miniatura/previsualización: ninguna transformación puede filtrar contenido.
- Archivo abierto pasa a restringido: invalidar cache, enlaces temporales y descargas públicas.
- Dos usuarios editan simultáneamente: bloqueo optimista por `updated_at` o `version` para evitar sobrescritura silenciosa.
- Falla la BD durante publicación: transacción y compensación de archivos; nada a medias.
- Falla antivirus/OCR/SMTP: la cola reintenta sin duplicar publicaciones o notificaciones; política configurable de cuarentena.
- Restauración: no mezclar respaldos de institutos ni reutilizar claves de manera insegura.
- Identificador Handle/DOI ausente: campo vacío y URI local válida, nunca un número ficticio.
- Documento con datos de DNI u otra información personal: separación entre captura interna, autorización para publicación y exportación; revisión de necesidad legal.
- Documento con acceso bajo embargo y metadatos publicados: motor de autorización resuelve archivo, tiempo, rol y permiso, no solo la vista.
- Reindexado por lotes: idempotencia, reanudación segura y límites de recursos.
- OAI `ListRecords` voluminoso: paginación estable y tokens firmados/verificados que expiran.
- Cambios de perfiles de metadatos: nuevos formularios no destruyen registros históricos.

---

## 20. CONVENCIONES DE CÓDIGO Y CALIDAD

- PHP con `declare(strict_types=1);` cuando resulte compatible; convenciones PSR-12 y autoload PSR-4.
- Clases y métodos con nombres expresivos; sin archivos gigantes tipo `functions.php` que acumulen toda la lógica.
- Separación consistente de entidad, repositorio, servicio, controlador, política y vista.
- Formularios con POST/PUT/PATCH/DELETE según router y método admitido; aplicar defensa CSRF.
- Respuestas HTTP correctas (`200`, `201`, `204`, `302`, `400`, `401`, `403`, `404`, `409`, `422`, `429`, `500`) y mensajes claros.
- `error_log` o logger controlado; no mostrar excepciones en producción.
- Validadores centralizados y documentación de restricciones de BD.
- Consultas SQL parametrizadas; white-list de campos dinámicos de orden/filtro.
- Componentes frontend reutilizables, JS modular y CSS por componente/página sin conflictos de nombres.
- No depender de conexión a Internet para el panel básico ni cargar fuentes externas de manera obligatoria.
- Mostrar fechas en español y zona `America/Lima` al usuario, almacenar instantes consistentes en UTC cuando proceda.
- Manejar nombres peruanos completos, tildes, eñes y apellidos compuestos sin alterarlos.
- Internacionalización preparada (al menos separación de textos de interfaz); prioridad español.
- `gitignore` excluye secretos, documentos privados, logs y respaldos.
- Si una funcionalidad depende de una herramienta de sistema no disponible en hosting compartido, ofrecer degradación explícita y documentada.

---

## 21. MATRIZ INICIAL DE CONFIGURACIÓN `.env.example`

Proveer valores ilustrativos seguros y documentación de cada variable. Ejemplo referencial (no guardar secretos reales):

```dotenv
APP_NAME="Repositorio Institucional"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=America/Lima
APP_LOCALE=es_PE
APP_KEY=
INSTALLATION_MODE=single
TENANCY_MODE=single
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=repositorio_institucional
DB_USERNAME=repo_user
DB_PASSWORD=
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=Lax
STORAGE_DRIVER=local_private
STORAGE_PRIVATE_PATH=/ruta/privada/fuera/de/public
UPLOAD_MAX_MB=50
UPLOAD_ALLOWED_TYPES=pdf,docx,xlsx,pptx,csv,jpg,png
QUEUE_DRIVER=database
MAIL_DRIVER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=no-responder@example.invalid
MAIL_FROM_NAME="Repositorio Institucional"
OAI_ENABLED=true
OAI_REPOSITORY_IDENTIFIER=
OAI_ADMIN_EMAIL=
OPEN_API_ENABLED=true
ANTIVIRUS_ENABLED=false
OCR_ENABLED=false
BACKUP_ENCRYPTION_KEY=
```

En producción exigir `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, cookie segura, APP_KEY aleatoria, ruta de almacenamiento privada real, SMTP o canal de notificación explícito y procedimiento para secretos. Las variables mostradas son **ejemplo de contrato**; deben coincidir exactamente con las implementadas.

---

## 22. FUENTES OFICIALES Y ESTÁNDARES PARA VALIDAR REQUISITOS

> Son referencias de diseño, **no una declaración de obligatoriedad uniforme** para todos los institutos ni una garantía de acreditación de la aplicación. Revisar versiones vigentes, ámbito material y posibles modificatorias antes de programar validaciones normativas.

1. **Ley N.º 30512 — Ley de Institutos y Escuelas de Educación Superior y de la Carrera Pública de sus Docentes (MINEDU):** https://www.gob.pe/institucion/minedu/normas-legales/118500-30512
2. **D. S. N.º 010-2017-MINEDU — Reglamento de la Ley N.º 30512 y sus modificatorias:** https://www.gob.pe/institucion/minedu/normas-legales/274628-010-2017-
3. **Portal MINEDU de normativa y licenciamiento** (revisar actualizaciones): https://minedu.gob.pe/superiortecnologica/procedimiento-licenciamiento.php
4. **CONCYTEC — Directrices RENARE, Guía ALICIA 2.1.0** (repositorio institucional oficial, revisar PDF y anexos actualizados): https://repositorio.concytec.gob.pe/handle/20.500.12390/3696
5. **Ley N.º 30035 — marco del Repositorio Nacional Digital de CTI de Acceso Abierto:** verificar texto y normas asociadas a través de CONCYTEC y normas oficiales; no suponer adhesión automática.
6. **OAI-PMH versión 2.0 — Open Archives Initiative:** https://www.openarchives.org/OAI/2.0/openarchivesprotocol.2002-08-06.htm
7. **Dublin Core Metadata Element Set — DCMI:** https://www.dublincore.org/specifications/dublin-core/dces/
8. **DCMI Metadata Terms — referencia técnica:** https://www.dublincore.org/specifications/dublin-core/dcmi-terms/
9. **Ley N.º 29733 y D. S. N.º 016-2024-JUS — protección de datos personales (ANPD):** https://www.gob.pe/institucion/anpd/normas-legales/6554453-16-2024-jus
10. **Información oficial sobre vigencia del nuevo reglamento de datos personales:** https://www.gob.pe/institucion/anpd/campa%C3%B1as/128319-nuevo-reglamento-de-proteccion-de-datos-personales
11. **Licencias Creative Commons:** https://creativecommons.org/share-your-work/cclicenses/
12. **WCAG 2.2 — W3C:** https://www.w3.org/TR/WCAG22/
13. **Guías OWASP ASVS y File Upload Cheat Sheet:** https://owasp.org/www-project-application-security-verification-standard/ y https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html

Cuando los perfiles académicos aplicables cambien, registrar la versión consultada, fecha, campos afectados, plan de migración y evidencia de pruebas. No derivar de forma automática obligaciones RENATI para todos los institutos.

---

## 23. ORDEN DIRECTA DE TRABAJO — COMIENZA AHORA

**No te limites a comentar el plan. Ejecuta la fase 0 e inmediatamente construye la fase 1**, salvo que el usuario pida explícitamente únicamente arquitectura. Continúa siguiendo las fases hasta tener un sistema completamente funcional dentro de los límites reales del entorno.

### Primeras acciones requeridas

1. Inspecciona archivos y versiones disponibles de PHP, Composer, MySQL, servidor web y extensiones.
2. Documenta en `docs/DECISIONES.md` que el sistema tiene **modo instituto individual por defecto** y **modelo de datos preparado para multientidad**, con aislamiento estricto.
3. Define arquitectura MVC, mapa de rutas inicial y diagrama de datos, evitando abstracciones vacías.
4. Crea base técnica funcional: autoload, bootstrap, router, PDO, migrador, manejo de errores, configuración, layouts y control básico de acceso.
5. Construye migraciones y seeders de instituciones, usuarios/membresías, roles, permisos, catálogos básicos, comunidades y colecciones.
6. Implementa login seguro, cierre de sesión, protección CSRF, autorización, panel administrativo inicial y portal público inicial.
7. Verifica de extremo a extremo en el entorno disponible y corrige errores.
8. Continúa con el depósito, revisión y publicación. **No abandones el trabajo tras crear el esqueleto.**

### Protocolo de comunicación

- Responde en español, con precisión y sin explicaciones innecesarias.
- Indica **hechos verificables**: qué archivo creaste, qué comando ejecutaste y qué resultado obtuvo.
- Si falta un servicio (SMTP, ClamAV, DOI, Handle, OCR), implementa la estructura preparada y **marca honestamente la integración como deshabilitada**, nunca como terminada.
- Si un requisito está fuera de las posibilidades del hosting, implementa la alternativa segura compatible y documenta la diferencia.
- Corrige fallos antes de pasar a la siguiente fase.
- Prioriza funcionalidad y seguridad sobre efectos visuales.
- Al cerrar el proyecto, entrega el checklist de aceptación con el estado de cada punto: `APROBADO`, `PENDIENTE` o `NO APLICA` y evidencia.

---

## 24. CRITERIO FINAL DE ÉXITO

Consideraré el proyecto **realmente completo** cuando pueda instalarlo en un instituto público peruano nuevo, cambiar su identidad visual y programas sin editar código, registrar usuarios con roles, crear comunidades y colecciones, depositar archivos y metadatos, someterlos a revisión, publicarlos, buscarlos, visualizarlos, descargarlos conforme a acceso, preservarlos, exportar metadatos, disponer de OAI-PMH verificable, consultar estadísticas, crear respaldos y restaurarlos; todo con separación de instituciones y controles de seguridad comprobados.

**Instrucción final:** desarrolla el producto paso a paso, escribiendo el código y verificando cada entregable. No sustituyas implementación por descripciones. No declares terminado algo que no hayas probado. No utilices Laravel ni otro framework PHP completo. Mantén PHP nativo MVC + MySQL como arquitectura oficial del proyecto.
