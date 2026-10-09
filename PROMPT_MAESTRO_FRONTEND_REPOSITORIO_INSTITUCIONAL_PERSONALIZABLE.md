# PROMPT MAESTRO — FRONTEND PREMIUM, RESPONSIVE Y PERSONALIZABLE PARA EL REPOSITORIO INSTITUCIONAL

> **Versión:** 1.0  
> **Fecha de referencia:** 08 de octubre de 2026  
> **Proyecto:** Repositorio Digital Institucional para institutos públicos del Perú  
> **Complemento de:** `PROMPT_MAESTRO_REPOSITORIO_INSTITUCIONAL_PHP_MVC_MYSQL_PERU.md`  
> **Tecnologías innegociables:** PHP nativo 8.3+ · MVC del proyecto existente · vistas PHP · HTML5 semántico · CSS3 modular · JavaScript nativo (ES modules) · MySQL 8.x · PDO.  
> **Idioma de toda la interfaz:** español del Perú (`es-PE`).  
> **Meta principal:** una experiencia visual excepcional, institucional y confiable, **personalizable íntegramente desde el panel de administración** sin editar archivos de código.

---

## 0. INSTRUCCIÓN PRINCIPAL: LEE ESTO ANTES DE MODIFICAR EL PROYECTO

Actúa simultáneamente como **director de diseño de producto, diseñador UX/UI sénior, especialista en design systems, ingeniero frontend experto, desarrollador PHP MVC, experto en accesibilidad WCAG 2.2 AA y QA visual**.

Tu tarea es **DISEÑAR Y CONSTRUIR** el frontend real y completo del repositorio institucional para cualquier instituto público del Perú, no presentar solamente capturas, propuestas conceptuales, wireframes, imágenes generadas, HTML aislado ni un dashboard sin funcionalidad.

**LEE PRIMERO** el prompt maestro del backend y **AUDITA EL PROYECTO EXISTENTE**: estructura de controladores, rutas, vistas, modelos, servicios, seguridad, autorización, entidades de institución, repositorio, usuarios, colecciones y documentos. Debes aprovechar lo que ya funciona sin reescribirlo injustificadamente ni romper contratos actuales. Este prompt **complementa y precisa el diseño visual**; ante contradicción técnica, conservar los invariantes de seguridad, negocio, aislamiento institucional y MVC del prompt principal.

### Reglas de ejecución no negociables

1. **El frontend será funcional, conectado a rutas y datos reales**, compatible con PHP MVC y MySQL actuales. No convertir el sistema a Laravel, React, Vue, Angular, Next.js, Tailwind como requisito, ni exigir Node.js en producción. Se permite una herramienta de compilación *solo si ya existe* y no condiciona el despliegue en cPanel/WHM o VPS.
2. HTML semántico y componentes reutilizables en **vistas parciales PHP**; CSS mantenible mediante variables, capas y componentes; JavaScript nativo modular para interacciones. Nada de CSS o JS gigantes insertados en cada plantilla.
3. El **administrador de cada instituto** debe poder personalizar colores, logo, favicon, nombre, cabecera, portada, textos, tipografía entre opciones permitidas, menú, elementos del inicio y pie de página mediante formularios, **sin modificar PHP, CSS ni JavaScript**.
4. Soportar **un instituto por instalación** y una arquitectura **multiinstitución**: cada instituto tiene tema, logos, banners y textos independientes. Jamás dejar que editar una institución altere otra.
5. Mantener el servicio perfectamente utilizable si el administrador todavía **no cargó logo, favicon, portada ni imágenes**: generar alternativas neutras, iniciales y gráficos propios ligeros; no inventar imágenes ni datos institucionales.
6. Respetar roles y permisos existentes. Mostrar navegación, botones, contadores y acciones **solo según autorización real**; ocultar controles no sustituye la validación del backend.
7. Implementar **todos los estados de cada pantalla**: carga, vacío, sin resultados, error, validación, éxito, acceso denegado, sesión vencida, operación en curso y conectividad fallida donde corresponda. **Nunca botones decorativos sin comportamiento**.
8. El diseño debe ser **de nivel producción**, sobrio, contemporáneo, de alta calidad visual y centrado en recuperación de contenido académico; no parecer una plantilla genérica escolar ni un diseño saturado de colores.
9. Trabajar **por fases completas y comprobables**. No interrumpir indefinidamente en el diseño del inicio; construir también administración, depósito, revisión, perfil y configurador de marca.
10. No poner identificadores, logos ni colores propios de un instituto de forma fija; toda identidad deberá surgir de la configuración vigente del instituto en cuestión.

### Orden de prioridad

**1. Seguridad y aislamiento > 2. Funcionalidad real > 3. Accesibilidad y usabilidad > 4. Coherencia de marca > 5. Rendimiento > 6. Refinamiento visual.** La estética jamás debe comprometer datos, accesos, legibilidad o rendimiento.

---

## 1. OBJETIVO DEL PRODUCTO Y SENSACIÓN VISUAL

Diseñar un repositorio que, al ingresar, transmita:

- **Prestigio institucional:** apropiado para un instituto de educación superior tecnológica o pedagógica del Perú.
- **Modernidad:** tipografía cuidada, jerarquía visual precisa, espacios generosos, animaciones discretas, interfaces limpias, bordes y sombras controladas.
- **Orden académico:** acceso rápido a documentos, programas de estudios, autores, comunidades y colecciones.
- **Facilidad de uso:** cualquier docente, bibliotecario, administrador, estudiante o visitante debe localizar la acción que necesita en pocos pasos.
- **Confianza:** avisos de acceso, licencias, datos de autores y archivos presentados sin ambigüedades; ningún documento privado se muestra por error.
- **Adaptación institucional:** visualmente distinto para cada instituto sin perder la calidad y consistencia de la plataforma.

### Dirección artística obligatoria

Inspiración conceptual: **portal universitario contemporáneo + biblioteca digital de investigación + SaaS administrativo de alta calidad**. No copiar el diseño, logo o marca registrada de ninguna institución o software externo.

**Lenguaje visual:** fondo predominantemente blanco y grises ligeramente fríos; identidad cromática como acento estratégico; tipografía de alta legibilidad; títulos compactos y jerárquicos; tarjetas con bordes delicados; separaciones de 8/12/16/24/32/48/64 px según escala; iconos lineales coherentes; animaciones sobrias; elementos interactivos con estados perceptibles. Usar gradientes suaves únicamente cuando aporten jerarquía.

**Evitar:** encabezados gigantes, carruseles automáticos que distraigan, gradientes fluorescentes, sombras exageradas, texto alineado en bloque en tarjetas, más de dos colores de acento simultáneos, campos diminutos, tablas incontrolables, menús multinivel que tapen contenido, iconos sin significado, microtextos ilegibles, contenido repetitivo ficticio y aspecto «dashboard plantilla» sin carácter.

### Ejemplo conceptual del resultado

- Encabezado limpio con logo y nombre oficial; barra contextual opcional superior; navegación que se adapta sin romperse.
- Inicio con **hero editorial**: título de repositorio, frase breve configurable, buscador poderoso centrado y accesos rápidos.
- Estadísticas discretas en tarjetas (solo datos reales) y bloques de publicaciones, colecciones y programas.
- Resultados de búsqueda organizados como fichas académicas, filtros rápidos visibles, panel lateral útil en escritorio.
- Ficha de recurso similar a una publicación académica profesional: título, autoría, tipo, fecha, resumen, metadatos, botones de citar y archivos con controles correctos.
- Administración con navegación lateral estable, barra superior concisa, formularios impecables, listas filtrables y estados claramente distinguibles.
- **Constructor de apariencia** con paleta de colores, logo, favicon, imágenes, opciones de composición y vista previa de escritorio/móvil.

