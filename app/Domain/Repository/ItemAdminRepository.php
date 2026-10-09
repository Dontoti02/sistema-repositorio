<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\TenantContext;
use App\Domain\Identity\UserAdminRepository;

/**
 * Repositorio de recursos para el panel administrativo (6.8-6.13).
 * Todas las operaciones estan aisladas por institucion.
 */
final class ItemAdminRepository
{
    private int $institutionId;

    public function __construct(?int $institutionId = null)
    {
        $this->institutionId = $institutionId ?? TenantContext::institutionId();
    }

    /**
     * Buscador interno de administracion (6.13).
     *
     * @param array{q?:string,status?:string,collection_id?:int,type_id?:int,page?:int} $filters
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function paginate(array $filters, int $perPage = 20): array
    {
        $where = ['i.institution_id = ?', 'i.deleted_at IS NULL'];
        $params = [$this->institutionId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(i.public_id LIKE ? OR v.title LIKE ? OR v.abstract LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            if ($status === 'pending') {
                $where[] = "i.status IN ('SUBMITTED','UNDER_REVIEW','CHANGES_REQUESTED')";
            } else {
                $where[] = 'i.status = ?';
                $params[] = $status;
            }
        }
        $collectionId = (int) ($filters['collection_id'] ?? 0);
        if ($collectionId > 0) {
            $where[] = 'i.collection_id = ?';
            $params[] = $collectionId;
        }
        $typeId = (int) ($filters['type_id'] ?? 0);
        if ($typeId > 0) {
            $where[] = 'i.document_type_id = ?';
            $params[] = $typeId;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) (Database::selectOne(
            "SELECT COUNT(*) AS total FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             WHERE {$whereSql}",
            $params
        )['total'] ?? 0);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $rows = Database::select(
            "SELECT i.id, i.public_id, i.status, i.access_level, i.updated_at, i.published_at,
                    i.collection_id, i.document_type_id, v.title,
                    c.name AS collection_name, cm.name AS community_name, dt.name AS document_type_name,
                    u.full_name AS author_name
             FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN collections c ON c.id = i.collection_id
             LEFT JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE {$whereSql}
             ORDER BY i.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $row = Database::selectOne(
            'SELECT i.*, v.title, v.abstract, v.language_code, v.issued_at, v.version_number,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type_name, p.name AS program_name,
                    u.full_name AS created_by_name
             FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN collections c ON c.id = i.collection_id
             LEFT JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             LEFT JOIN programs p ON p.id = i.program_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.institution_id = ? AND i.id = ? AND i.deleted_at IS NULL
             LIMIT 1',
            [$this->institutionId, $id]
        );
        if ($row !== null) {
            $row['current_version_db_id'] = $row['current_version_id'];
            $row['current_version_id'] = $row['current_version_db_id'];
        }
        return $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function authors(int $itemId): array
    {
        return Database::select(
            'SELECT id, full_name, author_role, affiliation, orcid, position, is_corresponding
             FROM item_authors WHERE item_id = ? ORDER BY position',
            [$itemId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function keywords(int $itemId): array
    {
        return Database::select(
            'SELECT k.id, k.term, k.slug FROM item_keywords ik
             JOIN keywords k ON k.id = ik.keyword_id
             WHERE ik.item_id = ? ORDER BY ik.position',
            [$itemId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function files(int $itemId): array
    {
        return Database::select(
            'SELECT id, original_filename, mime_type, extension, file_size, sha256,
                    is_primary, download_count, created_at, visibility
             FROM files WHERE item_id = ? AND deleted_at IS NULL ORDER BY is_primary DESC, created_at',
            [$itemId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function versions(int $itemId): array
    {
        return Database::select(
            'SELECT id, version_number, title, change_reason, status, created_at
             FROM item_versions WHERE item_id = ? ORDER BY version_number DESC',
            [$itemId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function history(int $itemId): array
    {
        return Database::select(
            'SELECT h.from_status, h.to_status, h.reason, h.version_number, h.created_at, u.full_name
             FROM item_status_history h
             LEFT JOIN users u ON u.id = h.actor_id
             WHERE h.item_id = ? ORDER BY h.created_at DESC, h.id DESC',
            [$itemId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function comments(int $itemId): array
    {
        return Database::select(
            'SELECT rc.comment, rc.visibility, rc.created_at, u.full_name
             FROM review_comments rc LEFT JOIN users u ON u.id = rc.created_by
             WHERE rc.item_id = ? ORDER BY rc.created_at DESC, rc.id DESC',
            [$itemId]
        );
    }

    /**
     * Crea recurso (DRAFT) + version 1 + autores + palabras clave.
     *
     * @param array<string,mixed> $data
     * @param array<int,array<string,string>> $authors
     * @param array<int,string> $keywordTerms
     */
    public function create(array $data, array $authors, array $keywordTerms, int $actorId): int
    {
        return Database::transaction(function () use ($data, $authors, $keywordTerms, $actorId): int {
            $publicId = $this->nextPublicId();
            $uuid = UserAdminRepository::uuid4();

            Database::statement(
                'INSERT INTO items (uuid, institution_id, collection_id, document_type_id, program_id,
                 public_id, status, access_level, embargo_until, slug, created_by, updated_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [
                    $uuid,
                    $this->institutionId,
                    (int) $data['collection_id'],
                    $data['document_type_id'] ?: null,
                    $data['program_id'] ?: null,
                    $publicId,
                    'DRAFT',
                    (string) $data['access_level'],
                    $data['embargo_until'] ?: null,
                    slugify((string) $data['title']),
                    $actorId,
                    $actorId,
                ]
            );
            $itemId = (int) Database::lastInsertId();

            Database::statement(
                'INSERT INTO item_versions (institution_id, item_id, version_number, title, abstract,
                 language_code, issued_at, status, created_by, created_at)
                 VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    $this->institutionId,
                    $itemId,
                    (string) $data['title'],
                    $data['abstract'] !== '' ? $data['abstract'] : null,
                    (string) $data['language_code'],
                    $data['issued_at'] ?: null,
                    'DRAFT',
                    $actorId,
                ]
            );
            $versionId = (int) Database::lastInsertId();
            Database::statement('UPDATE items SET current_version_id = ? WHERE id = ?', [$versionId, $itemId]);

            $this->replaceAuthors($itemId, $authors);
            $this->replaceKeywords($itemId, $keywordTerms);

            return $itemId;
        });
    }

    /**
     * Actualiza metadatos. DRAFT: edita en vivo. Otros estados: edita la version
     * vigente solo si el usuario tiene permiso all; la publicacion exige revision.
     *
     * @param array<string,mixed> $data
     * @param array<int,array<string,string>> $authors
     * @param array<int,string> $keywordTerms
     */
    public function update(int $itemId, array $data, array $authors, array $keywordTerms, int $actorId): void
    {
        Database::transaction(function () use ($itemId, $data, $authors, $keywordTerms, $actorId): void {
            Database::statement(
                'UPDATE items SET collection_id = ?, document_type_id = ?, program_id = ?,
                 access_level = ?, embargo_until = ?, updated_by = ?, updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND institution_id = ?',
                [
                    (int) $data['collection_id'],
                    $data['document_type_id'] ?: null,
                    $data['program_id'] ?: null,
                    (string) $data['access_level'],
                    $data['embargo_until'] ?: null,
                    $actorId,
                    $itemId,
                    $this->institutionId,
                ]
            );
            $versionId = (int) (Database::selectOne(
                'SELECT current_version_id AS vid FROM items WHERE id = ?',
                [$itemId]
            )['vid'] ?? 0);
            if ($versionId > 0) {
                Database::statement(
                    'UPDATE item_versions SET title = ?, abstract = ?, language_code = ?, issued_at = ?
                     WHERE id = ?',
                    [
                        (string) $data['title'],
                        $data['abstract'] !== '' ? $data['abstract'] : null,
                        (string) $data['language_code'],
                        $data['issued_at'] ?: null,
                        $versionId,
                    ]
                );
            }
            $this->replaceAuthors($itemId, $authors);
            $this->replaceKeywords($itemId, $keywordTerms);
        });
    }

    /**
     * Crea una nueva version a partir de la vigente (para recursos publicados).
     */
    public function createNewVersion(int $itemId, string $reason, int $actorId): int
    {
        return Database::transaction(function () use ($itemId, $reason, $actorId): int {
            $current = Database::selectOne(
                'SELECT v.*, i.institution_id FROM item_versions v JOIN items i ON i.id = v.item_id WHERE v.id = (
                    SELECT current_version_id FROM items WHERE id = ?)',
                [$itemId]
            );
            if ($current === null) {
                throw new HttpException(404, 'Version actual no encontrada.');
            }
            $next = (int) $current['version_number'] + 1;
            Database::statement(
                'INSERT INTO item_versions (institution_id, item_id, version_number, title, abstract,
                 language_code, issued_at, change_reason, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    (int) $current['institution_id'],
                    $itemId,
                    $next,
                    (string) $current['title'],
                    $current['abstract'],
                    (string) $current['language_code'],
                    $current['issued_at'],
                    trim($reason) !== '' ? trim($reason) : null,
                    'DRAFT',
                    $actorId,
                ]
            );
            $newId = (int) Database::lastInsertId();
            Database::statement('UPDATE items SET current_version_id = ? WHERE id = ?', [$newId, $itemId]);
            return $newId;
        });
    }

    /**
     * @param array<int,array<string,string>> $authors
     */
    private function replaceAuthors(int $itemId, array $authors): void
    {
        Database::statement('DELETE FROM item_authors WHERE item_id = ?', [$itemId]);
        $position = 0;
        foreach ($authors as $author) {
            $name = trim((string) ($author['full_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $position++;
            Database::statement(
                'INSERT INTO item_authors (institution_id, item_id, full_name, author_role, affiliation, orcid, position, is_corresponding, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    $this->institutionId,
                    $itemId,
                    mb_substr($name, 0, 190),
                    mb_substr(trim((string) ($author['author_role'] ?? 'author')), 0, 40) ?: 'author',
                    ($author['affiliation'] ?? '') !== '' ? mb_substr((string) $author['affiliation'], 0, 190) : null,
                    ($author['orcid'] ?? '') !== '' ? mb_substr((string) $author['orcid'], 0, 19) : null,
                    $position,
                    !empty($author['is_corresponding']) ? 1 : 0,
                ]
            );
        }
    }

    /**
     * @param array<int,string> $terms
     */
    private function replaceKeywords(int $itemId, array $terms): void
    {
        Database::statement('DELETE FROM item_keywords WHERE item_id = ?', [$itemId]);
        $position = 0;
        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }
            $term = mb_substr($term, 0, 140);
            $slug = slugify($term);
            if ($slug === '') {
                continue;
            }
            $existing = Database::selectOne(
                'SELECT id FROM keywords WHERE institution_id = ? AND slug = ? LIMIT 1',
                [$this->institutionId, $slug]
            );
            if ($existing !== null) {
                $keywordId = (int) $existing['id'];
            } else {
                Database::statement(
                    'INSERT INTO keywords (institution_id, term, slug) VALUES (?, ?, ?)',
                    [$this->institutionId, $term, $slug]
                );
                $keywordId = (int) Database::lastInsertId();
            }
            $position++;
            Database::statement(
                'INSERT IGNORE INTO item_keywords (item_id, keyword_id, position) VALUES (?, ?, ?)',
                [$itemId, $keywordId, $position]
            );
        }
    }

    private function nextPublicId(): string
    {
        $year = gmdate('Y');
        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM items WHERE institution_id = ?',
            [$this->institutionId]
        )['total'] ?? 0);
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = sprintf('RI-%s-%06d', $year, $total + 1 + $attempt);
            $taken = (int) (Database::selectOne(
                'SELECT COUNT(*) AS total FROM items WHERE institution_id = ? AND public_id = ?',
                [$this->institutionId, $candidate]
            )['total'] ?? 0);
            if ($taken === 0) {
                return $candidate;
            }
        }
        return sprintf('RI-%s-%s', $year, strtoupper(bin2hex(random_bytes(4))));
    }

    /** @return array<int,array<string,mixed>> */
    public function collectionsOptions(bool $depositOnly = false): array
    {
        $sql = 'SELECT c.id, c.name, c.community_id, c.deposit_enabled, cm.name AS community_name
                FROM collections c JOIN communities cm ON cm.id = c.community_id
                WHERE c.institution_id = ? AND c.deleted_at IS NULL AND c.is_public = 1';
        if ($depositOnly) {
            $sql .= ' AND c.deposit_enabled = 1';
        }
        return Database::select($sql . ' ORDER BY cm.name, c.name', [$this->institutionId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function programsOptions(): array
    {
        return Database::select(
            "SELECT id, name FROM programs WHERE institution_id = ? AND status = 'active' ORDER BY name",
            [$this->institutionId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function documentTypesOptions(): array
    {
        return Database::select(
            'SELECT id, name FROM document_types WHERE institution_id = ? AND is_active = 1 ORDER BY sort_order, name',
            [$this->institutionId]
        );
    }
}
