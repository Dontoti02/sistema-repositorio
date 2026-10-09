<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

/**
 * API REST v1 — solo lectura pública (7.5).
 * Recursos publicados, comunidades y colecciones con paginación,
 * filtros, rate limiting y errores JSON consistentes.
 */
final class ApiController extends Controller
{
    /** GET /api/v1 */
    public function index(Request $request): Response
    {
        return Response::json([
            'nombre' => (string) (TenantContext::institution()['name'] ?? config('app.name')),
            'version' => 'v1',
            'descripcion' => 'API REST de solo lectura del repositorio institucional. Solo expone recursos publicados.',
            'endpoints' => [
                'GET /api/v1' => 'Este índice.',
                'GET /api/v1/documentacion' => 'Documentación detallada de la API.',
                'GET /api/v1/recursos' => 'Listado paginado de recursos publicados.',
                'GET /api/v1/recursos/{public_id}' => 'Detalle de un recurso por su identificador público.',
                'GET /api/v1/comunidades' => 'Comunidades públicas.',
                'GET /api/v1/comunidades/{slug}' => 'Detalle de comunidad con sus colecciones.',
                'GET /api/v1/colecciones/{slug}' => 'Detalle de una colección.',
                'GET /api/v1/colecciones/{slug}/recursos' => 'Recursos publicados de una colección.',
            ],
            'limitacion' => '60 solicitudes por minuto por sesión.',
            'documentacion' => url('/api/v1/documentacion'),
        ]);
    }

    /** GET /api/v1/documentacion */
    public function docs(Request $request): Response
    {
        return Response::json([
            'version' => 'v1',
            'url_base' => url('/api/v1'),
            'autenticacion' => 'No requiere. La API es pública y de solo lectura.',
            'formato' => 'application/json; charset=utf-8',
            'errores' => [
                '400' => 'Parámetros inválidos: {"error": "..."}',
                '404' => 'Recurso no encontrado: {"error": "..."}',
                '429' => 'Límite de solicitudes excedido: {"error": "..."}',
            ],
            'paginacion' => [
                'parametros' => ['page' => 'Página (desde 1)', 'per_page' => 'Resultados por página (1-100, por defecto 20)'],
                'meta' => 'meta.page, meta.per_page, meta.total, meta.last_page',
                'links' => 'links.self, links.next, links.prev',
            ],
            'filtros_recursos' => [
                'q' => 'Búsqueda en título y resumen',
                'coleccion' => 'Slug de la colección',
                'desde' => 'Fecha de emisión desde (AAAA-MM-DD)',
                'hasta' => 'Fecha de emisión hasta (AAAA-MM-DD)',
                'orden' => 'recientes (por defecto), titulo, fecha',
            ],
            'ejemplos' => [
                'GET ' . url('/api/v1/recursos') . '?per_page=2',
                'GET ' . url('/api/v1/recursos/RI-2026-000002'),
                'GET ' . url('/api/v1/comunidades'),
                'GET ' . url('/api/v1/colecciones/sistemas-productivos/recursos'),
            ],
        ]);
    }

    /** GET /api/v1/recursos */
    public function items(Request $request): Response
    {
        $page = max(1, $request->int('page', 1));
        $perPage = min(100, max(1, $request->int('per_page', 20)));
        $q = trim($request->str('q'));
        $coleccion = trim($request->str('coleccion'));
        $desde = trim($request->str('desde'));
        $hasta = trim($request->str('hasta'));
        $orden = $request->str('orden', 'recientes');

        foreach (['desde' => $desde, 'hasta' => $hasta] as $name => $value) {
            if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new HttpException(400, "Parámetro «{$name}» inválido: use AAAA-MM-DD.");
            }
        }
        if (!in_array($orden, ['recientes', 'titulo', 'fecha'], true)) {
            throw new HttpException(400, 'Parámetro «orden» inválido: use recientes, titulo o fecha.');
        }

        $instId = TenantContext::institutionId();
        [$where, $params] = $this->itemFilters($instId, $q, $coleccion, $desde, $hasta);

