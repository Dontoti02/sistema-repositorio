<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Manejo global de errores: identificador de incidente,
 * mensaje público discreto y detalle solo en log privado.
 */
final class ExceptionHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', config('app.debug') ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', storage_path('logs/php-error.log'));

        set_exception_handler([self::class, 'handle']);
        set_error_handler([self::class, 'error']);
        register_shutdown_function([self::class, 'shutdown']);
    }

    public static function handle(\Throwable $e): void
    {
        $response = self::toResponse($e);
        $response->send();
    }

    public static function toResponse(\Throwable $e): Response
    {
        $request = Request::capture();

        if ($e instanceof HttpException) {
            $status = $e->status();
            if ($status >= 500) {
                self::log($e);
            }
            if ($request->wantsJson()) {
                $payload = ['error' => $e->getMessage()];
                if ($e->errors() !== []) {
                    $payload['errors'] = $e->errors();
                    $payload['message'] = 'Los datos enviados no son válidos.';
                }
                return Response::json($payload, $status);
            }
            if ($e->errors() !== []) {
                // Reenvía errores de validación al formulario vía sesión.
                Session::put('_validation_errors', $e->errors());
                Session::put('_old_input', $request->all());
                $referer = (string) $request->header('referer', '');
                $base = (string) config('app.url', '');
                $sameHost = $referer !== ''
                    && str_starts_with($referer, ($request->isSecure() ? 'https://' : 'http://') . $request->host());
                if ($referer !== '' && (($base !== '' && str_starts_with($referer, $base)) || $sameHost)) {
                    return Response::redirect($referer);
                }
            }
            return self::errorPage($status, $e->getMessage());
        }

        $incident = self::log($e);

        if ($request->wantsJson()) {
            return Response::json([
                'error' => 'Ocurrió un error interno.',
                'incident_id' => $incident,
            ], 500);
        }
        return self::errorPage(500, 'Ocurrió un error interno. El incidente quedó registrado.', $incident);
    }

    public static function log(\Throwable $e): string
    {
        $incident = bin2hex(random_bytes(8));
        $line = sprintf(
            "[%s] INCIDENTE=%s %s: %s en %s:%d\n%s\n",
            gmdate('Y-m-d H:i:s'),
            $incident,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents(storage_path('logs/app.log'), $line, FILE_APPEND | LOCK_EX);
        return $incident;
    }

    private static function errorPage(int $status, string $message, ?string $incident = null): Response
    {
        try {
            $html = View::render('errors/error', [
                'status' => $status,
                'message' => $message,
                'incident' => $incident,
            ], 'layouts/web');
            return Response::html($html, $status);
        } catch (\Throwable) {
            $title = match ($status) {
                401 => 'Sesión requerida',
                403 => 'Acceso denegado',
                404 => 'Página no encontrada',
                405 => 'Método no permitido',
                419 => 'Sesión expirada',
                422 => 'Datos inválidos',
                429 => 'Demasiadas solicitudes',
                default => 'Error',
            };
            $body = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
                . '<title>' . e($title) . '</title></head><body style="font-family:system-ui;max-width:640px;margin:80px auto;padding:0 20px">'
                . '<h1>' . $status . ' — ' . e($title) . '</h1><p>' . e($message) . '</p>'
                . ($incident ? '<p><small>Identificador de incidente: ' . e($incident) . '</small></p>' : '')
                . '<p><a href="/">Volver al inicio</a></p></body></html>';
            return Response::html($body, $status);
        }
    }

    /** Convierte errores PHP en excepciones. */
    public static function error(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public static function shutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::log(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            if (!headers_sent()) {
                http_response_code(500);
                echo 'Ocurrió un error interno.';
            }
        }
    }
}
