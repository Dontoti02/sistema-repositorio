<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;

if (!function_exists('env')) {
    /**
     * Lee una variable de entorno con valor por defecto.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = Env::get($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    /**
     * Lee configuración: config('app.url') o config('app')->['url'] vía file.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('config_file')) {
    function config_file(string $name): array
    {
        return Config::file($name);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $base = base_path('storage');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $base = base_path('public');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Escape de salida HTML seguro.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * URL absoluta a partir de ruta relativa.
     */
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        if ($path === '') {
            return $base . '/';
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('now_utc')) {
    /**
     * Fecha/hora actual en UTC (almacenamiento).
     */
    function now_utc(string $format = 'Y-m-d H:i:s'): string
    {
        return gmdate($format);
    }
}

if (!function_exists('slugify')) {
    /**
     * Identificador web en minúsculas: letras sin acentos, números y guiones.
     */
    function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        if (function_exists('iconv')) {
            $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($translit) && $translit !== '') {
                $text = $translit;
            }
        }
        $text = str_replace(['ñ', 'ü'], ['n', 'u'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}

if (!function_exists('now_lima')) {
    /**
     * Fecha/hora actual en zona America/Lima (presentación).
     */
    function now_lima(string $format = 'Y-m-d H:i:s'): string
    {
        $tz = new DateTimeZone('America/Lima');
        return (new DateTime('now', $tz))->format($format);
    }
}

if (!function_exists('format_date_es')) {
    /**
     * Formatea una fecha UTC a presentación en español (America/Lima).
     */
    function format_date_es(?string $utc, string $format = 'd/m/Y H:i'): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        try {
            $dt = new DateTime($utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('America/Lima'));
            $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $salida = str_replace(
                ['F', 'M', 'd', 'Y', 'H', 'i', 'j'],
                ['MMMM', 'MMM', 'dd', 'yyyy', 'HH', 'mm', 'd'],
                $format
            );
            $salida = str_replace(
                ['MMMM', 'MMM', 'dd', 'yyyy', 'HH', 'mm'],
                [
                    $meses[(int) $dt->format('n')],
                    ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][(int) $dt->format('n')],
                    $dt->format('d'),
                    $dt->format('Y'),
                    $dt->format('H'),
                    $dt->format('i'),
                ],
                $salida
            );
            return $dt->format($salida);
        } catch (Throwable) {
            return $utc;
        }
    }
}

if (!function_exists('abort')) {
    /**
     * Aborta la ejecución con una excepción HTTP.
     */
    function abort(int $status, string $message = ''): never
    {
        throw new \App\Core\HttpException($status, $message);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to, int $status = 302): \App\Core\Response
    {
        return \App\Core\Response::redirect($to, $status);
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): \App\Core\Response
    {
        return \App\Core\Response::json($data, $status);
    }
}
