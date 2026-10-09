<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Motor de vistas PHP con layouts y componentes.
 * Las vistas no contienen SQL ni lógica de seguridad.
 */
final class View
{
    private const BASE = __DIR__ . '/../Views';

    /**
     * Renderiza una plantilla con layout.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/web'): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        $layoutData = $data + [
            'content' => $content,
            'authUser' => Auth::user(),
            'flashes' => Session::pullFlashes(),
            'csrfToken' => Csrf::token(),
        ];
        return self::partial($layout, $layoutData);
    }

    /**
     * Incluye un parcial (sin layout). La ruta es relativa a app/Views.
     *
     * @param array<string,mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::BASE . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Vista no encontrada: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /**
     * Componente reutilizable (app/Views/components/...).
     *
     * @param array<string,mixed> $data
     */
    public static function component(string $name, array $data = []): string
    {
        return self::partial('components/' . $name, $data);
    }
}
