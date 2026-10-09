<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Consultas públicas del portal (solo registros publicados y abiertos
 * según política). Nunca expone archivos privados.
 */
final class PublicRepository
{
    private int $institutionId;

    public function __construct(?int $institutionId = null)
    {
        $this->institutionId = $institutionId ?? TenantContext::institutionId();
    }

    public function institutionName(): string
    {
        return (string) (TenantContext::institution()['name'] ?? config('app.name', 'Repositorio'));
    }

    /**
     * @return array<string,int>
     */
    public function stats(): array
    {
        $items = Database::selectOne(
            "SELECT COUNT(*) AS total FROM items
             WHERE institution_id = ? AND status = 'PUBLISHED' AND deleted_at IS NULL",
            [$this->institutionId]
        );
        $communities = Database::selectOne(
            'SELECT COUNT(*) AS total FROM communities WHERE institution_id = ? AND is_public = 1 AND deleted_at IS NULL',
            [$this->institutionId]
        );
        $collections = Database::selectOne(
            'SELECT COUNT(*) AS total FROM collections WHERE institution_id = ? AND is_public = 1 AND deleted_at IS NULL',
            [$this->institutionId]
        );
        $downloads = Database::selectOne(
            'SELECT COUNT(*) AS total FROM download_events WHERE institution_id = ?',
            [$this->institutionId]
        );
        return [
            'items' => (int) ($items['total'] ?? 0),
            'communities' => (int) ($communities['total'] ?? 0),
            'collections' => (int) ($collections['total'] ?? 0),
            'downloads' => (int) ($downloads['total'] ?? 0),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recentItems(int $limit = 6): array
    {
        $limit = max(1, min($limit, 24));
        return Database::select(
            "SELECT i.id, i.public_id, i.slug, i.access_level, i.published_at, i.collection_id,
                    v.title, v.abstract, v.language_code,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type_name,
                    YEAR(v.issued_at) AS year
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             WHERE i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
               AND (i.access_level IN ('open','metadata_only','embargoed')
                    OR (i.access_level = 'restricted' AND i.published_at IS NOT NULL))
             ORDER BY i.published_at DESC
             LIMIT {$limit}",
            [$this->institutionId]
        );
    }

    /**
     * Recursos más consultados.
     *
     * @return array<int,array<string,mixed>>
     */
    public function mostViewedItems(int $limit = 6): array
    {
        $limit = max(1, min($limit, 24));
        return Database::select(
            "SELECT i.id, i.public_id, i.slug, i.access_level, v.title,
                    COALESCE(SUM(a.metric_value), 0) AS views
             FROM aggregate_daily_stats a
             JOIN items i ON i.id = a.item_id
             JOIN item_versions v ON v.id = i.current_version_id
             WHERE a.institution_id = ? AND a.metric = 'item_views'
               AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
             GROUP BY i.id, i.public_id, i.slug, i.access_level, v.title
             ORDER BY views DESC, i.published_at DESC
             LIMIT {$limit}",
            [$this->institutionId]
        );
    }

    /**
     * Comunidades públicas con contadores.
     *
     * @return array<int,array<string,mixed>>
     */
    public function communities(?int $parentId = null): array
    {
        return Database::select(
            "SELECT c.id, c.name, c.slug, c.description, c.parent_id,
                    (SELECT COUNT(*) FROM collections col
                      WHERE col.community_id = c.id AND col.is_public = 1 AND col.deleted_at IS NULL) AS collections_count,
                    (SELECT COUNT(*) FROM items i
                      WHERE i.collection_id IN (SELECT id FROM collections WHERE community_id = c.id)
                        AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL) AS items_count
             FROM communities c
             WHERE c.institution_id = ? AND c.is_public = 1 AND c.deleted_at IS NULL
               AND c.parent_id " . ($parentId === null ? 'IS NULL' : '= ' . (int) $parentId) . "
             ORDER BY c.sort_order, c.name",
            [$this->institutionId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function communityBySlug(string $slug): ?array
    {
        return Database::selectOne(
            'SELECT * FROM communities
             WHERE institution_id = ? AND slug = ? AND is_public = 1 AND deleted_at IS NULL LIMIT 1',
            [$this->institutionId, $slug]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function collectionBySlug(string $slug): ?array
    {
        return Database::selectOne(
            'SELECT c.*, cm.name AS community_name, cm.slug AS community_slug
             FROM collections c
             JOIN communities cm ON cm.id = c.community_id
             WHERE c.institution_id = ? AND c.slug = ? AND c.is_public = 1 AND c.deleted_at IS NULL
             LIMIT 1',
            [$this->institutionId, $slug]
        );
    }

    /**
     * Colecciones de una comunidad.
     *
     * @return array<int,array<string,mixed>>
     */
    public function collectionsIn(int $communityId): array
    {
        return Database::select(
            "SELECT c.id, c.name, c.slug, c.description,
                    (SELECT COUNT(*) FROM items i
                      WHERE i.collection_id = c.id AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL) AS items_count
             FROM collections c
             WHERE c.institution_id = ? AND c.community_id = ? AND c.is_public = 1 AND c.deleted_at IS NULL
             ORDER BY c.sort_order, c.name",
            [$this->institutionId, $communityId]
        );
    }

    /**
     * Programas públicos.
     *
     * @return array<int,array<string,mixed>>
     */
    public function programs(): array
    {
        return Database::select(
            "SELECT p.id, p.name, p.slug, p.description, p.level,
                    (SELECT COUNT(*) FROM items i WHERE i.program_id = p.id AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL) AS items_count
             FROM programs p
             WHERE p.institution_id = ? AND p.status = 'active'
             ORDER BY p.sort_order, p.name",
            [$this->institutionId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function programBySlug(string $slug): ?array
    {
        return Database::selectOne(
            'SELECT * FROM programs WHERE institution_id = ? AND slug = ? AND status = ? LIMIT 1',
            [$this->institutionId, $slug, 'active']
        );
    }

    /**
     * Ficha completa de un recurso por su identificador estable.
     *
     * @return array<string,mixed>|null
     */
    public function itemByPublicId(string $publicId): ?array
    {
        return Database::selectOne(
            "SELECT i.*, v.title, v.abstract, v.language_code, v.issued_at, v.version_number,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type_name, dt.slug AS document_type_slug,
                    p.name AS program_name, p.slug AS program_slug,
                    u.full_name AS publisher_name
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN programs p ON p.id = i.program_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.institution_id = ? AND i.public_id = ? AND i.deleted_at IS NULL
             LIMIT 1",
            [$this->institutionId, $publicId]
        );
    }

    /**
     * Búsqueda de recursos publicados con filtros.
     *
     * @param array<string,mixed> $filters
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 12): array
    {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["i.institution_id = ?", "i.status = 'PUBLISHED'", 'i.deleted_at IS NULL'];
        $params = [$this->institutionId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(v.title LIKE ? OR v.abstract LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['collection_id'])) {
            $where[] = 'i.collection_id = ?';
            $params[] = (int) $filters['collection_id'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = 'dt.slug = ?';
            $params[] = (string) $filters['tipo'];
        }
        if (!empty($filters['coleccion'])) {
            $where[] = 'c.slug = ?';
            $params[] = (string) $filters['coleccion'];
        }
        if (!empty($filters['comunidad'])) {
            $where[] = 'cm.slug = ?';
            $params[] = (string) $filters['comunidad'];
        }
        if (!empty($filters['programa'])) {
            $where[] = 'p.slug = ?';
            $params[] = (string) $filters['programa'];
        }
        if (!empty($filters['anio_desde'])) {
            $where[] = 'YEAR(v.issued_at) >= ?';
            $params[] = (int) $filters['anio_desde'];
        }
        if (!empty($filters['anio_hasta'])) {
            $where[] = 'YEAR(v.issued_at) <= ?';
            $params[] = (int) $filters['anio_hasta'];
        }
        if (!empty($filters['idioma'])) {
            $where[] = 'v.language_code = ?';
            $params[] = (string) $filters['idioma'];
        }
        if (!empty($filters['autor'])) {
            $where[] = 'EXISTS (SELECT 1 FROM item_authors ia WHERE ia.item_id = i.id AND ia.full_name LIKE ?)';
            $params[] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['autor']) . '%';
        }
        if (!empty($filters['acceso'])) {
            $where[] = 'i.access_level = ?';
            $params[] = (string) $filters['acceso'];
        }
        if (!empty($filters['palabra_clave'])) {
            $where[] = 'EXISTS (SELECT 1 FROM item_keywords ik JOIN keywords k ON k.id = ik.keyword_id
                        WHERE ik.item_id = i.id AND k.slug = ?)';
            $params[] = (string) $filters['palabra_clave'];
        }

        $whereSql = implode(' AND ', $where);

        $total = Database::selectOne(
            "SELECT COUNT(*) AS total
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN programs p ON p.id = i.program_id
             WHERE {$whereSql}",
            $params
        );

        $order = match ($filters['orden'] ?? 'relevancia') {
            'fecha' => 'i.published_at DESC',
            'titulo' => 'v.title ASC',
            'consultas' => 'views DESC, i.published_at DESC',
            default => 'i.published_at DESC, v.title ASC',
        };

        $rows = Database::select(
            "SELECT i.id, i.public_id, i.slug, i.access_level, i.published_at,
                    v.title, v.abstract, v.language_code,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type_name, dt.slug AS document_type_slug,
                    YEAR(v.issued_at) AS year,
                    COALESCE((SELECT SUM(metric_value) FROM aggregate_daily_stats a
                              WHERE a.item_id = i.id AND a.metric = 'item_views'), 0) AS views
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN programs p ON p.id = i.program_id
             WHERE {$whereSql}
             ORDER BY {$order}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($total['total'] ?? 0)];
    }

    /**
     * Autores con más publicaciones.
     *
     * @return array<int,array<string,mixed>>
     */
    public function authors(int $limit = 50): array
    {
        return Database::select(
            "SELECT ia.full_name, COUNT(*) AS items_count,
                    SUBSTRING_INDEX(ia.full_name, ' ', 1) AS slug_part
             FROM item_authors ia
             JOIN items i ON i.id = ia.item_id
             WHERE ia.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
             GROUP BY ia.full_name
             ORDER BY items_count DESC, ia.full_name
             LIMIT {$limit}",
            [$this->institutionId]
        );
    }

    /**
     * Registros de una colección (para páginas de colección).
     *
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function itemsInCollection(int $collectionId, int $page = 1, int $perPage = 12): array
    {
        return $this->search(['collection_id' => $collectionId], $page, $perPage);
    }
}
