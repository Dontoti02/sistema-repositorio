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
 * Búsqueda general y avanzada con filtros conservados en la URL.
 */
final class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $page = max(1, $request->int('pagina', 1));
        $perPage = (int) config('repository.page_size_public', 12);

        $repo = new PublicRepository();
        $result = $repo->search($filters, $page, $perPage);

        if (!empty($filters['q'])) {
            $this->recordSearch((string) $filters['q']);
        }

        return $this->view('web/search', [
            'pageTitle' => !empty($filters['q']) ? 'Resultados para: ' . $filters['q'] : 'Buscar en el repositorio',
            'metaDescription' => 'Búsqueda en el repositorio institucional.',
            'filters' => $filters,
            'result' => $result,
            'page' => $page,
            'perPage' => $perPage,
            'types' => Database::select(
                'SELECT name, slug FROM document_types WHERE institution_id = ? AND is_active = 1 ORDER BY sort_order',
                [TenantContext::institutionId()]
            ),
            'communities' => $repo->communities(),
            'programs' => $repo->programs(),
            'languages' => Database::select(
                "SELECT term_value, term FROM vocabulary_terms vt
                 JOIN controlled_vocabularies cv ON cv.id = vt.vocabulary_id
                 WHERE cv.slug = 'languages' AND vt.is_active = 1 ORDER BY vt.sort_order"
            ),
            'years' => Database::select(
                "SELECT YEAR(v.issued_at) AS year, COUNT(*) AS total FROM items i
                 JOIN item_versions v ON v.id = i.current_version_id
                 WHERE i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL AND v.issued_at IS NOT NULL
                 GROUP BY year ORDER BY year DESC LIMIT 60",
                [TenantContext::institutionId()]
            ),
        ]);
    }

    public function advanced(Request $request): Response
    {
        return $this->view('web/advanced', [
            'pageTitle' => 'Búsqueda avanzada',
            'metaDescription' => 'Combine campos y operadores para localizar recursos del repositorio.',
            'types' => Database::select(
                'SELECT name, slug FROM document_types WHERE institution_id = ? AND is_active = 1 ORDER BY sort_order',
                [TenantContext::institutionId()]
            ),
            'communities' => (new PublicRepository())->communities(),
            'programs' => (new PublicRepository())->programs(),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function filters(Request $request): array
    {
        $allowed = ['q', 'tipo', 'coleccion', 'comunidad', 'programa', 'autor', 'idioma',
            'anio_desde', 'anio_hasta', 'acceso', 'orden', 'palabra_clave'];
        $filters = [];
        foreach ($allowed as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim(mb_substr($value, 0, 200));
            }
        }
        if (isset($filters['orden']) && !in_array($filters['orden'], ['relevancia', 'fecha', 'titulo', 'consultas'], true)) {
            $filters['orden'] = 'relevancia';
        }
        return $filters;
    }

    /** Registra la búsqueda agregada (sin datos de visitantes individuales). */
    private function recordSearch(string $term): void
    {
        if ($term === '') {
            return;
        }
        try {
            $term = mb_substr($term, 0, 190);
            $hash = hash('sha256', mb_strtolower($term));
            Database::statement(
                'INSERT INTO search_aggregates (institution_id, term_hash, term, searches, last_at)
                 VALUES (?, ?, ?, 1, UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE searches = searches + 1, last_at = UTC_TIMESTAMP(), term = VALUES(term)',
                [TenantContext::institutionId(), $hash, $term]
            );
        } catch (\Throwable) {
            // La analítica agregada no debe bloquear la búsqueda.
        }
    }
}
