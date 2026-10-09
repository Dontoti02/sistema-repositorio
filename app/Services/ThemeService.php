<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Servicio de temas y personalización visual institucional (Design System).
 * Gestiona el ciclo de vida del tema: valores por defecto, borrador, publicación,
 * historial de versiones, compilación de tokens CSS seguros y validación de contraste WCAG.
 */
final class ThemeService
{
    /** @var array<int,array<string,mixed>> Caché en memoria por institución */
    private static array $memoryCache = [];

    /**
     * Paletas predefinidas de demostración basadas en el prompt maestro.
     * @var array<string,array{name:string,primary:string,secondary:string,accent:string}>
     */
    public const PRESET_PALETTES = [
        'blue' => [
            'name' => 'Azul institucional',
            'primary' => '#113b70',
            'secondary' => '#2873bd',
            'accent' => '#e4ae35',
        ],
        'green' => [
            'name' => 'Verde académico',
            'primary' => '#145a42',
            'secondary' => '#238263',
            'accent' => '#d9ae50',
        ],
        'garnet' => [
            'name' => 'Granate académico',
            'primary' => '#702c44',
            'secondary' => '#9e4b67',
            'accent' => '#dca64f',
        ],
        'petroleum' => [
            'name' => 'Azul petróleo',
            'primary' => '#12485b',
            'secondary' => '#1c7892',
            'accent' => '#d5a447',
        ],
        'purple' => [
            'name' => 'Morado institucional',
            'primary' => '#472e75',
            'secondary' => '#7659aa',
            'accent' => '#e5b64c',
        ],
    ];

    /**
     * Devuelve la configuración por defecto del sistema de diseño.
     *
     * @return array{tokens:array<string,string>,layout:array<string,mixed>,texts:array<string,string>}
     */
    public static function getDefaults(): array
    {
        return [
            'tokens' => [
                'brand_primary'       => '#113b70',
                'brand_secondary'     => '#2873bd',
                'brand_accent'        => '#e4ae35',
                'brand_on_primary'    => '#ffffff',
                'brand_on_secondary'  => '#ffffff',
                'brand_on_accent'     => '#172334',
                'page_bg'             => '#f6f8fc',
                'surface'             => '#ffffff',
                'surface_subtle'      => '#f1f5fa',
                'text'                => '#152438',
                'text_muted'          => '#5b6a7c',
                'border'              => '#dce4ee',
                'focus_ring'          => '#1b64c2',
                'header_bg'           => '#ffffff',
                'header_text'         => '#152438',
                'sidebar_bg'          => '#102d52',
                'sidebar_text'        => '#f6f8fc',
                'footer_bg'           => '#102d52',
                'footer_text'         => '#f6f8fc',
                'success'             => '#14794b',
                'warning'             => '#9a6515',
                'danger'              => '#b42318',
                'info'                => '#205b95',
                'font_sans'           => 'system-ui, -apple-system, "Segoe UI", sans-serif',
                'radius_sm'           => '8px',
                'radius_md'           => '12px',
                'radius_lg'           => '18px',
                'shadow_card'         => '0 8px 28px rgb(18 42 74 / 6%)',
                'header_height'       => '76px',
                'container_max'       => '1240px',
                'motion_fast'         => '150ms',
                'motion_base'         => '220ms',
            ],
            'layout' => [
                'hero_mode'           => 'gradient', // solid | gradient | image
                'hero_focal'          => 'center',   // center | left | right
                'hero_overlay'        => 'dark',     // dark | light | none
                'header_style'        => 'standard', // standard | compact
                'logo_display'        => 'logo_name',// logo_name | only_logo | logo_short
                'footer_style'        => 'full',     // full | compact
                'card_density'        => 'comfortable',
                'show_stats'          => true,
                'show_collections'    => true,
                'show_recent'         => true,
                'show_programs'       => true,
                'sections_order'      => ['hero', 'search', 'stats', 'collections', 'recent', 'programs', 'info'],
            ],
            'texts' => [
                'site_name'           => '',
                'site_short'          => '',
                'motto'               => '',
                'hero_title'          => 'Repositorio Institucional',
                'hero_subtitle'       => 'Acceso abierto a la producción académica, científica y tecnológica.',
                'about_summary'       => '',
                'legal_footer'        => 'Repositorio digital de acceso abierto bajo políticas de preservación institucional.',
                'contact_email'       => '',
                'contact_phone'       => '',
                'contact_address'     => '',
            ],
        ];
    }

