<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use finfo;
use RuntimeException;
use Throwable;

/**
 * Servicio de gestión de activos visuales de marca institucional.
 * Maneja carga segura, validación MIME con finfo, dimensiones reales,
 * almacenamiento privado y entrega controlada con cache busting.
 */
final class BrandAssetService
{
    public const KIND_LOGO_PRIMARY   = 'logo_primary';
    public const KIND_LOGO_ALTERNATE = 'logo_alternate';
    public const KIND_LOGO_COMPACT   = 'logo_compact';
    public const KIND_FAVICON        = 'favicon';
    public const KIND_HERO           = 'hero';
    public const KIND_DEFAULT_COVER  = 'default_cover';

    public const ALLOWED_KINDS = [
        self::KIND_LOGO_PRIMARY,
        self::KIND_LOGO_ALTERNATE,
        self::KIND_LOGO_COMPACT,
        self::KIND_FAVICON,
        self::KIND_HERO,
        self::KIND_DEFAULT_COVER,
    ];

    private const MIME_EXTENSIONS = [
        'image/png'     => 'png',
        'image/jpeg'    => 'jpg',
        'image/webp'    => 'webp',
        'image/x-icon'  => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    /**
     * Sube y valida un activo de marca institucional.
     *
     * @param array<string,mixed> $uploadedFile Array de $_FILES['archivo']
     * @return array<string,mixed>
     */
    public static function uploadAsset(int $institutionId, string $kind, array $uploadedFile, ?int $userId = null): array
    {
        if (!in_array($kind, self::ALLOWED_KINDS, true)) {
            throw new RuntimeException("Tipo de activo no permitido: {$kind}");
        }

        $error = (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorMessage($error));
        }

        $tmpPath = (string) ($uploadedFile['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpPath)) {
            throw new RuntimeException('El archivo subido no es válido.');
        }

        $size = (int) ($uploadedFile['size'] ?? 0);
        $maxBytes = ($kind === self::KIND_HERO) ? 5 * 1024 * 1024 : 2 * 1024 * 1024; // 5MB hero, 2MB logo/ico
        if ($size > $maxBytes) {
            $mb = round($maxBytes / (1024 * 1024));
            throw new RuntimeException("El archivo excede el tamaño máximo permitido de {$mb}MB.");
        }

        // Validación MIME estricta vía finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpPath);

        // Si es favicon, aceptar también ICO
        if ($kind === self::KIND_FAVICON) {
            $allowedMimes = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'];
            if (!in_array($mime, $allowedMimes, true)) {
                // Comprobación adicional de firma ICO
                $fp = fopen($tmpPath, 'rb');
                $header = $fp ? fread($fp, 4) : '';
                if ($fp) fclose($fp);
                if ($header === "\x00\x00\x01\x00") {
                    $mime = 'image/x-icon';
                } else {
                    throw new RuntimeException('El favicon debe ser una imagen PNG o un archivo ICO válido.');
                }
            }
        } else {
            $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
            if (!in_array($mime, $allowedMimes, true)) {
                throw new RuntimeException("Formato no permitido ({$mime}). Solo se aceptan imágenes PNG, JPEG o WebP.");
            }
        }

        // Obtener dimensiones reales con getimagesize
        $imgInfo = @getimagesize($tmpPath);
        $width = $imgInfo ? (int) $imgInfo[0] : null;
        $height = $imgInfo ? (int) $imgInfo[1] : null;

        // Comprobaciones dimensionales razonables
        if ($kind === self::KIND_FAVICON && $width !== null && $height !== null) {
            if ($width > 512 || $height > 512) {
                throw new RuntimeException('El favicon no debe superar 512x512 píxeles.');
            }
        }

        $sha256 = hash_file('sha256', $tmpPath) ?: bin2hex(random_bytes(16));
        $ext = self::MIME_EXTENSIONS[$mime] ?? 'bin';
        $storageDir = storage_path("private/brands/{$institutionId}");
        @mkdir($storageDir, 0775, true);

        $filename = "{$kind}_" . substr($sha256, 0, 16) . ".{$ext}";
        $destPath = "{$storageDir}/{$filename}";

        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new RuntimeException('No se pudo guardar la imagen en el almacenamiento del servidor.');
        }
        @chmod($destPath, 0644);

        $storageKey = "brands/{$institutionId}/{$filename}";
        $pdo = Database::connection();

        // Eliminar activo anterior del mismo tipo en BD si existe
        $oldAsset = self::getAsset($institutionId, $kind);
        if ($oldAsset !== null) {
            $oldPath = storage_path('private/' . $oldAsset['storage_key']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
            $pdo->prepare("DELETE FROM institution_brand_assets WHERE institution_id = ? AND kind = ?")
                ->execute([$institutionId, $kind]);
        }

        // Insertar nuevo activo
        $stmt = $pdo->prepare(
            "INSERT INTO institution_brand_assets
             (institution_id, kind, storage_key, mime_type, width, height, size_bytes, sha256, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $institutionId,
            $kind,
            $storageKey,
            $mime,
            $width,
            $height,
            $size,
            $sha256,
            $userId,
        ]);
        $assetId = (int) $pdo->lastInsertId();

        // Sincronizar columna compatible en tabla institutions
        self::syncInstitutionColumn($institutionId, $kind, $storageKey);

        AuditLogger::log('brand_asset.uploaded', 'institution_brand_assets', $assetId, null, json_encode([
            'institution_id' => $institutionId,
            'kind'           => $kind,
            'mime'           => $mime,
            'size'           => $size,
        ]));

        return [
            'id'          => $assetId,
            'kind'        => $kind,
            'storage_key' => $storageKey,
            'url'         => self::getAssetUrl($institutionId, $kind),
            'mime_type'   => $mime,
            'width'       => $width,
            'height'      => $height,
            'size_bytes'  => $size,
        ];
    }

    /**
     * Elimina un activo de marca de la institución.
     */
    public static function deleteAsset(int $institutionId, string $kind, ?int $userId = null): bool
    {
        $asset = self::getAsset($institutionId, $kind);
        if ($asset === null) {
            return false;
        }

        $filePath = storage_path('private/' . $asset['storage_key']);
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        Database::connection()
            ->prepare("DELETE FROM institution_brand_assets WHERE institution_id = ? AND kind = ?")
            ->execute([$institutionId, $kind]);

        // Limpiar columna en tabla institutions
        self::syncInstitutionColumn($institutionId, $kind, null);

        AuditLogger::log('brand_asset.deleted', 'institution_brand_assets', (int) $asset['id'], null, json_encode([
            'institution_id' => $institutionId,
            'kind'           => $kind,
        ]));

        return true;
    }

    /**
     * Obtiene los metadatos de un activo de marca.
     *
     * @return array<string,mixed>|null
     */
    public static function getAsset(int $institutionId, string $kind): ?array
    {
        try {
            $row = Database::selectOne(
                "SELECT * FROM institution_brand_assets WHERE institution_id = ? AND kind = ? ORDER BY id DESC LIMIT 1",
                [$institutionId, $kind]
            );
            return $row;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Genera la URL pública de entrega del activo con parámetro de versión para cache busting.
     */
    public static function getAssetUrl(int $institutionId, string $kind): ?string
    {
        $asset = self::getAsset($institutionId, $kind);
        if ($asset === null) {
            return null;
        }

        $version = substr((string) ($asset['sha256'] ?? '1'), 0, 8);
        return url("assets/marca/{$institutionId}/{$kind}?v={$version}");
    }

    /**
     * Sirve el activo binario con cabeceras HTTP de caché y seguridad.
     */
    public static function serveAsset(int $institutionId, string $kind): Response
    {
        $asset = self::getAsset($institutionId, $kind);
        if ($asset === null) {
            return new Response(404, ['Content-Type' => 'text/plain'], 'Activo de marca no encontrado.');
        }

        $filePath = storage_path('private/' . $asset['storage_key']);
        if (!is_file($filePath)) {
            return new Response(404, ['Content-Type' => 'text/plain'], 'Archivo físico no encontrado.');
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return new Response(500, ['Content-Type' => 'text/plain'], 'Error al leer el archivo.');
        }

        $mime = (string) ($asset['mime_type'] ?? 'image/png');
        $etag = '"' . ($asset['sha256'] ?? md5($content)) . '"';

        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientEtag === $etag) {
            return new Response(304, [], '');
        }

        return new Response(200, [
            'Content-Type'        => $mime,
            'Content-Length'      => (string) strlen($content),
            'Cache-Control'       => 'public, max-age=604800, immutable',
            'ETag'                => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ], $content);
    }

    /**
     * Sincroniza las columnas históricas de institutions para retrocompatibilidad.
     */
    private static function syncInstitutionColumn(int $institutionId, string $kind, ?string $storageKey): void
    {
        $column = match ($kind) {
            self::KIND_LOGO_PRIMARY => 'logo_path',
            self::KIND_FAVICON      => 'favicon_path',
            self::KIND_HERO         => 'hero_image_path',
            default                 => null,
        };

        if ($column !== null) {
            Database::connection()
                ->prepare("UPDATE institutions SET {$column} = ? WHERE id = ?")
                ->execute([$storageKey, $institutionId]);
        }
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL   => 'La carga se interrumpió antes de completar.',
            UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
            default              => 'Error durante la carga del archivo (código ' . $code . ').',
        };
    }
}
