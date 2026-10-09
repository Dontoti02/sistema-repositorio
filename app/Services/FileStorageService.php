<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Almacenamiento privado: claves generadas por servidor, nunca rutas del cliente.
 */
final class FileStorageService
{
    private string $root;

    public function __construct()
    {
        $configured = (string) (Config::file('storage')['private_path'] ?? '');
        $this->root = $configured !== '' ? rtrim($configured, '/\\') : storage_path('private');
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * Genera una clave de almacenamiento única e irreal de adivinar.
     *
     * @param array<string,mixed> $file Arreglo $_FILES['archivo']
     */
    public function makeKey(int $institutionId, array $file): string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?? '';
        $uuid = bin2hex(random_bytes(16));
        return sprintf(
            '%d/%s/%s/%s%s',
            $institutionId,
            gmdate('Y'),
            gmdate('m'),
            $uuid,
            $ext !== '' ? '.' . $ext : ''
        );
    }

    public function absolutePath(string $storageKey): string
    {
        // Defensa contra path traversal: se normaliza y se exige bajo la raíz.
        $key = str_replace(['..', "\0"], '', $storageKey);
        $key = ltrim(str_replace('\\', '/', $key), '/');
        $full = $this->root . '/' . $key;
        $realRoot = realpath($this->root);
        if ($realRoot !== false) {
            $real = realpath(dirname($full));
            if ($real !== false && !str_starts_with($real, $realRoot)) {
                throw new \RuntimeException('Clave de almacenamiento fuera de la raíz privada.');
            }
        }
        return $full;
    }

    /**
     * Guarda un archivo subido de forma atómica. Devuelve la clave.
     *
     * @param array<string,mixed> $file
     */
    public function store(int $institutionId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        $size = (int) ($file['size'] ?? 0);
        $maxBytes = (int) Config::file('storage')['upload_max_mb'] * 1024 * 1024;
        if ($size > $maxBytes) {
            throw new \RuntimeException('El archivo excede el tamaño máximo permitido.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            throw new \RuntimeException('Archivo no válido.');
        }
        $key = $this->makeKey($institutionId, $file);
        $dest = $this->absolutePath($key);
        @mkdir(dirname($dest), 0775, true);
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('No se pudo guardar el archivo en el almacenamiento privado.');
        }
        @chmod($dest, 0640);
        return [
            'storage_key' => $key,
            'size' => filesize($dest) ?: $size,
            'sha256' => hash_file('sha256', $dest) ?: '',
            'mime' => $this->detectMime($dest),
            'original_filename' => basename((string) ($file['name'] ?? 'archivo')),
        ];
    }

    /**
     * Copia un archivo existente ya validado hacia una clave nueva.
     */
    public function putContents(string $storageKey, string $content): void
    {
        $dest = $this->absolutePath($storageKey);
        @mkdir(dirname($dest), 0775, true);
        file_put_contents($dest, $content);
        @chmod($dest, 0640);
    }

    public function delete(string $storageKey): void
    {
        $path = $this->absolutePath($storageKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function exists(string $storageKey): bool
    {
        return is_file($this->absolutePath($storageKey));
    }

    public function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return (string) ($finfo->file($path) ?: 'application/octet-stream');
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite del servidor.',
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo.',
            default => 'Error al subir el archivo (código ' . $code . ').',
        };
    }
}