    /**
     * Sanitiza y normaliza un código hexadecimal de color.
     */
    public static function sanitizeHex(string $color, string $default = '#113b70'): string
    {
        $clean = trim($color);
        if (!str_starts_with($clean, '#')) {
            $clean = '#' . $clean;
        }
        $clean = strtolower($clean);

        // Convertir formato corto #abc a #aabbcc
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $clean, $matches)) {
            $clean = '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
        }

        if (preg_match('/^#[0-9a-f]{6}$/', $clean)) {
            return $clean;
        }

        return $default;
    }

    /**
     * Calcula la luminancia relativa según estándar WCAG 2.2.
     */
    public static function relativeLuminance(string $hexColor): float
    {
        $hex = ltrim(self::sanitizeHex($hexColor), '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calcula el ratio de contraste entre dos colores (1.0 a 21.0).
     */
    public static function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = self::relativeLuminance($color1);
        $l2 = self::relativeLuminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Deriva un color de texto contrastante (#ffffff o #152438) según el fondo.
     */
    public static function deriveOnColor(string $bgColor): string
    {
        $ratioWhite = self::calculateContrastRatio($bgColor, '#ffffff');
        return ($ratioWhite >= 4.5) ? '#ffffff' : '#152438';
    }

    /**
     * Compila un array de tokens a un bloque seguro de CSS :root { ... }.
     *
     * @param array<string,string> $tokens
     */
    public static function compileCss(array $tokens): string
    {
        $defaults = self::getDefaults()['tokens'];
        $merged = array_merge($defaults, $tokens);

        $allowedRadii = ['0px', '4px', '6px', '8px', '10px', '12px', '14px', '16px', '18px', '20px', '24px', '9999px'];
        $allowedFonts = [
            'system-ui, -apple-system, "Segoe UI", sans-serif',
            'Arial, Helvetica, sans-serif',
            'Georgia, "Times New Roman", serif',
            '"Roboto", system-ui, sans-serif',
            '"Inter", system-ui, sans-serif',
        ];

        $lines = [];
        $lines[] = ':root {';

        foreach ($merged as $key => $val) {
            $cssVar = '--' . str_replace('_', '-', $key);

            // Sanitización estricta según el tipo de variable
            if (str_contains($key, 'color') || str_contains($key, 'brand') || str_contains($key, 'bg') || str_contains($key, 'surface') || str_contains($key, 'text') || in_array($key, ['border', 'focus_ring', 'success', 'warning', 'danger', 'info'], true)) {
                $safeVal = self::sanitizeHex((string) $val, $defaults[$key] ?? '#000000');
            } elseif (str_contains($key, 'radius')) {
                $safeVal = in_array($val, $allowedRadii, true) ? $val : ($defaults[$key] ?? '12px');
            } elseif ($key === 'font_sans') {
                $safeVal = in_array($val, $allowedFonts, true) ? $val : $defaults['font_sans'];
            } elseif ($key === 'header_height') {
                $safeVal = preg_match('/^\d{2,3}px$/', (string) $val) ? $val : '76px';
            } elseif ($key === 'container_max') {
                $safeVal = preg_match('/^\d{3,4}px$/', (string) $val) ? $val : '1240px';
            } elseif (str_contains($key, 'motion')) {
                $safeVal = preg_match('/^\d{2,4}ms$/', (string) $val) ? $val : '200ms';
            } else {
                $safeVal = preg_match('/^[a-zA-Z0-9\s,\-\.\(\)\/%#]+$/', (string) $val) ? (string) $val : ($defaults[$key] ?? '');
            }

            $lines[] = "  {$cssVar}: {$safeVal};";
        }

        $lines[] = '}';
        return implode("\n", $lines);
    }

    /**
     * Obtiene el tema publicado de una institución.
     * Si no existe tema publicado, inicializa y devuelve el tema predeterminado.
     *
     * @return array<string,mixed>
     */
    public static function getPublishedTheme(int $institutionId): array
    {
        if (isset(self::$memoryCache[$institutionId]['published'])) {
            return self::$memoryCache[$institutionId]['published'];
        }

        try {
            $row = Database::selectOne(
                "SELECT * FROM institution_themes WHERE institution_id = ? AND status = 'published' ORDER BY version DESC LIMIT 1",
                [$institutionId]
            );

            if ($row !== null) {
                $theme = self::hydrateTheme($row);
                self::$memoryCache[$institutionId]['published'] = $theme;
                return $theme;
            }

            // Si aún no hay tema publicado, generar tema inicial desde la institución o defaults
            return self::createInitialPublishedTheme($institutionId);
        } catch (Throwable $e) {
            // Fallback robusto sin BD
            $defaults = self::getDefaults();
            return [
                'id'             => 0,
                'institution_id' => $institutionId,
                'version'        => 1,
                'status'         => 'published',
                'tokens'         => $defaults['tokens'],
                'layout'         => $defaults['layout'],
                'texts'          => $defaults['texts'],
                'created_at'     => gmdate('Y-m-d H:i:s'),
                'published_at'   => gmdate('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Obtiene el tema borrador (draft) de una institución si existe.
     *
     * @return array<string,mixed>|null
     */
    public static function getDraftTheme(int $institutionId): ?array
    {
        try {
            $row = Database::selectOne(
                "SELECT * FROM institution_themes WHERE institution_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1",
                [$institutionId]
            );

            return $row !== null ? self::hydrateTheme($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Guarda o actualiza un borrador para la institución.
     *
     * @param array<string,mixed> $tokens
     * @param array<string,mixed> $layout
     * @param array<string,mixed> $texts
     * @return array<string,mixed>
     */
    public static function saveDraft(int $institutionId, array $tokens, array $layout, array $texts, ?int $userId = null): array
    {
        $defaults = self::getDefaults();

        // Sanitizar tokens
        $cleanTokens = [];
        foreach ($defaults['tokens'] as $k => $defVal) {
            $val = (string) ($tokens[$k] ?? $defVal);
            if (str_contains($k, 'color') || str_contains($k, 'brand') || str_contains($k, 'bg') || str_contains($k, 'surface') || str_contains($k, 'text') || in_array($k, ['border', 'focus_ring', 'success', 'warning', 'danger', 'info'], true)) {
                $cleanTokens[$k] = self::sanitizeHex($val, $defVal);
            } else {
                $cleanTokens[$k] = $val;
            }
        }
        // Auto-calcular on-colors para garantizar legibilidad
        $cleanTokens['brand_on_primary']   = self::deriveOnColor($cleanTokens['brand_primary']);
        $cleanTokens['brand_on_secondary'] = self::deriveOnColor($cleanTokens['brand_secondary']);
        $cleanTokens['brand_on_accent']    = self::deriveOnColor($cleanTokens['brand_accent']);

        // Sanitizar layout
        $cleanLayout = array_merge($defaults['layout'], array_intersect_key($layout, $defaults['layout']));

        // Sanitizar textos
        $cleanTexts = [];
        foreach ($defaults['texts'] as $k => $defVal) {
            $cleanTexts[$k] = strip_tags(trim((string) ($texts[$k] ?? $defVal)));
        }

        $tokensJson = json_encode($cleanTokens, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $layoutJson = json_encode($cleanLayout, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $textsJson  = json_encode($cleanTexts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $pdo = Database::connection();
        $draft = self::getDraftTheme($institutionId);

        if ($draft !== null) {
            $stmt = $pdo->prepare(
                "UPDATE institution_themes
                 SET tokens_json = ?, layout_json = ?, texts_json = ?, created_by = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND institution_id = ?"
            );
            $stmt->execute([$tokensJson, $layoutJson, $textsJson, $userId, $draft['id'], $institutionId]);
            $draftId = (int) $draft['id'];
            $version = (int) $draft['version'];
        } else {
            $currentPub = self::getPublishedTheme($institutionId);
            $version = (int) ($currentPub['version'] ?? 1) + 1;

            $stmt = $pdo->prepare(
                "INSERT INTO institution_themes (institution_id, version, status, tokens_json, layout_json, texts_json, created_by)
                 VALUES (?, ?, 'draft', ?, ?, ?, ?)"
            );
            $stmt->execute([$institutionId, $version, $tokensJson, $layoutJson, $textsJson, $userId]);
            $draftId = (int) $pdo->lastInsertId();
        }

        AuditLogger::log(
            'appearance.draft_saved',
            'institution_themes',
            $draftId,
            null,
            json_encode(['institution_id' => $institutionId, 'version' => $version])
        );

        return [
            'id'             => $draftId,
            'institution_id' => $institutionId,
            'version'        => $version,
            'status'         => 'draft',
            'tokens'         => $cleanTokens,
            'layout'         => $cleanLayout,
            'texts'          => $cleanTexts,
        ];
    }

    /**
     * Publica el borrador actual (o tema dado) de forma atómica y transaccional.
     * Archiva temas publicados previos.
     *
     * @return array<string,mixed>
     */
    public static function publish(int $institutionId, ?int $userId = null): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $draft = self::getDraftTheme($institutionId);
            if ($draft === null) {
                // Si no hay borrador, publicar el tema actual incrementando versión
                $current = self::getPublishedTheme($institutionId);
                $draft = self::saveDraft($institutionId, $current['tokens'], $current['layout'], $current['texts'], $userId);
            }

            // Archivar temas publicados existentes
            $stmtArchive = $pdo->prepare(
                "UPDATE institution_themes
                 SET status = 'archived'
                 WHERE institution_id = ? AND status = 'published'"
            );
            $stmtArchive->execute([$institutionId]);

            // Marcar borrador como publicado
            $stmtPublish = $pdo->prepare(
                "UPDATE institution_themes
                 SET status = 'published', published_by = ?, published_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND institution_id = ?"
            );
            $stmtPublish->execute([$userId, $draft['id'], $institutionId]);

            // Actualizar también los colores primarios en la tabla institutions para redundancia
            $tokens = $draft['tokens'];
            $stmtInst = $pdo->prepare(
                "UPDATE institutions
                 SET primary_color = ?, secondary_color = ?, accent_color = ?
                 WHERE id = ?"
            );
            $stmtInst->execute([
                $tokens['brand_primary'] ?? '#113b70',
                $tokens['brand_secondary'] ?? '#2873bd',
                $tokens['brand_accent'] ?? '#e4ae35',
                $institutionId,
            ]);

            $pdo->commit();

            // Limpiar caché
            unset(self::$memoryCache[$institutionId]);

            AuditLogger::log(
                'appearance.published',
                'institution_themes',
                $draft['id'],
                null,
                json_encode(['institution_id' => $institutionId, 'version' => $draft['version']])
            );

            return self::getPublishedTheme($institutionId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Restaura una versión histórica previa del tema.
     *
     * @return array<string,mixed>
     */
    public static function restoreVersion(int $institutionId, int $themeId, ?int $userId = null): array
    {
        $row = Database::selectOne(
            "SELECT * FROM institution_themes WHERE id = ? AND institution_id = ?",
            [$themeId, $institutionId]
        );

        if ($row === null) {
            throw new \RuntimeException("Versión de tema no encontrada.");
        }

        $hydrated = self::hydrateTheme($row);
        // Guardar como borrador y luego publicar
        self::saveDraft($institutionId, $hydrated['tokens'], $hydrated['layout'], $hydrated['texts'], $userId);
        return self::publish($institutionId, $userId);
    }

    /**
     * Devuelve el historial de versiones de una institución.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getHistory(int $institutionId, int $limit = 10): array
    {
        $rows = Database::select(
            "SELECT id, version, status, created_at, published_at, created_by, published_by
             FROM institution_themes
             WHERE institution_id = ?
             ORDER BY id DESC LIMIT ?",
            [$institutionId, $limit]
        );

        return array_map(static fn (array $r): array => [
            'id'           => (int) $r['id'],
            'version'      => (int) $r['version'],
            'status'       => (string) $r['status'],
            'created_at'   => (string) $r['created_at'],
            'published_at' => (string) ($r['published_at'] ?? ''),
        ], $rows);
    }

    /**
     * Hidrata una fila de BD a un objeto de tema tipado.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function hydrateTheme(array $row): array
    {
        $defaults = self::getDefaults();
        $tokens = json_decode((string) $row['tokens_json'], true) ?: [];
        $layout = json_decode((string) $row['layout_json'], true) ?: [];
        $texts  = json_decode((string) $row['texts_json'], true) ?: [];

        return [
            'id'             => (int) $row['id'],
            'institution_id' => (int) $row['institution_id'],
            'version'        => (int) $row['version'],
            'status'         => (string) $row['status'],
            'tokens'         => array_merge($defaults['tokens'], $tokens),
            'layout'         => array_merge($defaults['layout'], $layout),
            'texts'          => array_merge($defaults['texts'], $texts),
            'created_at'     => (string) $row['created_at'],
            'published_at'   => (string) ($row['published_at'] ?? ''),
        ];
    }

    /**
     * Crea el tema inicial publicado para una institución recién instalada o sin tema.
     *
     * @return array<string,mixed>
     */
    private static function createInitialPublishedTheme(int $institutionId): array
    {
        $defaults = self::getDefaults();

        // Si la institución tiene colores propios definidos en la tabla institutions, respetarlos
        $inst = Database::selectOne("SELECT * FROM institutions WHERE id = ?", [$institutionId]);
        if ($inst !== null) {
            if (!empty($inst['primary_color'])) {
                $defaults['tokens']['brand_primary'] = self::sanitizeHex($inst['primary_color']);
                $defaults['tokens']['brand_on_primary'] = self::deriveOnColor($defaults['tokens']['brand_primary']);
            }
            if (!empty($inst['secondary_color'])) {
                $defaults['tokens']['brand_secondary'] = self::sanitizeHex($inst['secondary_color']);
                $defaults['tokens']['brand_on_secondary'] = self::deriveOnColor($defaults['tokens']['brand_secondary']);
            }
            if (!empty($inst['accent_color'])) {
                $defaults['tokens']['brand_accent'] = self::sanitizeHex($inst['accent_color']);
                $defaults['tokens']['brand_on_accent'] = self::deriveOnColor($defaults['tokens']['brand_accent']);
            }
            $defaults['texts']['site_name'] = (string) ($inst['name'] ?? '');
            $defaults['texts']['site_short'] = (string) ($inst['short_name'] ?? '');
            $defaults['texts']['contact_email'] = (string) ($inst['email'] ?? '');
            $defaults['texts']['contact_phone'] = (string) ($inst['phone'] ?? '');
            $defaults['texts']['contact_address'] = (string) ($inst['address'] ?? '');
        }

        $tokensJson = json_encode($defaults['tokens'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $layoutJson = json_encode($defaults['layout'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $textsJson  = json_encode($defaults['texts'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "INSERT INTO institution_themes (institution_id, version, status, tokens_json, layout_json, texts_json, published_at)
             VALUES (?, 1, 'published', ?, ?, ?, CURRENT_TIMESTAMP)"
        );
        $stmt->execute([$institutionId, $tokensJson, $layoutJson, $textsJson]);
        $id = (int) $pdo->lastInsertId();

        $theme = [
            'id'             => $id,
            'institution_id' => $institutionId,
            'version'        => 1,
            'status'         => 'published',
            'tokens'         => $defaults['tokens'],
            'layout'         => $defaults['layout'],
            'texts'          => $defaults['texts'],
            'created_at'     => gmdate('Y-m-d H:i:s'),
            'published_at'   => gmdate('Y-m-d H:i:s'),
        ];

        self::$memoryCache[$institutionId]['published'] = $theme;
        return $theme;
    }
}