---

## 2. PERSONALIZACIÓN TOTAL DE IDENTIDAD POR INSTITUTO — MÓDULO OBLIGATORIO

Implementar en el panel:

**Configuración → Apariencia e identidad institucional**

Dividir el configurador en las siguientes pestañas o secciones, con guardado independiente y opción final de publicar:

| Sección | Ajustes que deben poder configurarse |
|---|---|
| Identidad | Nombre oficial, nombre corto, sigla, lema, descripción resumida y pie legal editable |
| Logotipos | Logo principal, logo alternativo para fondos oscuros, versión compacta, escudo opcional; previsualización y eliminación controlada |
| Favicon | Favicon PNG/ICO, icono para dispositivos, imagen de compartir social opcional; vista previa de pestaña |
| Colores | Primario, secundario, acento, texto principal, texto secundario, fondos, superficies, borde, cabecera, sidebar y pie |
| Tipografía | Familia entre opciones seguras, escala compacta/normal/amplia, espaciado y altura de línea dentro de límites accesibles |
| Cabecera | Altura/versión, alineación del logo, nombre visible, barra superior opcional, estilo de navegación y botón principal |
| Inicio | Título y subtítulo, textos introductorios, hero, imagen/ilustración de portada, orden y visibilidad de secciones |
| Colecciones | Imagen predeterminada, estilos de tarjetas, número de columnas según viewport, elementos destacados |
| Pie | Fondo, logo, datos de contacto, correo, dirección, redes, enlaces legales, créditos institucionales |
| Componentes | Radio de borde, densidad de interfaz, estilo de botones, sombra sutil y longitud de animaciones permitidas |
| Previsualizar/Publicar | Vista previa en escritorio/tablet/móvil, comprobar contraste, guardar borrador, publicar, restaurar |

**Regla:** «Personalizable» no significa permitir CSS/HTML/JS arbitrario que cause XSS, rompa accesibilidad o interfiera con otras instituciones. Construye una **configuración tipada y validada de tokens y opciones**.

### 2.1 Colores editables desde controles visuales

El administrador debe contar con:

1. Selector de color accesible para cada token personalizable.
2. Campo HEX asociado, sincronizado en vivo con el selector.
3. Botón **Restablecer color** a valor predeterminado por token.
4. Botón **Restablecer paleta** completa (con confirmación).
5. Selector de **paletas predefinidas** opcionales como punto de partida, nunca como restricción.
6. Cambios en vista previa **sin recargar la página** y sin publicar accidentalmente.
7. Mensajes de contraste si una combinación dificulta la lectura; ofrecer un color de texto contrastante derivado en el servidor cuando corresponda.
8. Gestión consistente de hover, focus, disabled, selected, alert y fondos, evitando que el usuario tenga que configurar decenas de valores técnicos.

**Paletas iniciales de demostración, no obligatorias** (editar libremente):

| Nombre | Primario | Secundario | Acento |
|---|---|---|---|
| Azul institucional | `#113B70` | `#2873BD` | `#E4AE35` |
| Verde académico | `#145A42` | `#238263` | `#D9AE50` |
| Granate académico | `#702C44` | `#9E4B67` | `#DCA64F` |
| Azul petróleo | `#12485B` | `#1C7892` | `#D5A447` |
| Morado institucional | `#472E75` | `#7659AA` | `#E5B64C` |

Cada paleta es **solo una semilla editable**. Nunca presumir que los colores reales de un instituto se corresponden con una paleta determinada.

### 2.2 Tokens CSS obligatorios

Unificar en un solo sistema de variables. **No usar valores de color de marca repetidos en componentes**.

```css
/* Ejemplo de contrato de tokens; los valores finales provienen del tema validado. */
:root {
  --brand-primary: #113b70;
  --brand-secondary: #2873bd;
  --brand-accent: #e4ae35;
  --brand-on-primary: #ffffff;
  --brand-on-secondary: #ffffff;
  --brand-on-accent: #172334;

  --page-bg: #f6f8fc;
  --surface: #ffffff;
  --surface-subtle: #f1f5fa;
  --text: #152438;
  --text-muted: #5b6a7c;
  --border: #dce4ee;
  --focus-ring: #1b64c2;

  --header-bg: #ffffff;
  --header-text: #152438;
  --sidebar-bg: #102d52;
  --sidebar-text: #f6f8fc;
  --footer-bg: #102d52;
  --footer-text: #f6f8fc;

  --success: #14794b;
  --warning: #9a6515;
  --danger: #b42318;
  --info: #205b95;

  --font-sans: system-ui, -apple-system, "Segoe UI", sans-serif;
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 18px;
  --shadow-card: 0 8px 28px rgb(18 42 74 / 6%);
  --container-max: 1240px;
  --header-height: 76px;
  --motion-fast: 150ms;
  --motion-base: 220ms;
}
```

Estos son **valores de ejemplo**. El backend toma una configuración aprobada de la institución y sirve tokens CSS **escapados y restringidos**. Los tokens de estados semánticos (`success`, `warning`, `danger`) deben preservar su significado y contraste aunque cambie la marca. Definir `on-*` legibles por cálculo/verificación, no asumir que texto blanco sirve para cualquier color.

### 2.3 Logotipo configurable: tratamiento profesional

Permitir al menos:

- Subir **logo principal** (PNG, JPEG, WebP; SVG únicamente si existe un proceso seguro de sanitización estricta y una política explícita, en caso contrario rechazar SVG).
- Subir **logo alternativo** claro u oscuro para distintas superficies.
- Subir **logo compacto** para cabeceras estrechas, sidebar colapsado y móviles.
- Vista previa sobre fondos claros y oscuros.
- Control del tamaño de visualización **dentro de rangos permitidos**: alto máximo, alineación horizontal y separación con el nombre.
- Opciones «mostrar logo y nombre», «solo logo» o «logo + sigla», verificando que el nombre institucional siga disponible como texto accesible.
- Conservación de proporción con `object-fit: contain`; jamás estirar, recortar el escudo o deformar la imagen.
- Sustitución segura con actualización de metadatos de archivo y cache busting basado en versión.
- Fallback elegante con iniciales institucionales y nombre en texto cuando falte logo.
- Texto alternativo apropiado: cuando el logotipo está junto al nombre institucional, evitar duplicación innecesaria; cuando es enlace único, proporcionar nombre accesible.

**Condiciones de subida:** `multipart/form-data`, comprobación MIME real en servidor, tamaño máximo configurable, límite de dimensiones/píxeles, nombres aleatorios del servidor, almacenamiento seguro y publicación de derivados verificados. No confiar en extensiones, atributos del navegador ni rutas remitidas por el usuario. Evitar SVG activo, scripts embebidos, metadatos dañinos y referencias remotas.

### 2.4 Favicon y símbolos del navegador

El panel debe aceptar **PNG cuadrado de calidad y/o ICO válido**, previsualizar, validar y generar/seleccionar las dimensiones adecuadas cuando el entorno permita procesar imágenes. Contemplar `favicon.ico`, variantes PNG, `apple-touch-icon` y `manifest.webmanifest` cuando se implemente soporte de instalación. Si no hay imagen, generar favicon sobrio con iniciales o símbolo genérico no engañoso.

Renderizar en `<head>` los iconos propios **de la institución activa**, con URL versionada; no un favicon global compartido por error. Mostrar vista previa tipo pestaña del navegador y comprobar respuesta HTTP, MIME, tamaño y caché. No reutilizar el favicon de la organización administradora salvo consentimiento y configuración explícita.

