<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Catalogos configurables del repositorio: programas, comunidades,
 * colecciones y tipos documentales. Siempre aislados por institucion.
 */
final class CatalogRepository
{
    private int $institutionId;

    public function __construct(?int $institutionId = null)
    {
        $this->institutionId = $institutionId ?? TenantContext::institutionId();
    }

    // ---------- Programas ----------

    /** @return array<int,array<string,mixed>> */
    public function programs(string $q = '', ?string $status = null): array
    {
        $where = ['institution_id = ?'];
        $params = [$this->institutionId];
        if ($q !== '') {
            $where[] = '(name LIKE ? OR code LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($status !== null && $status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        return Database::select(
            'SELECT * FROM programs WHERE ' . implode(' AND ', $where) . ' ORDER BY sort_order, name',
            $params
        );
    }

    /** @return array<string,mixed>|null */
    public function program(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM programs WHERE institution_id = ? AND id = ?', [$this->institutionId, $id]);
    }

    public function programSlugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM programs WHERE institution_id = ? AND slug = ?';
        $params = [$this->institutionId, $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) (Database::selectOne($sql, $params)['total'] ?? 0) > 0;
    }

    public function createProgram(string $name, string $slug, string $code, string $level, string $description, string $status): int
    {
        Database::statement(
            'INSERT INTO programs (institution_id, name, slug, code, level, description, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [$this->institutionId, $name, $slug, $code !== '' ? $code : null, $level !== '' ? $level : null, $description !== '' ? $description : null, $status]
        );
        return (int) Database::lastInsertId();
    }

    public function updateProgram(int $id, string $name, string $slug, string $code, string $level, string $description, string $status): void
    {
        Database::statement(
            'UPDATE programs SET name = ?, slug = ?, code = ?, level = ?, description = ?, status = ?,
             updated_at = UTC_TIMESTAMP() WHERE institution_id = ? AND id = ?',
            [$name, $slug, $code !== '' ? $code : null, $level !== '' ? $level : null, $description !== '' ? $description : null, $status, $this->institutionId, $id]
        );
    }

    // ---------- Comunidades ----------

    /** @return array<int,array<string,mixed>> */
    public function communities(string $q = ''): array
    {
        $where = ['c.institution_id = ?', 'c.deleted_at IS NULL', 'c.parent_id IS NULL'];
        $params = [$this->institutionId];
        if ($q !== '') {
            $where[] = 'c.name LIKE ?';
            $params[] = '%' . $q . '%';
        }
        return Database::select(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM collections col WHERE col.community_id = c.id AND col.deleted_at IS NULL) AS collections_count,
                    (SELECT COUNT(*) FROM communities ch WHERE ch.parent_id = c.id AND ch.deleted_at IS NULL) AS children_count
             FROM communities c
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.sort_order, c.name',
            $params
        );
    }

    /** @return array<string,mixed>|null */
    public function community(int $id): ?array
    {
        return Database::selectOne(
            'SELECT * FROM communities WHERE institution_id = ? AND id = ? AND deleted_at IS NULL',
            [$this->institutionId, $id]
        );
    }

    public function slugExists(string $table, string $slug, ?int $exceptId = null): bool
    {
        if (!in_array($table, ['communities', 'collections', 'document_types', 'programs'], true)) {
            return false;
        }
        $softDelete = in_array($table, ['communities', 'collections'], true);
        $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE institution_id = ? AND slug = ?";
        if ($softDelete) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $params = [$this->institutionId, $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) (Database::selectOne($sql, $params)['total'] ?? 0) > 0;
    }

    public function createCommunity(string $name, string $slug, string $description, bool $isPublic, ?int $parentId = null): int
    {
        Database::statement(
            'INSERT INTO communities (institution_id, parent_id, name, slug, description, is_public, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [$this->institutionId, $parentId, $name, $slug, $description !== '' ? $description : null, $isPublic ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public function updateCommunity(int $id, string $name, string $slug, string $description, bool $isPublic): void
    {
        Database::statement(
            'UPDATE communities SET name = ?, slug = ?, description = ?, is_public = ?, updated_at = UTC_TIMESTAMP()
             WHERE institution_id = ? AND id = ?',
            [$name, $slug, $description !== '' ? $description : null, $isPublic ? 1 : 0, $this->institutionId, $id]
        );
    }

    public function deleteCommunity(int $id): void
    {
        $hasCollections = (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM collections WHERE community_id = ? AND deleted_at IS NULL',
            [$id]
        )['total'] ?? 0);
        if ($hasCollections > 0) {
            throw new \App\Core\HttpException(422, 'No puede eliminar una comunidad con colecciones activas.');
        }
        Database::statement(
            'UPDATE communities SET deleted_at = UTC_TIMESTAMP() WHERE institution_id = ? AND id = ?',
            [$this->institutionId, $id]
        );
    }

    // ---------- Colecciones ----------

    /** @return array<int,array<string,mixed>> */
    public function collections(?int $communityId = null): array
    {
        $where = ['col.institution_id = ?', 'col.deleted_at IS NULL'];
        $params = [$this->institutionId];
        if ($communityId !== null) {
            $where[] = 'col.community_id = ?';
            $params[] = $communityId;
        }
        return Database::select(
            'SELECT col.*, c.name AS community_name, c.id AS community_id,
                    (SELECT COUNT(*) FROM items i WHERE i.collection_id = col.id AND i.deleted_at IS NULL) AS items_count
             FROM collections col
             JOIN communities c ON c.id = col.community_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.name, col.sort_order, col.name',
            $params
        );
    }

    /** @return array<string,mixed>|null */
    public function collection(int $id): ?array
    {
        return Database::selectOne(
            'SELECT * FROM collections WHERE institution_id = ? AND id = ? AND deleted_at IS NULL',
            [$this->institutionId, $id]
        );
    }

    public function createCollection(int $communityId, string $name, string $slug, string $description, bool $isPublic, bool $depositEnabled, bool $requiresApproval): int
    {
        Database::statement(
            'INSERT INTO collections (institution_id, community_id, name, slug, description, is_public, deposit_enabled, requires_approval, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [$this->institutionId, $communityId, $name, $slug, $description !== '' ? $description : null, $isPublic ? 1 : 0, $depositEnabled ? 1 : 0, $requiresApproval ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public function updateCollection(int $id, int $communityId, string $name, string $slug, string $description, bool $isPublic, bool $depositEnabled, bool $requiresApproval): void
    {
        Database::statement(
            'UPDATE collections SET community_id = ?, name = ?, slug = ?, description = ?, is_public = ?,
             deposit_enabled = ?, requires_approval = ?, updated_at = UTC_TIMESTAMP()
             WHERE institution_id = ? AND id = ?',
            [$communityId, $name, $slug, $description !== '' ? $description : null, $isPublic ? 1 : 0, $depositEnabled ? 1 : 0, $requiresApproval ? 1 : 0, $this->institutionId, $id]
        );
    }

    public function deleteCollection(int $id): void
    {
        $hasItems = (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM items WHERE collection_id = ? AND deleted_at IS NULL',
            [$id]
        )['total'] ?? 0);
        if ($hasItems > 0) {
            throw new \App\Core\HttpException(422, 'No puede eliminar una coleccion con recursos.');
        }
        Database::statement(
            'UPDATE collections SET deleted_at = UTC_TIMESTAMP() WHERE institution_id = ? AND id = ?',
            [$this->institutionId, $id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function communitiesOptions(): array
    {
        return Database::select(
            'SELECT id, name FROM communities WHERE institution_id = ? AND deleted_at IS NULL ORDER BY sort_order, name',
            [$this->institutionId]
        );
    }

    // ---------- Tipos documentales ----------

    /** @return array<int,array<string,mixed>> */
    public function documentTypes(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM document_types WHERE institution_id = ?';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        return Database::select($sql . ' ORDER BY sort_order, name', [$this->institutionId]);
    }

    /** @return array<string,mixed>|null */
    public function documentType(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM document_types WHERE institution_id = ? AND id = ?', [$this->institutionId, $id]);
    }

    public function createDocumentType(string $name, string $slug, string $description, bool $requiresDegreeFields, bool $isActive): int
    {
        Database::statement(
            'INSERT INTO document_types (institution_id, name, slug, description, requires_degree_fields, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [$this->institutionId, $name, $slug, $description !== '' ? $description : null, $requiresDegreeFields ? 1 : 0, $isActive ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public function updateDocumentType(int $id, string $name, string $slug, string $description, bool $requiresDegreeFields, bool $isActive): void
    {
        Database::statement(
            'UPDATE document_types SET name = ?, slug = ?, description = ?, requires_degree_fields = ?, is_active = ?,
             updated_at = UTC_TIMESTAMP() WHERE institution_id = ? AND id = ?',
            [$name, $slug, $description !== '' ? $description : null, $requiresDegreeFields ? 1 : 0, $isActive ? 1 : 0, $this->institutionId, $id]
        );
    }
}
