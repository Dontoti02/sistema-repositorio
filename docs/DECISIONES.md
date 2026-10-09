# DECISIONES TÉCNICAS

> Registro de decisiones y supuestos del proyecto. Toda decisión menor que no
> bloquea interoperabilidad se resuelve aquí con valores por defecto sensatos.

## D-001 — Modo de instalación por defecto (Fase 0)

- **Decisión:** el sistema nace en **Modo A (instituto individual)** por defecto:
  `INSTALLATION_MODE=single` y `TENANCY_MODE=single`.
- **Razón:** el prompt exige que cualquier instituto público peruano pueda
  instalarlo de forma independiente; el modo multientidad es optativo.
- **Implementación:** el modelo de datos **siempre** lleva `institution_id` en
  todas las entidades institucionales (items, archivos, colecciones,
  usuarios-membresía, estadísticas, auditoría). La resolución del instituto se
  hace en `TenantContext`; en modo `single` se usa el instituto activo único.
  Las rutas/funciones de administración de red solo se registran cuando
  `TENANCY_MODE=multi`.
- **Supuesto registrado:** ninguna funcionalidad depende de asumir un solo
  instituto; el aislamiento es estricto desde la primera migración.

## D-002 — Stack tecnológico

- PHP 8.2+ nativo (entorno XAMPP con PHP 8.2.12 verificado), orientado a
  objetos, MVC propio sin frameworks (Laravel/Symfony/CodeIgniter prohibidos).
- Composer solo para autoload PSR-4 + PHPUnit (dev).
- MySQL/MariaDB: verificado en el entorno MariaDB 11.8.2 (compatible con
  MySQL 8.x) en `127.0.0.1:3306`. Las migraciones se escriben en SQL
  compatible con ambos motores (InnoDB, utf8mb4).
- JavaScript vanilla, CSS propio con variables, sin CDN obligatorio.

## D-003 — Identificador estable de recurso

- **Decisión:** `items.public_id` con formato `RI-AAAA-NNNNNN`
  (RI = Repositorio Institucional, año de publicación, correlativo 6 dígitos),
  único por institución. La URL pública es `/recursos/{public_id}` y **nunca
  cambia** aunque cambie el título o slug.
- El slug solo se usa como segmento opcional de SEO.

## D-004 — Estados editoriales y modalidades de acceso

- Estados editoriales: `DRAFT`, `SUBMITTED`, `UNDER_REVIEW`,
  `CHANGES_REQUESTED`, `APPROVED`, `REJECTED`, `PUBLISHED`, `WITHDRAWN`,
  `ARCHIVED`.
- Modalidades de acceso (columna `access_level`): `open`, `metadata_only`,
  `embargoed`, `restricted`, `private`. Son independientes del estado
  editorial.
- Transiciones controladas por `ItemWorkflowService` con permisos RBAC y
  registro en `item_status_history`.

## D-005 — Almacenamiento de archivos

- Los binarios **nunca** se guardan en `public/`. Ruta privada por defecto:
  `storage/private/institutions/{institution_id}/{yyyy}/{mm}/{uuid}.{ext}`.
- En BD solo: metadatos, `storage_key`, `sha256`, tamaño, MIME real (fileinfo).
- Descarga siempre vía controlador que valida permisos, embargo y visibilidad.

## D-006 — Motor de búsqueda

- MySQL FULLTEXT (natural language, soporte español `es`) sobre
  `item_versions(title, abstract)` + búsquedas LIKE en autores/palabras clave
  como complemento. Se documenta que la relevancia no se garantiza como
  perfecta.

## D-007 — Identificadores DOI/Handle

- **Nunca** se generan DOI/Handle ficticios. Mientras no exista proveedor
  habilitado, el identificador público es la URI propia
  `{APP_URL}/recursos/{public_id}` etiquetada como `local_uri`.

## D-008 — Integraciones externas no disponibles

- SMTP: `MAIL_DRIVER=log` por defecto (cola en BD `email_outbox` + registro).
  Estado visible en Salud como "deshabilitado" hasta configurar SMTP real.
- ClamAV/OCR: interfaces preparadas (`AntivirusInterface`, `OcrInterface`) con
  implementación `Null*` y estado explícito "no disponible".
- El sistema marca honestamente estas integraciones como deshabilitadas.

## D-009 — Protección de datos personales

- Referencia: Ley N.º 29733 y D. S. N.º 016-2024-JUS. El sistema minimiza
  datos: los campos internos no se exportan ni se exponen públicamente. IP en
  auditoría almacenada enmascarada / hash con sal.

## D-010 — Zona horaria e idioma

- Presentación: `America/Lima`, locale `es_PE`. Almacenamiento de timestamps
  en UTC (`DATETIME` calculado en UTC). Fechas en español en interfaz.

## D-011 — Instalador

- El instalador web (`/instalar`) solo opera si `schema_migrations` no tiene
  filas o flag `installed` ausente; tras instalar escribe
  `storage/installed.lock` y queda bloqueado (aunque `APP_KEY` exista).

## D-012 — Pruebas

- PHPUnit 10 (dev) para unitarias e integración. Si el entorno no dispone de
  red para Composer, se provee runner alternativo `bin/console test`.