### 2.5 Banner y hero personalizables

Permitir subir imagen de portada opcional con recorte previsualizado y punto focal; compresión y versiones responsivas al existir soporte. Añadir controles:

- Modo hero: **color sólido / gradiente de marca / imagen institucional**.
- Posición focal configurable (centro, izquierda, derecha o posición porcentual segura).
- Superposición oscura/clara automática para asegurar que el título sea legible.
- Título, subtítulo, frase principal y texto complementario editables; limitar longitudes razonables.
- Mostrar/ocultar estadísticas, colecciones destacadas, publicaciones recientes, programas y llamada a depósito autorizada.
- Elegir orden de bloques del inicio mediante lista accesible (botones «subir/bajar» como alternativa a drag & drop).
- Previsualizar escritorio, tablet y móvil antes de publicar.

No cargar imágenes de dimensiones originales enormes en móvil. Si falla la imagen, volver a un hero de color institucional sin romper la interfaz.

### 2.6 Personalización por institución y aislamiento

Toda configuración visual estará asociada a `institution_id`. Un instituto puede tener azul/dorado y otro verde/crema **en la misma aplicación**. Aislar cachés, vistas previas, rutas de logos, CSS servido y configuraciones. Al cambiar de instituto, actualizar correctamente título, favicon, variables, logo, hero, enlaces y permisos. Probar expresamente que un usuario del instituto A **no puede** cambiar ni previsualizar el borrador privado del B.

---

## 3. CONSTRUCTOR DE APARIENCIA: EXPERIENCIA ADMINISTRATIVA REAL

Desarrollar un configurador **premium, sencillo y visual**, no solo un formulario de 30 campos.

### 3.1 Diseño del configurador

**Desktop (ideal):**

- Columna lateral de ajustes: identidad / imágenes / paleta / tipografía / cabecera / portada / pie.
- Zona principal con vista previa en vivo del portal público.
- Barra superior fija con «Cambios sin guardar», «Guardar borrador», «Vista previa», «Publicar cambios» y «Restaurar versión».
- Selector de dispositivo: **escritorio, tablet, móvil**; el marco de previsualización no debe inducir desbordamiento horizontal de la página.
- Acciones de **deshacer/revertir** y restablecer valores por categoría si es viable; al menos mantener un estado de borrador y versión publicada.

**Móvil:** ajustes en acordeones/pestañas; vista previa en contenedor independiente; nunca superposición que impida guardar.

### 3.2 Flujo de edición y publicación

1. Cargar configuración **publicada** desde BD.
2. Crear/editar **borrador** separado sin afectar el portal visible a visitantes.
3. Previsualizar cambios inmediatos en un contexto **autorizado**, aislado del portal público; no exponer el borrador mediante URLs adivinables.
4. Validar tipos, rangos, archivos y contraste; bloquear solo configuraciones que impidan el uso básico, explicar cómo resolverlo.
5. Guardar borrador con CSRF, validaciones PHP y auditoría.
6. Publicar con confirmación, transacción, fecha/autor de versión e invalidación de cachés **de esa institución**.
7. Ver historial corto de versiones publicadas y **restaurar** una anterior, si el esquema lo soporta, con auditoría.
8. Tras publicar, comprobar logo, favicon, hero, color de botones y nav en portal real.

No publicar cambios por tocar un color. No almacenar texto o scripts arbitrarios en variables CSS. Si el esquema del backend carece de historial, implementar la migración mínima necesaria sin destruir tablas existentes.

### 3.3 Contratos de almacenamiento recomendados

Respetar nomenclatura real del proyecto; si no existe esquema de temas, proponer e implementar migraciones equivalentes:

- `institution_themes`: `id`, `institution_id`, `version`, `status` (`draft|published|archived`), `tokens_json` validado, `layout_json` validado, `texts_json` validado, `created_by`, `published_by`, `created_at`, `published_at`, `updated_at`.
- `institution_brand_assets`: `id`, `institution_id`, `kind` (`logo_primary|logo_alternate|logo_compact|favicon|hero|default_cover|social_image`), `storage_key`, `mime_type`, `width`, `height`, `size_bytes`, `sha256`, `created_by`, `created_at`.
- Si las tablas institucionales ya guardan logos o ajustes, **reutilizar y migrar de forma compatible**, sin duplicación inconsistente.
- Un solo tema **publicado** activo por institución; estrategia transaccional/índice adecuado para no tener dos versiones activas.
- **Datos de temas tipados y con esquema fijo**, jamás eval/ejecución de CSS o HTML del administrador.

**Contratos de salida:** los controladores exponen a la vista un `ThemeViewModel` o estructura equivalente, con tokens ya validados, configuración pública permitida, URLs de activos seguras y valores por defecto. No imprimir JSON crudo sin el escape apropiado al contexto HTML/JS.

---

## 4. DESIGN SYSTEM DE CALIDAD PROFESIONAL

Crear **una sola biblioteca visual** compartida por portal, login y administración, con variantes contextuales claras.

### 4.1 Fundamentos

- Escala de espaciado y tipografía uniforme; tamaños recomendados con `clamp()` cuando sea útil.
- Contenedor público típico de `max-width: 1200–1280px` (ajustable por layout, no por instituto arbitrariamente).
- Vistas administrativas aprovechando el ancho disponible sin generar líneas de texto excesivas.
- Sistemas de grillas de 12 columnas conceptuales o CSS Grid/Flexbox; sin framework necesario.
- Tipografías locales/web seguras: `system-ui`, `Segoe UI`, Arial; si se usan fuentes adicionales, alojarlas con licencias pertinentes y fallback funcional, evitando dependencias CDN obligatorias.
- Tamaños mínimos legibles, line-height cómodo, texto no justificado forzadamente.
- Paleta institucional aplicada a enlaces, CTAs, indicadores y secciones; superficies de contenido mayormente neutras.
- Iconos de trazo homogéneo. Si se utiliza un set ligero, alojarlo localmente, comprobar licencia, y acompañar con textos accesibles.
- Bordes discretos, sombras suaves y estado hover predecible.

### 4.2 Componentes reutilizables que debes construir

| Componente | Variantes / comportamientos |
|---|---|
| Botón | Primary, secondary, outline, subtle, danger, icon; loading, disabled, focus |
| Campo de texto | Label visible, hint, requerido, contador cuando corresponda, error contextual |
| Select / combobox | Opciones locales/remotas existentes, teclado, mensaje sin resultados |
| Radio / checkbox / switch | Semántica accesible, estado explícito, agrupación clara |
| Fecha | Entrada nativa robusta, formato visual local y validación en servidor |
| File uploader | Dropzone opcional + selector normal; progreso, nombre, tamaño, error, remover |
| Tarjeta académica | Tipo, título, autor, fecha, acceso, palabras clave, acciones permitidas |
| Tarjeta de colección | Nombre, descripción breve, imagen opcional, cantidad real de recursos |
| Tarjeta KPI | Icono discreto, número, etiqueta, contexto, estado vacío |
| Breadcrumb | Jerarquía clara, colapsable en móvil, `aria-label` |
| Tabs | Navegables con teclado; no usar pestañas para ocultar información crucial |
| Accordion | Nativo o accesible; apertura/cierre y persistencia si procede |
| Modal / confirmación | Foco contenido, Escape, devolución de foco y acciones concretas |
| Drawer móvil | Foco gestionado, fondo controlado, salida accesible, sin scroll perdido |
| Alert / toast | Éxito, error, aviso; `role=alert/status` coherente; no desaparecer antes de poder leer |
| Badge | Estado con texto y color semántico, no solo color |
| Tabla | Encabezados, paginación real, filtros, acciones, overflow adaptable |
| Paginador | Enlaces de páginas válidos, actual, primero/último, teclado |
| Skeleton | Coincide con estructura real y respeta movimiento reducido |
| Empty state | Mensaje útil, orientación y CTA según permisos |
| Tooltip | Solo complementario; no esconder instrucciones necesarias detrás de hover |
| Gráfico | Leyenda y descripción o tabla alternativa; datos reales |
| Tema | Preview, guardado de borrador, publicar, verificación de accesibilidad |

