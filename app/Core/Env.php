<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cargador de variables de entorno (.env).
 * Parsea KEY=VALUE simple, comentarios # y comillas.
 */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                if (str_ends_with($value, $quote)) {
                    $value = substr($value, 1, -1);
                }
            }
            self::$values[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$values[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /** Normaliza valores booleanos conocidos. */
    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    /** Fuerza recarga (usado por el instalador). */
    public static function flush(): void
    {
        self::$values = [];
        self::$loaded = false;
    }
}
