# Sistema de Diseño Frontend — Repositorio Institucional Digital

> **Versión:** 1.0.0  
> **Fecha de liberación:** Octubre de 2026  
> **Ámbito:** Repositorios Institucionales de Educación Superior Tecnológica y Pedagógica del Perú  
> **Compatibilidad:** WCAG 2.2 Nivel AA · CSS3 Modular Nativo · Navegadores Evergreen y Móviles (320px–1920px)  
> **Dependencias:** Cero dependencias de Node.js o librerías externas en tiempo de ejecución.

---

## 1. Fundamentos y Filosofía Visual

El sistema de diseño del Repositorio Institucional ha sido concebido para transmitir cuatro valores esenciales:

1. **Prestigio y Rigor Académico:** Una interfaz sobria, estructurada y limpia donde el protagonismo absoluto recae en la producción científica, tecnológica e intelectual (tesis, proyectos de titulación, artículos, monografías).
2. **Identidad Institucional Soberana:** Cada instituto público del Perú posee su propia paleta cromática, heráldica y logotipo. El diseño se adapta de forma orgánica y dinámica a la identidad de la entidad sin alterar la legibilidad ni la usabilidad.
3. **Accesibilidad Universal (A11y):** Cumplimiento estricto de las pautas WCAG 2.2 AA (contraste mínimo de 4.5:1 en texto normal y 3:1 en elementos de interfaz), soporte para lectores de pantalla, navegación completa por teclado, enlaces de salto (`skip-links`) y respeto a las preferencias de reducción de movimiento (`prefers-reduced-motion`).
4. **Rendimiento Extremo (Web Performance):** Arquitectura CSS pura, sin frameworks pesados ni compilaciones complejas en el servidor de producción. Tiempo de renderizado inicial inferior a 150 ms en conexiones locales.

---

## 2. Arquitectura de Design Tokens

Los tokens de diseño están centralizados en `:root` y se estructuran en capas jerárquicas: **Tokens de Marca**, **Tokens de Superficie y Texto**, **Tokens Semánticos de Estado**, **Tokens Espaciales y de Elevación**, y **Tokens de Movimiento**.

### 2.1 Tokens de Marca (Dinamizados por Institución)

Estos tokens son inyectados dinámicamente desde el motor `ThemeService` en cada carga de página mediante la hoja versionada `/assets/temas/{institucion_id}/{version}.css`:

| Token CSS | Propósito | Fallback Seguro |
|---|---|---|
| `--brand-primary` | Color institucional predominante (cabeceras, acciones principales, acentos fuertes) | `#113b70` |
| `--brand-secondary` | Color complementario de marca (fondos suaves, estados activos secundarios) | `#2873bd` |
| `--brand-accent` | Acento vibrante para destacados, insignias y botones de conversión | `#e4ae35` |
| `--brand-on-primary` | Color de texto accesible calculado para contrastar sobre `--brand-primary` | `#ffffff` |
| `--brand-on-secondary` | Color de texto accesible calculado para contrastar sobre `--brand-secondary` | `#ffffff` |
| `--brand-on-accent` | Color de texto accesible calculado para contrastar sobre `--brand-accent` | `#152438` |

### 2.2 Tokens de Superficie, Texto y Bordes

Definen la atmósfera limpia y los niveles de elevación de las tarjetas y contenedores:

| Token CSS | Propósito | Valor Predeterminado |
|---|---|---|
| `--page-bg` | Fondo del lienzo principal de la página | `#f6f8fc` |
| `--surface` | Fondo de tarjetas, modales y barras de navegación | `#ffffff` |
| `--surface-subtle` | Fondo alterno para filas de tablas y bloques secundarios | `#f1f5fa` |
| `--text` | Color del texto principal para máxima legibilidad | `#152438` |
| `--text-muted` | Color para metadatos, fechas, subtítulos y etiquetas secundarias | `#5b6a7c` |
| `--border` | Borde sutil para tarjetas, separadores y campos | `#dce4ee` |
| `--focus-ring` | Anillo de enfoque de alta visibilidad para accesibilidad por teclado | `#1b64c2` |

### 2.3 Tokens de Estado del Sistema

Invariables ante los cambios de paleta de marca para preservar la claridad semántica:

| Token CSS | Propósito | Valor |
|---|---|---|
| `--success` | Operaciones exitosas, estados aprobados, acceso abierto | `#14794b` |
| `--warning` | Alertas, estados en observación, advertencias de contraste | `#9a6515` |
| `--danger` | Errores, rechazos, acciones destructivas, acceso restringido | `#b42318` |
| `--info` | Notificaciones informativas y guías de ayuda | `#205b95` |

### 2.4 Tokens de Geometría, Espacio y Elevación

| Token CSS | Valor | Uso |
|---|---|---|
| `--radius-sm` | `8px` | Botones pequeños, insignias, chips de palabras clave |
| `--radius-md` | `12px` | Tarjetas estándar, controles de formulario, botones |
| `--radius-lg` | `18px` | Modales, paneles de diálogo, contenedores hero |
| `--shadow-card` | `0 8px 28px rgba(18, 42, 74, 0.06)` | Elevación flotante sutil para tarjetas académicas |
| `--shadow-hover` | `0 12px 32px rgba(18, 42, 74, 0.12)` | Transición interactiva en hover |
| `--container-max`| `1240px` | Ancho máximo de lectura y alineación de cuadrícula |
| `--header-height`| `76px` | Altura estándar de la cabecera fija de navegación |

### 2.5 Tokens de Animación

| Token CSS | Duración | Uso |
|---|---|---|
| `--motion-fast` | `150ms ease` | Hover de botones, cambio de color de enlaces |
| `--motion-base` | `220ms cubic-bezier(...)` | Despliegue de menús, transiciones de tarjetas |