### 4.3 Contrato de estados

Todo botón debe tener acciones reales; todo enlace debe dirigir a una ruta correcta; un usuario sin permiso no debe ver acciones que fallarán sin explicación. Estados mínimos: `default`, `hover`, `focus-visible`, `active`, `disabled`, `loading`, `success`, `error`. Las tablas, gráficos y tarjetas deben incluir tratamiento de **cero registros** y de **fallo del servidor**.

### 4.4 Textos UI

Usar microcopy claro en español peruano: «Buscar documentos», «Explorar colecciones», «Subir documento», «Guardar borrador», «Enviar a revisión», «Volver a resultados», «Copiar cita», «Descargar archivo», «Publicar cambios», «Restaurar apariencia». Evitar tecnicismos al visitante; para roles académicos usar las denominaciones exactas del proceso de depósito.

---

## 5. PORTAL PÚBLICO: PÁGINAS OBLIGATORIAS

Construir un portal público **completo**, enlazado con el repositorio real, para visitantes sin iniciar sesión.

### 5.1 Inicio `/`

Orden visual sugerido — editable por institución sin comprometer navegación:

1. **Barra superior opcional:** enlace al portal institucional principal, datos discretos o enlaces útiles.
2. **Cabecera:** logo correcto, nombre oficial/sigla, navegación principal y botón «Iniciar sesión»; menú móvil real.
3. **Hero** según tema: etiqueta «Repositorio Institucional», título personalizable, subtítulo de utilidad, buscador central destacado; imagen opcional que no quite legibilidad.
4. **Búsqueda principal:** input prominente, botón claro y chips útiles como «Todos», «Investigación», «Proyectos», «Material académico» únicamente si hay categorías reales.
5. **Indicadores públicos:** total de recursos publicados, colecciones, comunidades, vistas/descargas si están disponibles y autorizadas. Números reales; no inventar «+10,000».
6. **Explorar por comunidad y colección:** tarjetas atractivas; iconos/portadas limpias; nombre, descripción y cantidad real.
7. **Publicaciones recientes:** cards con metadatos relevantes y etiquetas de acceso.
8. **Programas de estudios:** mostrarlos cuando estén registrados y tengan utilidad navegable.
9. **Bloque editorial opcional:** por qué consultar o depositar, solo con texto institucional configurado.
10. **Pie de página completo:** logo/identidad, contacto y políticas configuradas, enlaces válidos.

**UX:** primera pantalla prioriza el buscador. La foto o banner no debe impedir reconocer la función del sitio. No hacer carrusel automático; si se agrega slider por petición posterior, controles accesibles y sin autoplay por defecto.

### 5.2 Buscar `/buscar`

- Barra de búsqueda persistente, filtros por tipo, año, programa, comunidad, colección, autor, idioma, materias y acceso; mostrar solo facetas disponibles.
- Cabecera «Resultados para “...”» con cantidad **real** y opción «Limpiar filtros».
- Desktop: columna de filtros de ancho apropiado + columna de resultados; móviles: botón «Filtros» que abre drawer accesible con aplicar/restablecer.
- Orden por relevancia, fecha, título y otros permitidos por el backend.
- Alternar **lista y cuadrícula** si beneficia; por defecto lista académica cuando el documento tiene mucho metadato.
- En cada resultado: tipo, título legible y clicable, autores, año, programa/colección, extracto del resumen limitado, etiquetas de acceso, acceso a ficha. Nunca ofrecer una descarga de material privado.
- Mantener búsqueda, filtros y ordenamiento en URL y durante paginación; marcar controles activos.
- Página «No encontramos resultados» con sugerencias reales y enlace a limpiar filtros; no mostrar publicaciones inventadas.
- Al escribir, **debounce** si se implementa sugerencia remota; soporte teclado; no exigir JS para búsqueda básica.

### 5.3 Búsqueda avanzada

Secciones claras de filtros combinables, campos y operadores respaldados por el backend. Etiquetas explicativas («Año desde», «Año hasta», «Autor», «Palabras clave»). Validación de fechas y filtros; persistencia de parámetros y botón limpiar.

### 5.4 Explorar comunidades, colecciones, programas, autores y temas

Pantallas con encabezado, breve descripción, breadcrumb, búsqueda local si procede, cards uniformes, conteos reales y paginación. Permitir colecciones anidadas con navegación comprensible. No usar iconos aleatorios ni imágenes tomadas de bancos externos sin licencia.

### 5.5 Ficha pública del recurso

**Pantalla prioritaria del producto.** Distribución sugerida:

- Breadcrumb de institución > comunidad > colección > documento.
- Cabecera editorial: tipo documental, acceso, título completo, autoría, año, programa/colección.
- Área central: resumen completo legible, palabras clave enlazadas, información académica, identificación persistente, versión publicada y licencia.
- Panel contextual (lateral en escritorio, bloque en móvil): archivos autorizados, tamaño, formato, «Ver PDF», «Descargar», cita y exportaciones **solo cuando aplican**.
- Sección «Cómo citar»: vista previa de cita, botón copiar, formatos APA/BibTeX/RIS respaldados; aviso si faltan datos para formar una cita adecuada.
- Metadatos adicionales expandibles de forma accesible.
- Relacionados bajo consulta real; no sugerencias ficticias.
- Registro restringido/embargado: etiqueta y explicación correctas; **jamás simular botón de descarga**.

**El bloque de archivos** debe diferenciar `metadata_only`, `embargoed`, `restricted`, `private` y `open` conforme al backend. La posibilidad de visualizar PDF no equivale a poder descargarlo; servidor siempre valida ambos permisos.

### 5.6 Lector integrado de PDF

- Encabezado con título, navegación de regreso y acciones autorizadas.
- Visor embebido o solución local apropiada que respete CSP, permisos y tipos MIME. Si el navegador no puede verlo, mostrar mensaje y alternativa legal.
- No utilizar una URL pública directa para archivos protegidos. Los bytes deben pasar por la ruta controlada y autorizada.
- No prometer controles de impresión/descarga que impidan realmente copiar un PDF: las restricciones efectivas son del servidor.
- Pantalla de error digna cuando archivo no disponible, permiso revocado o sesión vencida.

### 5.7 Estadísticas y páginas informativas

Diseñar páginas de estadísticas públicas agregadas, acerca de, políticas, privacidad, contacto y preguntas frecuentes según datos y permisos existentes. Los gráficos requieren cifras auténticas y alternativa tabular. No inventar datos, rankings o acreditaciones.

### 5.8 SEO y vista al compartir

Usar título dinámico «Página | Repositorio - Instituto», favicon, Open Graph con imagen institucional verificada, descripción por recurso, URL canónica y datos estructurados cuando aplican; metadatos seguros y escapados. Robots y sitemap **solo** para recursos publicados/autorizados; jamás incluir rutas de vistas previas privadas ni borradores de tema.

---

## 6. AUTENTICACIÓN: PANTALLA DE LOGIN DE ALTO NIVEL

Diseñar login institucional profesional y ligero:

