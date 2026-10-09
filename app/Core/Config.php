<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Acceso a archivos de configuración con notación de puntos.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    private static string $path = '';

    public static function boot(string $path): void
    {
        self::$path = rtrim($path, '/');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $first = (string) ($segments[0] ?? '');
        if ($first !== '' && !array_key_exists($first, self::$items) && self::$path !== '') {
            self::$items[$first] = self::file($first);
        }
        $value = self::$items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &self::$items;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                break;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    public static function file(string $name): array
    {
        if (isset(self::$items['__file_' . $name])) {
            return self::$items['__file_' . $name];
        }
        $file = self::$path . '/' . $name . '.php';
        $data = is_file($file) ? (array) require $file : [];
        self::$items['__file_' . $name] = $data;
        return $data;
    }

    /** Recarga archivos después de que .env cambie (instalador). */
    public static function flush(): void
    {
        self::$items = [];
    }
}
