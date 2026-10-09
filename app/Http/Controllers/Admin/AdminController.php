<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;

/**
 * Controlador base del panel administrativo.
 * Usa el layout de administración; la autorización es por permiso en cada acción.
 */
abstract class AdminController extends Controller
{
    /**
     * @param array<string,mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200, ?string $layout = 'layouts/admin'): Response
    {
        return parent::view($template, $data, $status, $layout ?? 'layouts/admin');
    }
}
