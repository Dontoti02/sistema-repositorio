<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\StreamResponse;
use App\Core\TenantContext;
use App\Domain\Repository\PublicRepository;
use App\Services\AccessControlService;
use App\Services\CitationService;
use App\Services\FileStorageService;

/**
 * Ficha pública del recurso, citas y entrega de archivos.
 * Ningún archivo se sirve sin pasar por AccessControlService.
 */
final class ItemController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access = new AccessControlService(),
        private readonly FileStorageService $storage = new FileStorageService(),
        private readonly CitationService $citations = new CitationService(),
    ) {
    }

    public function show(Request $request): Response
    {
        $repo = new PublicRepository();
        $item = $repo->itemByPublicId((string) $request->route('identificador'));
        if ($item === null) {
            throw new HttpException(404, 'El recurso solicitado no existe.');
        }
        if (!$this->access->canViewMetadata($item)) {
            throw new HttpException(404, 'El recurso solicitado no existe.');
        }

        $this->recordView($item);

        $authors = Database::select(
            'SELECT full_name, author_role, affiliation, orcid, position FROM item_authors WHERE item_id = ? ORDER BY position',
            [(int) $item['id']]
        );
        $keywords = Database::select(
            'SELECT k.term, k.slug FROM item_keywords ik JOIN keywords k ON k.id = ik.keyword_id WHERE ik.item_id = ? ORDER BY ik.position',
            [(int) $item['id']]
        );
        $files = $this->visibleFiles($item);
        $license = Database::selectOne(
            'SELECT l.code, l.name, l.url, il.statement FROM item_licenses il
             JOIN licenses l ON l.id = il.license_id WHERE il.item_id = ? LIMIT 1',
            [(int) $item['id']]
        );
        $identifiers = Database::select(
            'SELECT identifier_type, identifier_value, is_verified FROM item_identifiers WHERE item_id = ?',
            [(int) $item['id']]
        );
        $versions = $item['status'] === 'PUBLISHED'
            ? Database::select(
                'SELECT version_number, created_at, change_reason FROM item_versions WHERE item_id = ? ORDER BY version_number DESC',
                [(int) $item['id']]
            )
            : [];
        $related = Database::select(
            "SELECT i2.public_id, i2.slug, v2.title, ir.relation_type
             FROM item_relations ir
             JOIN items i2 ON i2.id = ir.related_item_id AND i2.deleted_at IS NULL
             JOIN item_versions v2 ON v2.id = i2.current_version_id
             WHERE ir.item_id = ? AND i2.status = 'PUBLISHED'
             LIMIT 10",
            [(int) $item['id']]
        );
        $comments = Database::select(
            "SELECT rc.comment, rc.created_at, u.full_name
             FROM review_comments rc LEFT JOIN users u ON u.id = rc.created_by
             WHERE rc.item_id = ? AND rc.visibility = 'author'
             ORDER BY rc.created_at DESC LIMIT 20",
            [(int) $item['id']]
        );

        // Campos de metadatos públicos.
        $metaValues = Database::select(
            'SELECT f.field_key, f.label, f.datatype, f.cardinality, mv.value_text, mv.value_uri, mv.sort_order
             FROM metadata_values mv
             JOIN metadata_fields f ON f.id = mv.field_id
             WHERE mv.item_version_id = ? AND f.is_public = 1
             ORDER BY f.sort_order, mv.sort_order',
            [(int) $item['current_version_id']]
        );
        $metadata = [];
        foreach ($metaValues as $mv) {
            $metadata[$mv['field_key']][] = $mv;
        }

        $citation = $this->citations->build('apa', $item);
        $relatedItems = [];
        $filePreview = null;
        foreach ($files as $f) {
            if (!empty($f['is_primary']) && strtolower((string) $f['extension']) === 'pdf') {
                $filePreview = $f;
                break;
            }
        }

        return $this->view('web/item', [
            'pageTitle' => (string) $item['title'],
            'metaDescription' => mb_substr((string) ($item['abstract'] ?? ''), 0, 155),
            'item' => $item,
            'authors' => $authors,
            'keywords' => $keywords,
            'files' => $files,
            'license' => $license,
            'identifiers' => $identifiers,
            'versions' => $versions,
            'related' => $related,
            'reviewerNotes' => $comments,
            'metadata' => $metadata,
            'citation' => $citation,
            'filePreview' => $filePreview,
            'schemaJson' => $this->schemaJson($item, $authors),
        ]);
    }

    public function citation(Request $request): Response
    {
        $repo = new PublicRepository();
        $item = $repo->itemByPublicId((string) $request->route('identificador'));
        if ($item === null || !$this->access->canViewMetadata($item)) {
            throw new HttpException(404, 'El recurso solicitado no existe.');
        }
        $format = (string) $request->query('formato', 'apa');
        if (!in_array($format, ['apa', 'bibtex', 'ris'], true)) {
            $format = 'apa';
        }
        $result = $this->citations->build($format, $item);
        $accept = (string) $request->header('accept', '');
        if (str_contains($accept, 'text/plain') || $request->wantsJson()) {
            return Response::html(
                '<pre style="white-space:pre-wrap">' . e($result['content']) . '</pre>',
                200
            );
        }
        return $this->view('web/citation', [
            'pageTitle' => 'Citar: ' . (string) $item['title'],
            'item' => $item,
            'result' => $result,
            'format' => $format,
        ]);
    }

    public function fileView(Request $request): Response
    {
        [$item, $file] = $this->resolveFile($request);
        $this->access->authorizeDownload($item, $file);
        if (strtolower((string) $file['extension']) !== 'pdf') {
            // No presentar como legible un formato que el navegador no soporta.
            return $this->redirect(url('/recursos/' . $item['public_id'] . '/archivos/' . $file['id'] . '/descargar'));
        }
        return $this->deliver($item, $file, inline: true);
    }

    public function fileDownload(Request $request): Response
    {
        [$item, $file] = $this->resolveFile($request);
        $this->access->authorizeDownload($item, $file);
        return $this->deliver($item, $file, inline: false);
    }

    /**
     * @return array{0:array<string,mixed>,1:array<string,mixed>}
     */
    private function resolveFile(Request $request): array
    {
        $repo = new PublicRepository();
        $item = $repo->itemByPublicId((string) $request->route('identificador'));
        if ($item === null) {
            throw new HttpException(404, 'El archivo solicitado no existe.');
        }
        $file = Database::selectOne(
            'SELECT * FROM files WHERE id = ? AND item_id = ? AND institution_id = ? AND deleted_at IS NULL LIMIT 1',
            [(int) $request->route('fileId'), (int) $item['id'], TenantContext::institutionId()]
        );
        if ($file === null) {
            throw new HttpException(404, 'El archivo solicitado no existe.');
        }
        return [$item, $file];
    }

    /**
     * Archivos visibles para el visitante (solo se muestran los que puede abrir).
     *
     * @param array<string,mixed> $item
     * @return array<int,array<string,mixed>>
     */
    private function visibleFiles(array $item): array
    {
        $rows = Database::select(
            'SELECT id, original_filename, mime_type, extension, file_size, sha256, visibility, is_primary, download_count
             FROM files WHERE item_id = ? AND deleted_at IS NULL ORDER BY is_primary DESC, id ASC',
            [(int) $item['id']]
        );
        $visible = [];
        foreach ($rows as $row) {
            if ($this->access->denyReason($item, $row) === null) {
                $visible[] = $row;
            }
        }
        return $visible;
    }

    /**
     * Entrega el archivo con streaming, soporte de rangos y cabeceras seguras.
     *
     * @param array<string,mixed> $item
     * @param array<string,mixed> $file
     */
    private function deliver(array $item, array $file, bool $inline): Response
    {
        $path = $this->storage->absolutePath((string) $file['storage_key']);
        if (!is_file($path)) {
            throw new HttpException(404, 'El archivo no está disponible en el almacén.');
        }
        $this->recordDownload($item, $file);

        $size = (int) filesize($path);
        $mime = (string) $file['mime_type'];
        $safeName = $this->safeFilename((string) $file['original_filename']);
        $disposition = $inline && strtolower((string) $file['extension']) === 'pdf' ? 'inline' : 'attachment';

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode((string) $file['original_filename']),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $size,
        ];

        // Soporte de rangos (video/PDF grandes).
        $range = (string) ($request = $_SERVER['HTTP_RANGE'] ?? '');
        if (is_string($range) && preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m) && ($m[1] !== '' || $m[2] !== '')) {
            $start = $m[1] !== '' ? (int) $m[1] : 0;
            $end = $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
            if ($start <= $end && $start < $size) {
                $length = $end - $start + 1;
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
                $headers['Content-Length'] = (string) $length;
                $response = new Response('', 206, $headers);
                return new StreamResponse($path, $response, $start, $length);
            }
        }
        return new StreamResponse($path, new Response('', 200, $headers));
    }

    private function safeFilename(string $name): string
    {
        $name = basename(str_replace(['"', "\0", '/', '\\'], '', $name));
        return $name !== '' ? $name : 'archivo';
    }

    /**
     * @param array<string,mixed> $item
     */
    private function recordView(array $item): void
    {
        try {
            Database::statement(
                'INSERT INTO item_view_events (institution_id, item_id, visitor_hash, user_id, event_at)
                 VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    (int) $item['institution_id'],
                    (int) $item['id'],
                    hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')),
                    Auth::id(),
                ]
            );
        } catch (\Throwable) {
            // La analítica no debe bloquear la lectura.
        }
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string,mixed> $file
     */
    private function recordDownload(array $item, array $file): void
    {
        try {
            Database::statement(
                'INSERT INTO download_events (institution_id, item_id, file_id, user_type, user_id, visitor_hash, event_at)
                 VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    (int) $item['institution_id'],
                    (int) $item['id'],
                    (int) $file['id'],
                    Auth::check() ? 'user' : 'anon',
                    Auth::id(),
                    hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')),
                ]
            );
            Database::statement('UPDATE files SET download_count = download_count + 1 WHERE id = ?', [(int) $file['id']]);
        } catch (\Throwable) {
            // No bloquea la descarga.
        }
    }

    /**
     * Datos estructurados Schema.org (CreativeWork) para SEO.
     *
     * @param array<string,mixed> $item
     * @param array<int,array<string,mixed>> $authors
     */
    private function schemaJson(array $item, array $authors): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => (string) $item['title'],
            'url' => url('/recursos/' . $item['public_id']),
            'inLanguage' => (string) ($item['language_code'] ?? 'es'),
        ];
        if (!empty($item['abstract'])) {
            $data['abstract'] = (string) $item['abstract'];
        }
        if (!empty($item['issued_at'])) {
            $data['datePublished'] = (string) $item['issued_at'];
        }
        if ($authors !== []) {
            $data['author'] = array_map(static fn (array $a): array => ['@type' => 'Person', 'name' => $a['full_name']], $authors);
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }
}