        $orderSql = match ($orden) {
            'titulo' => 'ORDER BY v.title ASC',
            'fecha' => 'ORDER BY v.issued_at DESC, i.id DESC',
            default => 'ORDER BY i.id DESC',
        };

        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             WHERE ' . $where,
            $params
        )['c'] ?? 0);

        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $rows = Database::select(
            $this->itemSelect() . ' WHERE ' . $where . ' ' . $orderSql . ' LIMIT ' . (int) $perPage
            . ' OFFSET ' . (int) (($page - 1) * $perPage),
            $params
        );

        $data = array_map(fn (array $row): array => $this->summary($row), $rows);

        return Response::json([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'links' => [
                'self' => url('/api/v1/recursos?page=' . $page . '&per_page=' . $perPage),
                'next' => $page < $lastPage ? url('/api/v1/recursos?page=' . ($page + 1) . '&per_page=' . $perPage) : null,
                'prev' => $page > 1 ? url('/api/v1/recursos?page=' . ($page - 1) . '&per_page=' . $perPage) : null,
            ],
        ]);
    }

    /** GET /api/v1/recursos/{public_id} */
    public function item(Request $request): Response
    {
        $publicId = trim($request->route('id'));
        $row = Database::selectOne(
            $this->itemSelect() . ' WHERE ' . 'i.institution_id = ? AND i.public_id = ? AND i.deleted_at IS NULL',
            [TenantContext::institutionId(), $publicId]
        );
        if ($row === null || (string) $row['status'] !== 'PUBLISHED') {
            throw new HttpException(404, 'Recurso no encontrado.');
        }
        return Response::json(['data' => $this->detail($row)]);
    }

    /** GET /api/v1/comunidades */
    public function communities(Request $request): Response
    {
        $rows = Database::select(
            'SELECT id, name, slug, description FROM communities
             WHERE institution_id = ? AND deleted_at IS NULL AND is_public = 1
             ORDER BY sort_order, name',
            [TenantContext::institutionId()]
        );
        $data = array_map(static fn (array $c): array => [
            'nombre' => (string) $c['name'],
            'slug' => (string) $c['slug'],
            'descripcion' => $c['description'] !== null ? (string) $c['description'] : null,
            'url' => url('/comunidades/' . $c['slug']),
            'api' => url('/api/v1/comunidades/' . $c['slug']),
        ], $rows);

        return Response::json(['data' => $data, 'meta' => ['total' => count($data)]]);
    }

    /** GET /api/v1/comunidades/{slug} */
    public function community(Request $request): Response
    {
        $slug = trim($request->route('slug'));
        $community = Database::selectOne(
            'SELECT id, name, slug, description FROM communities
             WHERE institution_id = ? AND slug = ? AND deleted_at IS NULL AND is_public = 1',
            [TenantContext::institutionId(), $slug]
        );
        if ($community === null) {
            throw new HttpException(404, 'Comunidad no encontrada.');
        }
        $collections = Database::select(
            'SELECT name, slug, description FROM collections
             WHERE institution_id = ? AND community_id = ? AND deleted_at IS NULL AND is_public = 1
             ORDER BY sort_order, name',
            [TenantContext::institutionId(), (int) $community['id']]
        );

        return Response::json(['data' => [
            'nombre' => (string) $community['name'],
            'slug' => (string) $community['slug'],
            'descripcion' => $community['description'] !== null ? (string) $community['description'] : null,
            'url' => url('/comunidades/' . $community['slug']),
            'colecciones' => array_map(static fn (array $c): array => [
                'nombre' => (string) $c['name'],
                'slug' => (string) $c['slug'],
                'descripcion' => $c['description'] !== null ? (string) $c['description'] : null,
                'url' => url('/colecciones/' . $c['slug']),
                'api_recursos' => url('/api/v1/colecciones/' . $c['slug'] . '/recursos'),
            ], $collections),
        ]]);
    }

    /** GET /api/v1/colecciones/{slug} */
    public function collection(Request $request): Response
    {
        $collection = $this->publicCollection(trim($request->route('slug')));
        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM items i
             WHERE i.institution_id = ? AND i.collection_id = ? AND i.status = ? AND i.deleted_at IS NULL',
            [TenantContext::institutionId(), (int) $collection['id'], 'PUBLISHED']
        )['c'] ?? 0);

        return Response::json(['data' => [
            'nombre' => (string) $collection['name'],
            'slug' => (string) $collection['slug'],
            'descripcion' => $collection['description'] !== null ? (string) $collection['description'] : null,
            'comunidad' => (string) $collection['community_name'],
            'url' => url('/colecciones/' . $collection['slug']),
            'recursos_publicados' => $total,
            'api_recursos' => url('/api/v1/colecciones/' . $collection['slug'] . '/recursos'),
        ]]);
    }

    /** GET /api/v1/colecciones/{slug}/recursos */
    public function collectionItems(Request $request): Response
    {
        $collection = $this->publicCollection(trim($request->route('slug')));
        $page = max(1, $request->int('page', 1));
        $perPage = min(100, max(1, $request->int('per_page', 20)));

        $instId = TenantContext::institutionId();
        $where = 'i.institution_id = ? AND i.collection_id = ? AND i.status = \'PUBLISHED\' AND i.deleted_at IS NULL';
        $params = [$instId, (int) $collection['id']];

        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM items i WHERE ' . $where,
            $params
        )['c'] ?? 0);
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $rows = Database::select(
            $this->itemSelect() . ' WHERE ' . $where . ' ORDER BY i.id DESC LIMIT ' . (int) $perPage
            . ' OFFSET ' . (int) (($page - 1) * $perPage),
            $params
        );

        return Response::json([
            'data' => array_map(fn (array $row): array => $this->summary($row), $rows),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => $lastPage],
            'links' => [
                'self' => url('/api/v1/colecciones/' . $collection['slug'] . '/recursos?page=' . $page . '&per_page=' . $perPage),
                'next' => $page < $lastPage ? url('/api/v1/colecciones/' . $collection['slug'] . '/recursos?page=' . ($page + 1) . '&per_page=' . $perPage) : null,
                'prev' => $page > 1 ? url('/api/v1/colecciones/' . $collection['slug'] . '/recursos?page=' . ($page - 1) . '&per_page=' . $perPage) : null,
            ],
        ]);
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private function itemFilters(int $instId, string $q, string $coleccion, string $desde, string $hasta): array
    {
        $where = 'i.institution_id = ? AND i.status = \'PUBLISHED\' AND i.deleted_at IS NULL AND c.deleted_at IS NULL';
        $params = [$instId];
        if ($q !== '') {
            $where .= ' AND (v.title LIKE ? OR v.abstract LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($coleccion !== '') {
            $where .= ' AND c.slug = ?';
            $params[] = $coleccion;
        }
        if ($desde !== '') {
            $where .= ' AND v.issued_at >= ?';
            $params[] = $desde;
        }
        if ($hasta !== '') {
            $where .= ' AND v.issued_at <= ?';
            $params[] = $hasta;
        }
        return [$where, $params];
    }

    private function itemSelect(): string
    {
        return 'SELECT i.id, i.public_id, i.status, i.access_level, i.embargo_until, i.slug,
                i.created_at, i.updated_at, i.collection_id,
                v.title, v.abstract, v.language_code, v.issued_at,
                c.name AS collection_name, c.slug AS collection_slug,
                cm.name AS community_name, cm.slug AS community_slug,
                dt.name AS document_type_name, dt.slug AS document_type_slug
                FROM items i
                JOIN item_versions v ON v.id = i.current_version_id
                JOIN collections c ON c.id = i.collection_id
                JOIN communities cm ON cm.id = c.community_id
                LEFT JOIN document_types dt ON dt.id = i.document_type_id';
    }

    /** @return array<string,mixed> */
    private function summary(array $row): array
    {
        return [
            'public_id' => (string) $row['public_id'],
            'titulo' => (string) $row['title'],
            'resumen' => $row['abstract'] !== null ? (string) $row['abstract'] : null,
            'idioma' => (string) $row['language_code'],
            'fecha_emision' => $row['issued_at'] !== null ? (string) $row['issued_at'] : null,
            'acceso' => (string) $row['access_level'],
            'embargo_hasta' => $row['embargo_until'] !== null ? (string) $row['embargo_until'] : null,
            'comunidad' => ['nombre' => (string) $row['community_name'], 'slug' => (string) $row['community_slug']],
            'coleccion' => ['nombre' => (string) $row['collection_name'], 'slug' => (string) $row['collection_slug']],
            'tipo_documental' => $row['document_type_name'] !== null
                ? ['nombre' => (string) $row['document_type_name'], 'slug' => (string) $row['document_type_slug']]
                : null,
            'url' => url('/recursos/' . $row['public_id']),
            'actualizado' => (string) $row['updated_at'],
        ];
    }

    /** @return array<string,mixed> */
    private function detail(array $row): array
    {
        $itemId = (int) $row['id'];
        $instId = TenantContext::institutionId();
        $data = $this->summary($row);

        $data['autores'] = array_map(static fn (array $a): array => [
            'nombre' => (string) $a['full_name'],
            'orcid' => $a['orcid'] !== null && (string) $a['orcid'] !== '' ? (string) $a['orcid'] : null,
            'afiliacion' => $a['affiliation'] !== null ? (string) $a['affiliation'] : null,
            'posicion' => (int) $a['position'],
        ], Database::select(
            'SELECT full_name, orcid, affiliation, position FROM item_authors
             WHERE institution_id = ? AND item_id = ? ORDER BY position',
            [$instId, $itemId]
        ));

        $data['palabras_clave'] = array_map(
            static fn (array $k): string => (string) $k['term'],
            Database::select(
                'SELECT k.term FROM item_keywords ik
                 JOIN keywords k ON k.id = ik.keyword_id
                 WHERE ik.item_id = ? ORDER BY ik.position',
                [$itemId]
            )
        );

        $data['identificadores'] = array_map(static fn (array $i): array => [
            'tipo' => (string) $i['identifier_type'],
            'valor' => (string) $i['identifier_value'],
        ], Database::select(
            'SELECT identifier_type, identifier_value FROM item_identifiers
             WHERE institution_id = ? AND item_id = ? ORDER BY identifier_type',
            [$instId, $itemId]
        ));

        $embargoActivo = $row['embargo_until'] !== null && (string) $row['embargo_until'] > gmdate('Y-m-d H:i:s');
        $accesoAbierto = (string) $row['access_level'] === 'open' && !$embargoActivo;
        $data['acceso_abierto'] = $accesoAbierto;
        $data['archivos'] = [];
        if ($accesoAbierto) {
            $data['archivos'] = array_map(fn (array $f): array => [
                'nombre' => (string) $f['original_filename'],
                'mime_type' => (string) $f['mime_type'],
                'tamano_bytes' => (int) $f['file_size'],
                'url' => url('/recursos/' . $row['public_id'] . '/archivos/' . $f['id'] . '/descargar'),
            ], Database::select(
                'SELECT id, original_filename, mime_type, file_size FROM files
                 WHERE institution_id = ? AND item_id = ? AND deleted_at IS NULL
                 ORDER BY is_primary DESC, id',
                [$instId, $itemId]
            ));
        }

        return $data;
    }

    /** @return array<string,mixed> */
    private function publicCollection(string $slug): array
    {
        $collection = Database::selectOne(
            'SELECT c.id, c.name, c.slug, c.description, cm.name AS community_name
             FROM collections c
             JOIN communities cm ON cm.id = c.community_id
             WHERE c.institution_id = ? AND c.slug = ? AND c.deleted_at IS NULL
               AND c.is_public = 1 AND cm.deleted_at IS NULL AND cm.is_public = 1',
            [TenantContext::institutionId(), $slug]
        );
        if ($collection === null) {
            throw new HttpException(404, 'Colección no encontrada.');
        }
        return $collection;
    }
}