---

## 3. Sistema Tipográfico

La tipografía utiliza una pila de fuentes seguras del sistema operativo para evitar parpadeos de carga de fuente (FOUT/FOIT) y garantizar la máxima nitidez tanto en Windows, macOS, Android como Linux:

```css
font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Escala Modular de Texto

- **Título Hero (`h1.hero-title`):** `clamp(2rem, 4vw, 2.75rem)` · Peso 800 · `line-height: 1.15`.
- **Encabezados Principales (`h1`):** `1.875rem (30px)` · Peso 700 · `line-height: 1.25`.
- **Encabezados de Sección (`h2`):** `1.5rem (24px)` · Peso 700 · `line-height: 1.3`.
- **Encabezados de Tarjeta (`h3`):** `1.125rem (18px)` · Peso 600 · `line-height: 1.4`.
- **Cuerpo de Texto (`body`):** `1rem (16px)` · Peso 400 · `line-height: 1.6`.
- **Metadatos y Etiquetas (`small`, `.text-muted`):** `0.875rem (14px)` · Peso 500 · `line-height: 1.4`.
- **Micro-etiquetas (`.badge`):** `0.75rem (12px)` · Peso 600 · `text-transform: uppercase` · `letter-spacing: 0.04em`.

---

## 4. Biblioteca de Componentes UI

### 4.1 Botones (`.btn`)
- `.btn-primary`: Fondo `--brand-primary`, texto `--brand-on-primary`, elevación sutil.
- `.btn-secondary`: Fondo `--surface`, borde `--border`, texto `--text`.
- `.btn-outline`: Fondo transparente, borde `2px solid var(--brand-primary)`, texto `--brand-primary`.
- `.btn-ghost`: Fondo transparente, sin borde, interacción suave.
- `.btn-sm`, `.btn-lg`: Variantes de escala adaptables a móvil y escritorio.

### 4.2 Tarjetas Académicas (`.academic-card`)
Diseñadas específicamente para la exhibición de tesis y artículos de investigación:
- Contenedor con borde fino de `1px solid var(--border)` y radio `--radius-md`.
- Indicador visual superior con barra de color de la colección o programa.
- Metadatos superiores: tipo de documento (`Tesis`, `Artículo`, `Proyecto de Titulación`), año de publicación y programa de estudios.
- Título académico en negrita con enlace accesible.
- Autores e identificador institucional (URI / Handle / DOI).
- Insignia de acceso (`Acceso Abierto`, `Embargado`, `Restringido`).

### 4.3 Tarjetas de Indicadores KPI (`.kpi-card`)
- Exhibición de estadísticas en el portal público e indicadores de gestión en el panel administrativo.
- Cifra numérica prominente (`2rem` peso 800) acompañada de icono semántico y rótulo descriptivo.

### 4.4 Formulario y Controles de Entrada (`.form-control`, `.form-select`)
- Estados completos: reposo, focus (`--focus-ring`), válido, error (`--danger`) y deshabilitado.
- Mensaje de ayuda y texto de error contiguo con enlace accesible mediante `aria-describedby`.

### 4.5 Asistente de Depósito en Pasos (`.wizard-stepper`)
- Componente de 6 pasos con numeración visual, título del paso e indicador de estado (`completado`, `activo`, `pendiente`).
- Adaptación responsive: colapsa elegantemente a formato numérico compacto en viewports inferiores a 768px.

### 4.6 Sistema de Alertas y Notificaciones (`.alert`, `.toast`)
- Contenedores semánticos con icono SVG accesible, título claro y botón de cierre si aplica.

---

## 5. Accesibilidad Universal (WCAG 2.2 Nivel AA)

El sistema incorpora de serie los siguientes mecanismos de accesibilidad:

1. **Cálculo Dinámico de Contraste:**
   El backend y el editor cliente calculan la luminancia relativa conforme al algoritmo estándar W3C:
   $$L = 0.2126 \cdot R_{lin} + 0.7152 \cdot G_{lin} + 0.0722 \cdot B_{lin}$$
   Cualquier combinación que no alcance un ratio de **4.5:1** para texto normal dispara una alerta inmediata en el configurador.
2. **Derivación Automática de Textos (`on-*`):**
   Si el administrador selecciona un color primario oscuro (ej. `#113b70`), el sistema deriva automáticamente texto blanco (`#ffffff`). Si selecciona un color claro o pastel (ej. `#e4ae35`), deriva automáticamente texto de alto contraste oscuro (`#152438`).
3. **Foco Accesible por Teclado:**
   Todo elemento interactivo cuenta con una regla explícita `:focus-visible` con contorno de 2px y desplazamiento (`outline-offset: 2px`).
4. **Soporte para Movimiento Reducido:**
   ```css
   @media (prefers-reduced-motion: reduce) {
     *, *::before, *::after {
       animation-duration: 0.01ms !important;
       transition-duration: 0.01ms !important;
       scroll-behavior: auto !important;
     }
   }
   ```
5. **Enlace de Salto al Contenido Principal (`.skip-link`):**
   Primer elemento focuseable de la página, invisible hasta que recibe foco por teclado. Permite a los usuarios de lectores de pantalla saltar directamente a `<main id="main-content">`.

---

## 6. Organización de Hojas de Estilo

```text
public/assets/css/
├── tokens.css            # Definición maestra de variables CSS y paleta fallback
├── base.css              # Reset semántico, tipografía, skip-links y accesibilidad
├── components.css        # Botones, formularios, tarjetas, stepper, badges, alerts
├── layouts.css           # Grillas, encabezados, footers, drawer móvil, contenedores
├── pages/
│   └── appearance.css    # Layout split-screen y canvas del configurador de apariencia
└── app.css               # Hoja unificada que importa los módulos en orden óptimo
```
