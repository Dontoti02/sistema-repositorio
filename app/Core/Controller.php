<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base: respuestas y vistas sin lógica de seguridad.
 */
abstract class Controller
{
    /**
     * @param array<string,mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200, ?string $layout = 'layouts/web'): Response
    {
        // Errores de validación reenviados por ExceptionHandler.
        $errors = Session::get('_validation_errors');
        $old = Session::get('_old_input');
        if (is_array($errors)) {
            $data['errors'] = $errors;
            Session::forget('_validation_errors');
        }
        if (is_array($old)) {
            $data['old'] = $old;
            Session::forget('_old_input');
        }
        return Response::html(View::render($template, $data, $layout), $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function back(Request $request, string $fallback = '/'): Response
    {
        $referer = (string) $request->header('referer', '');
        $base = (string) config('app.url', '');
        if ($referer !== '' && ($base === '' || str_starts_with($referer, $base))) {
            return Response::redirect($referer);
        }
        return Response::redirect($fallback);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * Valida y redirige con errores si falla.
     *
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     * @return array<string,mixed>
     */
    protected function validate(Request $request, array $rules, array $labels = []): array
    {
        return Validator::make($request->all(), $rules, $labels);
    }

    protected function authorize(string $permission): void
    {
        Authorization::authorize($permission);
    }
}