- En escritorio, composición en dos columnas **solo si aporta**: panel de marca con logo, ilustración/fondo institucional configurable y panel de formulario; en móvil, una columna sin imágenes dominantes.
- Logo y nombre del instituto activo, favicon correcto y frase discreta.
- Campos con etiquetas visibles, autocompletar adecuado, botón ver/ocultar contraseña con nombre accesible, CSRF, errores genéricos seguros.
- Enlace de recuperación si el backend soporta ese flujo; estados de sesión vencida y cuenta deshabilitada.
- No mostrar cuenta «demo», contraseñas o credenciales de prueba en producción.
- No alterar la autenticación real existente ni crear rutas anónimas alternativas para funciones protegidas.

---

## 7. PANEL ADMINISTRATIVO: EXPERIENCIA PREMIUM POR ROLES

### 7.1 Estructura visual general

- **Sidebar** con marca compacta, agrupación lógica de navegación, iconos consistentes, texto claro, ruta activa, opción colapsar; responsive con drawer correcto en móvil.
- **Topbar** con título contextual, breadcrumbs, selector de institución **solo cuando el usuario tiene permisos multientidad**, notificaciones reales, cuenta y menú de perfil.
- Área principal con `max-width` apropiado, títulos compactos, subtítulos explicativos, acciones primarias a la derecha, grillas y tablas responsivas.
- El panel puede mantener superficies blancas sobre fondo gris suave, usando los colores institucionales en acciones y estados seleccionados.
- Enlaces/paneles por rol, derivados del control de autorización, no por nombres escritos de roles en CSS/JS.
- Sidebar en pantallas largas sin tapar pie; evitar `position: fixed` que cause problemas en móviles/tablets.

### 7.2 Dashboard principal

Mostrar lo pertinente al rol:

- Total de registros visibles para ese usuario, borradores, enviados, pendientes de revisión, observados, aprobados, publicados y embargados.
- Tendencia de depósitos y consultas/descargas cuando hay datos reales.
- Actividad reciente y cola de pendientes con responsables/fechas.
- Acciones rápidas: «Nuevo depósito», «Mis documentos», «Revisiones pendientes», «Administrar colecciones», «Apariencia» según permisos.
- Avisos de almacenamiento, validaciones pendientes y tareas de atención si existen.
- Gráficos sin saturación ni animaciones superfluas; acompañar de tabla o explicación numérica.
- Un nuevo instituto con cero recursos debe mostrar **onboarding útil** en lugar de ocho tarjetas con ceros sin orientación.

### 7.3 Tabla maestra de documentos

- Buscar, filtrar por tipo, colección, programa, estado, fechas, autor y responsable según permisos.
- Encabezados claros, filas cómodas, acciones en menú contextual accesible y botón principal visible.
- Columnas adaptables a móvil: preferir cards de fila o scroll horizontal controlado con encabezados perceptibles.
- Filtros sincronizados con URL cuando sea útil; paginación real del backend, indicador de total, filas por página dentro de límites.
- Selección masiva autorizada, confirmación previa, resultado por lote, gestión de errores parciales.

### 7.4 Depósito guiado de documentos — máxima calidad UX

Implementar un **asistente de 6 pasos** integrado al flujo real:

1. Comunidad, colección y tipo de documento.
2. Información general, título y autores/coautores (reordenables por botones accesibles).
3. Metadatos académicos adicionales según plantilla.
4. Acceso, licencias y derechos, embargo cuando aplica.
5. Subida de archivos, verificación y archivo principal.
6. Vista previa, resumen de datos, errores por corregir y envío a revisión.

**Exigencias visuales y funcionales:**

- Stepper visible con etapa actual/completada y etiquetas.
- Sección de ayuda contextual para campos complejos, sin sobrecargar el formulario.
- Validación **junto a cada campo** y resumen superior al intentar continuar.
- Guardado de borrador y recuperación respaldados por el backend; indicar cuándo se guardó, sin afirmar «Guardado» antes de respuesta exitosa.
- Prevención de pérdida accidental de datos al salir, sin bloquear navegación de forma intrusiva.
- Autores repetibles, etiquetas claras, teclado completo, respuesta a errores del servidor.
- Archivo PDF visualizable para usuario autorizado antes de envío; no exponerlo públicamente.
- Progreso por archivo cuando hay soporte real; fallos de tamaño y formato tratados claramente.
- Botones «Anterior», «Guardar borrador» y «Continuar», y «Enviar a revisión» solo cuando corresponda.

### 7.5 Revisión y aprobación

Interfaz en dos paneles o pestañas: información del documento + controles editoriales. Ver metadatos y adjuntos **según permisos**, observaciones de campo, historial y resultado de validaciones. Acciones coherentes: asignar, observar, devolver, aprobar, rechazar y publicar **solo** si el backend y el rol las permiten. Confirmaciones con detalle y mensajes que identifiquen el estado posterior.

### 7.6 Administración de comunidades y colecciones

CRUD visual de jerarquías con icono/portada, nombre, descripción, responsable, cantidad de registros y opciones de orden; breadcrumb para navegar niveles. Formularios simples con validación; precaución si se elimina/archiva una colección con recursos existentes. No usar drag-and-drop como único mecanismo de ordenamiento.

### 7.7 Programas, docentes, autores, usuarios, roles

- Formularios consistentes, validaciones, búsquedas y filtros.
- Perfil de usuario con foto/avatar opcional, identidad, permisos y vínculo institucional.
- Rol/permiso en matriz que funcione por teclado, con explicación del alcance de cada privilegio.
- Indicar con claridad qué cambios requieren confirmación y qué usuarios resultarán afectados.
- No inventar un módulo académico no contemplado por el backend; reflejar la funcionalidad realmente existente.

### 7.8 Reportes y métricas

Filtros por rango y categorías, KPIs, gráficos legibles, tabla subyacente, exportación real en formatos soportados; fechas y números en formato local peruano. Mostrar unidad y periodo de cada métrica. Colores semánticos, no depender exclusivamente de gradientes.

### 7.9 Auditoría, notificaciones y ajustes

Vistas legibles con paginación, filtros y mensajes comprensibles. Las auditorías y secretos del sistema **solo** son accesibles a los roles autorizados. No mostrar `.env`, tokens, cadenas SMTP ni claves de terceros en vistas ni respuestas de AJAX.

---

## 8. NAVEGACIÓN Y EXPERIENCIA RESPONSIVE REAL

### Anchuras que debes probar

`320, 360, 390, 430, 768, 820, 1024, 1280, 1440 y 1920 px` en los flujos principales. Adaptar el diseño por **espacio disponible y contenido**, no solo por tipo de dispositivo.

- **Escritorio:** navegación completa sin quiebres; sidebar administrativa colapsable; filtros laterales útiles.
- **Tablet:** navegación pública simplificada; panel admin con menú lateral ocultable sin tapar el contenido.
- **Móvil:** logo visible sin deformar; botón de menú a la derecha si es adecuado; buscador usable; resultados sin columnas estrechas; CTAs accesibles; tarjetas sin recortes; formularios de una columna.
- **320 px:** no hay desbordamiento horizontal general de la página; tablas especializadas pueden tener un contenedor de scroll claramente indicado.
- Menús desplegables responden a mouse, teclado y toque; no dependen solo de hover.
- Encabezados sticky únicamente si no consumen demasiado alto ni tapan anclas o formularios.
- Elementos táctiles suficientemente grandes y separados; íconos con etiqueta o nombre accesible.

### Interacciones y microanimaciones

