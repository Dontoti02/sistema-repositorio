<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Domain\Repository\PublicRepository;

/**
 * Portal público: inicio y páginas institucionales.
 */
final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new PublicRepository();
        return $this->view('web/home', [
            'pageTitle' => ($repo->institutionName() ?? config('app.name')) . ' — Repositorio institucional',
            'metaDescription' => 'Repositorio digital de acceso abierto: investigaciones, proyectos, informes y material académico de la institución.',
            'stats' => $repo->stats(),
            'recent' => $repo->recentItems(6),
            'mostViewed' => $repo->mostViewedItems(6),
            'communities' => array_slice($repo->communities(), 0, 6),
        ]);
    }

    public function about(Request $request): Response
    {
        $inst = TenantContext::institution();
        return $this->view('web/about', [
            'pageTitle' => 'Acerca del repositorio',
            'metaDescription' => 'Presentación, política de depósito y lineamientos de acceso del repositorio institucional.',
            'inst' => $inst,
        ]);
    }

    public function policies(Request $request): Response
    {
        $inst = TenantContext::institution();
        return $this->view('web/policies', [
            'pageTitle' => 'Políticas y privacidad',
            'metaDescription' => 'Políticas de conservación, derechos de autor y protección de datos personales.',
            'inst' => $inst,
        ]);
    }

    public function contact(Request $request): Response
    {
        $inst = TenantContext::institution();
        $sent = false;
        if ($request->method() === 'POST') {
            $data = $this->validate($request, [
                'name' => 'required|max:190',
                'email' => 'required|email|max:190',
                'message' => 'required|max:4000',
            ], ['name' => 'Nombre', 'email' => 'Correo', 'message' => 'Mensaje']);
            Database::statement(
                'INSERT INTO email_outbox (institution_id, to_email, subject, body, status, created_at)
                 VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    TenantContext::institutionId(),
                    (string) ($inst['support_email'] ?? $inst['email'] ?? 'no-responder@example.invalid'),
                    'Consulta desde el repositorio — ' . $data['name'],
                    "De: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
                    'pending',
                ]
            );
            $this->flash('success', 'Mensaje registrado. El equipo del repositorio le responderá a la brevedad.');
            return $this->redirect('/contacto');
        }
        return $this->view('web/contact', [
            'pageTitle' => 'Contacto',
            'metaDescription' => 'Contacto con el equipo responsable del repositorio institucional.',
            'inst' => $inst,
        ]);
    }

    public function faq(Request $request): Response
    {
        return $this->view('web/faq', [
            'pageTitle' => 'Preguntas frecuentes',
            'metaDescription' => 'Preguntas frecuentes sobre uso, dep�sito y consulta del repositorio.',
        ]);
    }

    public function manual(Request $request): Response
    {
        return $this->view('web/manual', [
            'pageTitle' => 'Manual de uso',
            'metaDescription' => 'Manual de uso del repositorio: consultar, citar y depositar documentos.',
        ]);
    }


    public function stats(Request $request): Response
    {
        $repo = new PublicRepository();
        $perYear = Database::select(
            "SELECT YEAR(v.issued_at) AS year, COUNT(*) AS total
             FROM items i JOIN item_versions v ON v.id = i.current_version_id
             WHERE i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
             GROUP BY year ORDER BY year DESC LIMIT 30",
            [TenantContext::institutionId()]
        );
        $perType = Database::select(
            "SELECT dt.name AS type_name, COUNT(*) AS total
             FROM items i LEFT JOIN document_types dt ON dt.id = i.document_type_id
             WHERE i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
             GROUP BY dt.name ORDER BY total DESC LIMIT 20",
            [TenantContext::institutionId()]
        );
        return $this->view('web/stats', [
            'pageTitle' => 'Estadísticas',
            'metaDescription' => 'Indicadores públicos del repositorio: recursos publicados, comunidades, colecciones y descargas.',
            'stats' => $repo->stats(),
            'perYear' => $perYear,
            'perType' => $perType,
        ]);
    }

    public function sitemap(Request $request): Response
    {
        $repo = new PublicRepository();
        $items = Database::select(
            "SELECT public_id, updated_at FROM items
             WHERE institution_id = ? AND status = 'PUBLISHED' AND deleted_at IS NULL
               AND access_level IN ('open','metadata_only','embargoed','restricted')
             ORDER BY updated_at DESC LIMIT 50000",
            [TenantContext::institutionId()]
        );
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (['/', '/comunidades', '/busqueda-avanzada', '/acerca-de'] as $path) {
            $xml .= '  <url><loc>' . e(url($path)) . '</loc></url>' . "\n";
        }
        foreach ($items as $row) {
            $xml .= '  <url><loc>' . e(url('/recursos/' . $row['public_id'])) . '</loc>'
                . '<lastmod>' . e(substr((string) $row['updated_at'], 0, 10)) . '</lastmod></url>' . "\n";
        }
        $xml .= '</urlset>';
        return Response::xml($xml);
    }
}