Usar transiciones 120–240 ms principalmente para color, opacidad y transformaciones suaves. Animaciones de entrada **discretas y prescindibles**. En `prefers-reduced-motion: reduce`, desactivar movimientos no esenciales y scroll animado. No sacrificar rendimiento por efectos decorativos ni bloquear carga inicial con animaciones.

---

## 9. ACCESIBILIDAD OBLIGATORIA: WCAG 2.2 AA

Implementar y comprobar:

1. Contraste de texto normal **al menos 4.5:1**, texto grande **al menos 3:1**; componentes y bordes de estado relevantes con contraste no textual apropiado. Ajustar `on-primary/on-secondary` y avisar si una paleta falla.
2. Navegación completa por teclado: orden lógico, foco visible, skip link «Saltar al contenido», Escape en overlays, retorno de foco desde modales.
3. Semántica HTML correcta: `header`, `nav`, `main`, `section`, `article`, `footer`, `table` cuando son datos tabulares, etiquetas de formularios y encabezados jerárquicos.
4. Inputs con `label` real, estado requerido y mensajes asociados mediante `aria-describedby`; errores visibles sin depender solo del color.
5. Rótulos accesibles en botones de icono; logo/foto con alt significativo según contexto; imagen decorativa con alt vacío.
6. Estado actual en navegación; breadcrumb con etiqueta; `aria-expanded` cuando corresponde.
7. Modal con trampa de foco apropiada o `<dialog>` correctamente manejado; los toast importantes son anunciados sin duplicaciones molestas.
8. Reflujo y ampliación de texto sin pérdida de contenido ni funcionalidad hasta los niveles esperados por WCAG; elementos táctiles adecuados.
9. Avisos de carga/resultado con texto claro; las barras de progreso exponen valor y etiqueta accesible.
10. No depender únicamente de color, íconos o posición para informar un estado.
11. `lang="es-PE"`, textos consistentes y fechas legibles; nombres institucionales oficiales sin forzar mayúsculas permanentes.
12. Efectuar pruebas manuales de teclado y herramientas automatizadas de accesibilidad; registrar y corregir hallazgos reales.

**No declarar certificación WCAG únicamente por usar una librería.** Documentar resultados de auditorías y las incidencias no resueltas, si las hubiera.

---

## 10. RENDIMIENTO: EXPERIENCIA RÁPIDA EN HOSTING COMPARTIDO Y VPS

- HTML renderizado en servidor por PHP: contenido público visible sin depender de JS.
- CSS dividido de forma razonable (`tokens`, `base`, `layout`, `components`, `pages`) y cargado sin duplicaciones.
- JavaScript modular con `defer` o módulos; cargar scripts de páginas solo si son necesarios.
- No incluir bibliotecas de UI masivas ni Google Fonts obligatorias.
- Imágenes optimizadas en formatos adecuados, tamaños intrínsecos, `srcset` cuando hay derivados y lazy load **excepto hero prioritario**.
- Reservar dimensiones de imágenes para evitar desplazamiento visual.
- Favicon/logo cacheados con cambios de URL por versión, no consultas a BD por cada imagen.
- Consultas de tema publicadas cacheables **por institución y versión** sin mezclar datos entre tenants; invalidación al publicar.
- Preferir iconos SVG **internos controlados por el código**, no SVG subido no confiable.
- Minificar cuando el proyecto ya dispone de pipeline o mediante proceso simple documentado; producción debe funcionar sin Node.js instalado.
- Evitar animaciones complejas, bloqueos por scripts y contenido oculto hasta que JS se inicialice.

**Metas orientativas en páginas representativas** (no afirmarlas sin medición): Lighthouse Performance ≥ 90 y Accessibility ≥ 95 en un entorno medible; experiencia Core Web Vitals acorde con recursos y conexión de referencia. Documentar hardware/red y reportar valores reales. Establecer presupuesto de CSS/JS e imágenes e identificar pantallas lentas.

---

## 11. SEGURIDAD DEL FRONTEND, CSP Y MULTIINSTITUCIÓN

- Escapar por contexto el HTML y los atributos de todo texto que proviene de BD; al inyectar valores en JSON/JS usar codificación segura.
- Toda operación de cambios (branding, orden de componentes, subida de logo, publicar tema, borrar banner, editar pie) pasa por **POST/PUT/PATCH autorizado y CSRF**.
- Validez y propiedad de `institution_id` comprobadas en controlador/servicio; no confiar en identificador enviado en formulario.
- **Nunca** ofrecer en el personalizador una caja de JavaScript, CSS o HTML libre al administrador institucional. Textos enriquecidos, si se autorizan, requieren formato limitado y sanitización estricta en servidor.
- Si se usan URLs configurables, aceptar únicamente esquemas seguros (p. ej., `https`), rutas internas validadas y dominios correspondientes cuando aplique; evitar `javascript:` y protocolos peligrosos.
- Procesar uploads, imagenes, iconos y SVG según política del backend; no insertar SVG externo arbitrario como HTML inline.
- Las previsualizaciones de borrador deben comprobar permisos y tenant, llevar `noindex`, no divulgar URLs firmadas de archivos privados y no entrar a cachés públicas.
- Las respuestas de error no revelan stack traces, rutas físicas, queries, credenciales ni nombres de archivos internos.
- Proteger vistas contra clickjacking e inyección conforme a cabeceras configuradas del backend; cuidar que la política CSP permita solo recursos confiables.
- No exponer credenciales de API en scripts, HTML ni variables globales del navegador.
- Consultas por filtros y búsqueda siempre pasan por los métodos seguros definidos en el backend, nunca SQL generado por JS.

---

## 12. INTEGRACIÓN TÉCNICA CON PHP MVC EXISTENTE

### 12.1 Responsabilidades estrictas

**Controladores PHP:** consultar modelos/servicios, verificar permisos, normalizar datos, componer ViewModels, redireccionar tras operaciones exitosas y transmitir errores de validación.

**Vistas PHP:** presentación y escape contextual; componentes compartidos; sin consultas SQL, reglas de autorización nuevas ni lógica de negocio compleja.

**CSS:** estructura y apariencia a partir de design tokens; sin colores institucionales duplicados por página.

**JS:** experiencias progresivas: personalizador visual, menú, filtros, subida, autoguardado, dialogs y búsqueda sugerida cuando exista API. No repetir en JS controles de seguridad que deben ejecutarse en servidor.

### 12.2 Estructura sugerida (ajustar a la real)

```text
app/
  Http/Controllers/
    Web/
    Admin/
  Services/
    ThemeService.php             # Resolver tema publicado/borrador por institución
    BrandAssetService.php        # Gestión/derivados seguros de recursos visuales
  Views/
    layouts/
      public.php                 # HTML base del portal
      admin.php                  # HTML base del panel
      auth.php                   # HTML base del login
    components/
      branding/
      navigation/
      form/
      feedback/
      repository/
    web/
      home.php
      search.php
      advanced-search.php
      collections/
      resources/
      statistics.php
      static/
    admin/
      dashboard.php
      resources/
      reviews/
      collections/
      people/
      reporting/
      appearance/
        index.php               # Configurador
        sections/
    errors/
      403.php
      404.php
      419.php
      500.php
public/assets/
  css/
    tokens.css
    reset.css
    base.css
    utilities.css
    layouts/
    components/
    pages/
  js/
    app.js
    modules/
      mobile-nav.js
      search.js
      upload.js
      deposit-wizard.js
      theme-editor.js
      a11y-dialog.js
  img/
    ui/                         # Elementos propios del producto, sin marcas fijas
routes/
  web.php
  admin.php
  api.php
database/migrations/
docs/
  FRONTEND_DESIGN_SYSTEM.md
  FRONTEND_PERSONALIZACION.md
  FRONTEND_MAPA_PANTALLAS.md
  FRONTEND_PRUEBAS_VISUALES.md
```

**No imponer nombres si los existentes resuelven lo mismo.** Mantener rutas actuales, helpers de rutas y patrones del proyecto para no crear dos frontends competidores.

### 12.3 Renderizar tokens seguros

La fuente de verdad es la versión publicada del tema institucional. El sistema puede:

- Generar un archivo CSS cacheado por `institution_id/version` **desde una lista permitida de tokens**, o
- Inyectar un bloque pequeño de variables generado por servidor con valores validados (preferir CSS externo versionado si CSP es estricta).

Cualquiera de las dos estrategias debe mantener el backend como autoridad: validar HEX con patrón estricto, rangos numéricos permitidos, familias tipográficas de una lista blanca y opciones de layout controladas. **No concatenar contenido libre del administrador en CSS.** No interpolar nombres de archivos o URLs sin validación.

### 12.4 Endpoints funcionales de administración de apariencia

Conservar rutas reales si ya existen; si faltan, crear equivalentes **autorizadas y documentadas**, por ejemplo:

```text
GET    /admin/apariencia                          # editor + tema vigente
POST   /admin/apariencia/borrador                 # guardar tema provisional
GET    /admin/apariencia/vista-previa             # preview autorizada, noindex
POST   /admin/apariencia/publicar                 # publicar transaccionalmente
POST   /admin/apariencia/restaurar                # recuperar versión anterior
POST   /admin/apariencia/activos                  # subir logo/favicon/banner
DELETE /admin/apariencia/activos/{id}             # retirar activo autorizado
GET    /assets/temas/{version-publica}.css         # CSS de marca seguro y cacheable
```

No suponer que `DELETE` funcionará con formularios HTML sin method override autorizado. Implementar método aceptado por router, con CSRF y permisos. La ruta del CSS debe resolver instituto de forma segura por contexto/identificador firmado o clave pública inequívoca, sin filtrar temas privados.

---

## 13. MAPA DE PANTALLAS Y COBERTURA

La IA desarrolladora deberá crear una **matriz de cumplimiento** con ruta, vista, componente, responsable y verificación.

| Área | Vista o pantalla | Prioridad |
|---|---|---|
| Público | Inicio y buscador hero | Crítica |
| Público | Resultados y filtros | Crítica |
| Público | Búsqueda avanzada | Alta |
| Público | Comunidad y colección | Alta |
| Público | Ficha pública de recurso | Crítica |
| Público | Vista PDF y acceso a archivos | Crítica |
| Público | Explorar autores, tipos y programas | Alta |
| Público | Estadísticas, acerca de, políticas, contacto | Alta |
| Público | Estados sin resultados, error, acceso denegado | Crítica |
| Acceso | Login, recuperación si existe, sesión vencida | Crítica |
| Administración | Shell: sidebar, topbar, breadcrumb, perfil | Crítica |
| Administración | Dashboard según rol | Crítica |
| Administración | Lista de documentos y filtros | Crítica |
| Administración | Depósito guiado de seis pasos | Crítica |
| Administración | Revisión, observaciones y publicación | Crítica |
| Administración | Comunidades/colecciones, usuarios y roles | Alta |
| Administración | Reportes, notificaciones, auditoría y ajustes | Alta |
| Apariencia | Configurar identidad y colores | Crítica |
| Apariencia | Subir logo, logo alternativo y favicon | Crítica |
| Apariencia | Hero, portada, pie y menús | Crítica |
| Apariencia | Preview responsive, guardar borrador y publicar | Crítica |
| Apariencia | Restauración de identidad/tema anterior | Alta |

Priorizar críticamente el flujo visitante **inicio → buscar → ficha → ver/descargar** y el flujo administrador **login → apariencia → personalizar → preview → publicar**, además de **depositante → borrador → enviar** y **revisor → verificar → aprobar**.

---

## 14. MANEJO DE DATOS, FORMATO PERUANO E IDENTIDAD INSTITUCIONAL

- Escribir textos en español claro del Perú, sin traducciones automáticas extrañas.
- Fechas con convención legible, horario `America/Lima`, valores del backend como fuente de verdad.
- Mostrar el nombre oficial exacto **configurado**, incluyendo tildes, siglas y denominación institucional real.
- Usar nombres genéricos en instalación limpia; jamás insertar docentes, autoridades, RUC, documentos o cifras no provistos como si fueran reales.
- Programas de estudio configurados por institución; no crear una lista inmutable de carreras para todos.
- Preparar tamaños adecuados para nombres institucionales largos sin reducir tipografía hasta quedar ilegible: dos líneas controladas, logo a un costado y jerarquía adaptable.
- En multiinstitución, todas las etiquetas, URL, enlaces externos, redes sociales, meta OG, colores, favicon y datos de contacto reflejan **solo** la institución activa.

---

## 15. CRITERIOS VISUALES DE APROBACIÓN: «INTERFAZ EXCELENTE»

El proyecto **NO** se da por terminado hasta superar lo siguiente:

### Primera impresión

- [ ] Al entrar a inicio se comprende en menos de unos segundos que es un repositorio académico y cómo buscar.
- [ ] El resultado luce profesional tanto con paleta azul como con una paleta verde/granate distinta.
- [ ] La marca institucional se ve nítida, proporcional y con nombre correcto.
- [ ] Logo ausente no produce imagen rota ni espacio vacío antiestético.
- [ ] El hero sigue siendo legible incluso con imagen de fondo cargada.
- [ ] La tipografía, espaciado y grid mantienen consistencia entre todas las vistas.

### Personalización

- [ ] Se puede cambiar primario, secundario y acento en una interfaz comprensible.
- [ ] Se puede cargar/reemplazar/quitar logo principal y alternativo sin editar archivos.
- [ ] Se puede cargar/reemplazar favicon y verlo reflejado en la pestaña.
- [ ] Se puede cambiar imagen de portada y texto hero.
- [ ] Se puede ajustar cabecera y pie con controles acotados.
- [ ] La previsualización no afecta todavía al portal publicado.
- [ ] Guardar y publicar son acciones separadas, confirmables, persistentes y auditadas.
- [ ] El tema publicado persiste tras logout/login y reinicio.
- [ ] La personalización del instituto A no aparece en el instituto B.
- [ ] Tema inválido se rechaza con mensaje específico, no con error genérico.
- [ ] Restaurar paleta o una versión anterior devuelve una configuración válida.

### Funcionalidad

- [ ] Cada pantalla principal está conectada a datos reales o muestra un estado vacío honesto.
- [ ] Formularios, filtros, tablas, paginación y diálogos cumplen su propósito.
- [ ] No hay enlaces rotos ni botones ficticios.
- [ ] Los documentos privados/embargados no se descargan desde enlaces directos.
- [ ] Acciones del panel corresponden a permisos reales del rol activo.
- [ ] Responsive verificado en móvil, tablet y escritorio sin desbordamiento general.

### Accesibilidad y rendimiento

- [ ] Navegación completa por teclado en login, menú, filtros, ficha, depósito y editor de tema.
- [ ] Foco visible y contraste verificado en paletas elegidas.
- [ ] Los errores de formulario son leíbles y ubicados junto al control.
- [ ] `prefers-reduced-motion` funciona y no hay animaciones bloqueantes.
- [ ] Lighthouse/mediciones reales documentadas con configuración y capturas o reportes cuando sean posibles.
- [ ] No aparecen errores JS, advertencias de recursos rotos ni contenido invisible sin JS.

---

## 16. PRUEBAS ESPECÍFICAS QUE DEBES EJECUTAR

### Prueba A: instituto sin activos

Crear una institución de pruebas sin logo, favicon ni banner. Validar que el portal conserva una identidad aceptable, favicon fallback, cabecera proporcionada, textos legibles y login usable.

### Prueba B: dos temas muy distintos

Preparar **solo en ambiente de pruebas** dos instituciones ficticias con paletas contrastantes y logos propios de prueba. Navegar portal, login, administración, documento y configurador en ambos. Verificar ausencia de filtraciones en colores, assets, textos y CSS cacheado. No mantener datos falsos como si fueran producción.

### Prueba C: cambio de logo/favicon

Subir archivo válido y observar vista previa, guardar, publicar y validar URL/imagen final. Probar tamaño excesivo, extensión falsa, MIME falso, contenido corrupto e intento de SVG con scripts. Deben rechazarse sin comprometer almacenamiento ni filtrarse datos.

### Prueba D: paletas difíciles

Usar primario casi blanco, muy oscuro, acento muy brillante y combinaciones de contraste bajo. Verificar derivación o rechazo explicativo para botones, texto, foco, header y sidebar. La interfaz debe seguir siendo usable.

### Prueba E: vistas móviles

Comprobar a 320/360/390/430 px: home, search, ficha, login, wizard y tema. No debe haber controles superpuestos, textos cortados ni botón «Guardar» fuera del área accesible.

### Prueba F: roles y publicación

Un visitante nunca ve el editor; un rol sin permiso recibe 403 si intenta la URL; un administrador de instituto A no puede editar B; un cambio de borrador no afecta al portal público; publicar exige CSRF y autorización.

### Prueba G: rutas y estado sin JS

La página inicial, búsqueda con formulario GET, resultados, ficha y enlaces principales deben continuar accesibles sin JavaScript. El personalizador puede requerir JS para **preview inmediato**, pero guardado seguro y validación de servidor no dependen de confiar en JS.

### Prueba H: regresión

Comprobar que el rediseño no rompe depósito, revisión, permisos, descargas controladas, sesiones, filtros, almacenamiento ni funcionalidades ya existentes. Registrar tests y defectos.

---

## 17. PLAN DE DESARROLLO OBLIGATORIO, EN ORDEN

No ejecutar una reescritura descontrolada. Implementar y **verificar** cada fase:

**Fase 0 — Auditoría y mapa:** inspeccionar MVC existente, rutas, entidades, base de datos, permisos, assets, vistas; registrar qué es reutilizable y matriz de faltantes. Detectar CSS/JS conflictivo y dependencias.

**Fase 1 — Design system y shell:** tokens, CSS base, componentes, iconos, layouts PHP (público/admin/auth), tipografía, responsive y accesibilidad global; validar sin romper pantallas existentes.

**Fase 2 — Identidad dinámica:** ThemeService, lectura por institución, valores por defecto, temas publicados, rutas/versionado de assets; logo, favicon y aplicación de tokens en todos los layouts.

**Fase 3 — Editor visual de marca:** panel «Apariencia e identidad», selectores HEX, gestión de activos, paletas predefinidas, tema borrador, previsualización desktop/móvil, publicación y auditoría.

**Fase 4 — Portal público:** inicio, búsqueda, filtros, comunidades, colecciones, ficha, PDF, páginas informativas, estados vacíos y SEO. Conectar datos auténticos del backend.

**Fase 5 — Administración:** dashboard, tablas, formularios, depósito guiado, revisión, colecciones, usuarios, reportes y pantallas auxiliares aplicables por rol.

**Fase 6 — Refinamiento:** microinteracciones, iconografía, consistencia tipográfica, estados UI completos, contraste, rendimiento y detalle responsive.

**Fase 7 — QA y entrega:** pruebas de aislamiento, accesibilidad, seguridad, exploración móvil, tests de regresión, documentación y corrección de incidencias. Registrar limitaciones honestamente.

**Dentro de cada fase:** implementar código real, correr comprobaciones disponibles (`php -l`, tests existentes, migraciones en ambiente de pruebas, validaciones y pruebas de navegador si hay herramientas), revisar errores y pasar a la siguiente. Evitar limitarse a un plan teórico.

---

## 18. ENTREGABLES OBLIGATORIOS

1. **Frontend funcional** integrado con el backend MVC existente.
2. **Portal público completamente responsive** y de calidad visual consistente.
3. **Panel interno por roles** con UX coherente y acciones conectadas.
4. **Sistema de apariencia institucional**, con colores, logo, favicon, hero, tipografías permitidas, cabecera y pie editables.
5. **Borradores de apariencia, previsualización y publicación real**, con persistencia y auditoría.
6. Componentes PHP, módulos JS y hojas CSS reutilizables y documentados.
7. Migraciones/configuración necesarias compatibles con esquema previo.
8. `docs/FRONTEND_DESIGN_SYSTEM.md` (tokens, componentes y variantes).
9. `docs/FRONTEND_PERSONALIZACION.md` (uso del editor, logo, favicon, cambios de tema, restauración, recomendaciones de imágenes).
10. `docs/FRONTEND_MAPA_PANTALLAS.md` (vistas y rutas implementadas).
11. `docs/FRONTEND_PRUEBAS_VISUALES.md` (viewports, escenarios, errores encontrados y resultados reales).
12. Capturas o evidencia de 3 flujos, **si el entorno tiene navegador/capacidad de captura**: portal público, panel de control y configurador de tema, cada uno en escritorio y móvil.

Documenta explícitamente cualquier característica que dependa de una integración no disponible, y ofrece alternativa funcional sin presentar un mockup como si estuviera terminado.

---

## 19. INSTRUCCIÓN FINAL A LA IA PROGRAMADORA — EJECUTA, NO SOLO EXPLIQUES

**Comienza ahora.**

1. Lee completamente el prompt maestro de repositorio PHP MVC/MySQL y este archivo.
2. Inspecciona el proyecto real: estructura, rutas, vistas, CSS, JS, servicios, tablas y permisos.
3. Presenta brevemente una lista de hallazgos y un **plan de archivos concretos**; no preguntes por detalles menores que puedan resolverse con valores por defecto.
4. Implementa primero una identidad institucional funcional y un **sistema de tokens único**, capaz de cambiar de apariencia en toda la aplicación.
5. Construye el módulo **Apariencia e identidad institucional** para subir logo y favicon, editar la paleta, previsualizar y publicar cambios.
6. Desarrolla el **portal público moderno completo**, comenzando por inicio/buscador/ficha, y continúa con el panel administrativo por roles y flujos de depósito/revisión.
7. Integra cada vista con los servicios de datos reales y controles de autorización existentes. Corrige los fallos visuales y funcionales que detectes.
8. Verifica compatibilidad móvil, accesibilidad, seguridad de carga de activos, aislamiento de temas por instituto y rendimiento.
9. Entrega documentación, pruebas y un resumen objetivo de módulos implementados, archivos modificados y pendientes verdaderos.

### Éxito esperado

> Al instalar el sistema en un instituto público peruano, su administrador debe poder **cargar su escudo o logo, subir el favicon, elegir exactamente sus colores institucionales, definir portada y textos, ver una previsualización y publicar**. El visitante verá inmediatamente un repositorio **elegante, rápido, accesible, profesional y con la identidad del instituto**; el personal tendrá un panel coherente para administrar documentos académicos, realizar depósitos y aprobaciones. Todo ello **sin modificar archivos fuente y sin interferir con otra institución**.

**No entregues únicamente un diseño bonito. Entrega un producto de frontend real, adaptable y probado.**
