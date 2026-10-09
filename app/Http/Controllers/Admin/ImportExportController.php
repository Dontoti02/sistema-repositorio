<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\StreamResponse;
use App\Core\TenantContext;
use App\Domain\Repository\ItemAdminRepository;
use App\Services\AuditLogger;

/**
 * Importación y exportación (6.16): CSV UTF-8 con mapeo de columnas,
 * prevalidación por fila, importación transaccional por lote y
 * exportación trazable de registros.
 */
final class ImportExportController extends AdminController
{
    /** Campos mapeables desde el CSV. */
    private const TARGETS = [
        'title' => 'Título',
        'abstract' => 'Resumen',
        'language_code' => 'Idioma',
        'issued_at' => 'Fecha de emisión',
        'access_level' => 'Nivel de acceso',
        'collection' => 'Colección (id, slug o nombre)',
        'document_type' => 'Tipo documental (nombre)',
        'program' => 'Programa (nombre o código)',
        'authors' => 'Autores (separados por ; )',
        'keywords' => 'Palabras clave (separadas por ; )',
        'doi' => 'DOI',
        'isbn' => 'ISBN',
        'embargo_until' => 'Embargo hasta (AAAA-MM-DD)',
    ];

    private const LANGUAGES = ['es', 'en', 'qu', 'ay', 'pt', 'fr', 'de', 'it'];

    private const ACCESS_LEVELS = ['open', 'metadata_only', 'embargoed', 'restricted', 'private'];

    public function index(Request $request): Response
    {
        $this->authorize('imports.manage');
        $instId = TenantContext::institutionId();
        $batches = Database::select(
            'SELECT b.*, u.full_name AS creator FROM import_batches b
             LEFT JOIN users u ON u.id = b.created_by
             WHERE b.institution_id = ? ORDER BY b.id DESC LIMIT 20',
            [$instId]
        );
        $exports = Database::select(
            'SELECT e.*, u.full_name AS creator FROM export_jobs e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.institution_id = ? ORDER BY e.id DESC LIMIT 20',
            [$instId]
        );
        return $this->view('admin/import-export/index', [
            'pageTitle' => 'Importar y exportar',
            'batches' => $batches,
            'exports' => $exports,
            'collections' => Database::select(
                "SELECT id, name FROM collections WHERE institution_id = ? AND deleted_at IS NULL ORDER BY name",
                [$instId]
            ),
            'statuses' => array_column(Database::select(
                'SELECT DISTINCT status FROM items WHERE institution_id = ? AND deleted_at IS NULL ORDER BY status',
                [$instId]
            ), 'status'),
        ]);
    }

    /** POST /admin/importar-exportar/subir — recibe el CSV y abre el mapeo. */
    public function upload(Request $request): Response
    {
        $this->authorize('imports.manage');
        $file = $request->file('csv');
        if ($file === [] || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new HttpException(422, 'Debe adjuntar un archivo CSV válido.', ['csv' => 'Archivo no recibido o con error de subida.']);
        }
        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new HttpException(422, 'El archivo supera el máximo de 5 MB.', ['csv' => 'Máximo 5 MB.']);
        }
        $original = (string) ($file['name'] ?? 'datos.csv');
        if (!preg_match('/\.csv$/i', $original)) {
            throw new HttpException(422, 'El archivo debe tener extensión .csv.', ['csv' => 'Solo se admite CSV UTF-8.']);
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new HttpException(422, 'Archivo de subida inválido.');
        }
        $content = file_get_contents($tmp);
        if ($content === false || $content === '' || !mb_check_encoding($content, 'UTF-8')) {
            throw new HttpException(422, 'El CSV debe estar codificado en UTF-8.', ['csv' => 'Codificación no válida (se espera UTF-8).']);
        }

        $dir = storage_path('private' . DIRECTORY_SEPARATOR . 'imports');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original)) ?: 'datos.csv';
        $path = $dir . DIRECTORY_SEPARATOR . gmdate('Ymd_His') . '_' . uniqid('', false) . '_' . $safe;
        if (file_put_contents($path, $content) === false) {
            throw new HttpException(500, 'No se pudo guardar el archivo subido.');
        }

        [$delimiter, $headers] = $this->readHeader($content);
        if ($headers === []) {
            throw new HttpException(422, 'El CSV no tiene una fila de encabezados.', ['csv' => 'La primera fila debe nombrar las columnas.']);
        }

        Database::statement(
            "INSERT INTO import_batches (institution_id, filename, mapping_json, status, total_rows, ok_rows, error_rows, created_by, created_at)
             VALUES (?, ?, ?, 'preview', 0, 0, 0, ?, UTC_TIMESTAMP())",
            [
                TenantContext::institutionId(),
                mb_substr($safe, 0, 255),
                json_encode(['delimiter' => $delimiter, 'headers' => $headers, 'path' => $path], JSON_UNESCAPED_UNICODE),
                Auth::id(),
            ]
        );
        $batchId = (int) Database::lastInsertId();
        AuditLogger::log('imports.upload', 'import_batches', $batchId, null, $safe . ' | columnas: ' . count($headers));

        $this->flash('success', 'CSV cargado (' . count($headers) . ' columnas). Defina el mapeo de columnas.');
        return $this->redirect('/admin/importar-exportar/' . $batchId);
    }

    /** GET /admin/importar-exportar/{id} — mapeo, vista previa e informe por fila. */
    public function show(Request $request): Response
    {
        $this->authorize('imports.manage');
        $batch = $this->batch((int) $request->route('id'));
        $meta = json_decode((string) $batch['mapping_json'], true);
        if (!is_array($meta)) {
            $meta = [];
        }
        $rows = Database::select(
            'SELECT row_no, raw_json, status, errors, item_id FROM import_rows WHERE batch_id = ? ORDER BY row_no LIMIT 300',
            [(int) $batch['id']]
        );
        foreach ($rows as $i => $row) {
            $rows[$i]['raw'] = json_decode((string) ($row['raw_json'] ?? ''), true) ?: [];
            $rows[$i]['error_list'] = array_filter(explode("\n", (string) ($row['errors'] ?? '')));
        }
        $statusCounts = Database::select(
            'SELECT status, COUNT(*) AS n FROM import_rows WHERE batch_id = ? GROUP BY status',
            [(int) $batch['id']]
        );
        $counts = ['ok' => 0, 'error' => 0];
        foreach ($statusCounts as $c) {
            $counts[(string) $c['status']] = (int) $c['n'];
        }

        return $this->view('admin/import-export/preview', [
            'pageTitle' => 'Importación — ' . $batch['filename'],
            'batch' => $batch,
            'headers' => $meta['headers'] ?? [],
            'savedMapping' => $meta['mapping'] ?? [],
            'defaults' => $meta['defaults'] ?? [],
            'targets' => self::TARGETS,
            'rows' => $rows,
            'counts' => $counts,
            'collections' => Database::select(
                'SELECT id, name FROM collections WHERE institution_id = ? AND deleted_at IS NULL ORDER BY name',
                [TenantContext::institutionId()]
            ),
            'languages' => $this->languages(),
        ]);
    }

    /** POST /admin/importar-exportar/{id}/preparar — aplica el mapeo y prevalida. */
    public function prepare(Request $request): Response
    {
        $this->authorize('imports.manage');
        $batch = $this->batch((int) $request->route('id'));
        if (!in_array($batch['status'], ['preview', 'validated'], true)) {
            throw new HttpException(422, 'Este lote ya fue importado o revertido.');
        }
        $meta = json_decode((string) $batch['mapping_json'], true) ?: [];
        $path = (string) ($meta['path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new HttpException(422, 'El archivo CSV original ya no está disponible.');
        }

        $map = [];
        foreach ($request->array('map') as $target => $col) {
            $col = trim((string) $col);
            if ($col !== '' && array_key_exists((string) $target, self::TARGETS)) {
                $map[(string) $target] = (int) $col;
            }
        }
        if (!isset($map['title'])) {
            throw new HttpException(422, 'Debe mapear la columna de Título.', ['map[title]' => 'Campo obligatorio.']);
        }
        $defaults = [
            'collection_id' => (int) ($request->array('defaults')['collection_id'] ?? 0),
            'language_code' => trim((string) ($request->array('defaults')['language_code'] ?? 'es')),
            'access_level' => trim((string) ($request->array('defaults')['access_level'] ?? 'open')),
        ];
        if ($defaults['collection_id'] <= 0) {
            throw new HttpException(422, 'Debe indicar la colección de destino.', ['defaults[collection_id]' => 'Seleccione una colección.']);
        }
        $collection = Database::selectOne(
            'SELECT id FROM collections WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
            [$defaults['collection_id'], TenantContext::institutionId()]
        );
        if ($collection === null) {
            throw new HttpException(422, 'La colección indicada no existe.');
        }

        $meta['mapping'] = $map;
        $meta['defaults'] = $defaults;

        $delim = (string) ($meta['delimiter'] ?? ',');
        $rowsData = $this->readRows($path, $delim);
        $instId = TenantContext::institutionId();

        $existingTitles = [];
        foreach (Database::select(
            'SELECT LOWER(v.title) AS t, i.collection_id FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             WHERE i.institution_id = ? AND i.deleted_at IS NULL',
            [$instId]
        ) as $e) {
            $existingTitles[$e['t'] . '|' . (int) $e['collection_id']] = true;
        }
        $types = [];
        foreach (Database::select('SELECT id, name FROM document_types WHERE institution_id = ?', [$instId]) as $t) {
            $types[$this->normKey((string) $t['name'])] = (int) $t['id'];
        }
        $programs = [];
        foreach (Database::select('SELECT id, name, code FROM programs WHERE institution_id = ?', [$instId]) as $p) {
            $programs[$this->normKey((string) $p['name'])] = (int) $p['id'];
            if ((string) $p['code'] !== '') {
                $programs[$this->normKey((string) $p['code'])] = (int) $p['id'];
            }
        }

        Database::statement('DELETE FROM import_rows WHERE batch_id = ?', [(int) $batch['id']]);

        $total = 0;
        $ok = 0;
        $errors = 0;
        $seenTitles = [];
        foreach ($rowsData as $i => $raw) {
            $rowNo = $i + 2; // la fila 1 son los encabezados
            $cells = [];
            foreach (self::TARGETS as $target => $label) {
                $cells[$target] = isset($map[$target]) && $raw[$map[$target]] !== null
                    ? trim((string) $raw[$map[$target]])
                    : '';
            }
            $problems = ['error' => [], 'warn' => []];
            $prepared = $this->validateRow($cells, $defaults, $types, $programs, $problems);
            $warnings = $problems['warn'] ?? [];
            $rowErrors = $problems['error'] ?? [];
            $status = $rowErrors === [] ? 'ok' : 'error';
            $total++;
            if ($status === 'ok') {
                $ok++;
            } else {
                $errors++;
            }
            $message = implode("\n", $rowErrors);
            if ($status === 'ok' && $warnings !== []) {
                $message = implode("\n", $warnings);
            }
            Database::statement(
                'INSERT INTO import_rows (batch_id, row_no, raw_json, status, errors, item_id)
                 VALUES (?, ?, ?, ?, ?, NULL)',
                [
                    (int) $batch['id'],
                    $rowNo,
                    json_encode($prepared !== [] ? $prepared : $cells, JSON_UNESCAPED_UNICODE),
                    $status,
                    $message !== '' ? mb_substr($message, 0, 4000) : null,
                ]
            );
            if ($status === 'ok') {
                $key = mb_strtolower($prepared['title']) . '|' . (int) $prepared['collection_id'];
                if (isset($existingTitles[$key]) || isset($seenTitles[$key])) {
                    Database::statement(
                        "UPDATE import_rows SET status = 'error', errors = ? WHERE batch_id = ? AND row_no = ?",
                        ["Conflicto: ya existe un recurso con ese título en la colección.", (int) $batch['id'], $rowNo]
                    );
                    $ok--;
                    $errors++;
                }
                $seenTitles[$key] = true;
            }
            unset($problems);
        }

        Database::statement(
            "UPDATE import_batches SET mapping_json = ?, status = 'validated', total_rows = ?, ok_rows = ?, error_rows = ? WHERE id = ?",
            [json_encode($meta, JSON_UNESCAPED_UNICODE), $total, $ok, $errors, (int) $batch['id']]
        );
        AuditLogger::log('imports.validate', 'import_batches', (int) $batch['id'], 'preview', "total=$total ok=$ok errores=$errors");

        $this->flash('success', "Prevalidación completada: $ok fila(s) válida(s), $errors con error(es).");
        return $this->redirect('/admin/importar-exportar/' . (int) $batch['id']);
    }

    /** POST /admin/importar-exportar/{id}/importar — transaccional por lote. */
    public function runImport(Request $request): Response
    {
        $this->authorize('imports.manage');
        $batch = $this->batch((int) $request->route('id'));
        if ($batch['status'] !== 'validated') {
            throw new HttpException(422, 'El lote debe estar prevalidado antes de importar.');
        }
        if ((int) $batch['ok_rows'] === 0) {
            throw new HttpException(422, 'El lote no tiene filas válidas para importar.');
        }

        $rows = Database::select(
            "SELECT row_no, raw_json FROM import_rows WHERE batch_id = ? AND status = 'ok' ORDER BY row_no",
            [(int) $batch['id']]
        );
        $repo = new ItemAdminRepository();
        $actorId = (int) Auth::id();

        try {
            $imported = Database::transaction(function () use ($rows, $repo, $actorId, $batch): int {
                $n = 0;
                foreach ($rows as $row) {
                    $cells = json_decode((string) $row['raw_json'], true) ?: [];
                    $authors = $this->splitList((string) ($cells['authors'] ?? ''));
                    $authorRows = [];
                    foreach ($authors as $name) {
                        $authorRows[] = ['full_name' => mb_substr($name, 0, 190), 'role' => 'author'];
                    }
                    $keywords = $this->splitList((string) ($cells['keywords'] ?? ''));
                    $itemId = $repo->create([
                        'collection_id' => (int) ($cells['collection_id'] ?? 0),
                        'document_type_id' => (int) ($cells['document_type_id'] ?? 0),
                        'program_id' => (int) ($cells['program_id'] ?? 0),
                        'title' => (string) ($cells['title'] ?? ''),
                        'abstract' => (string) ($cells['abstract'] ?? ''),
                        'language_code' => (string) ($cells['language_code'] ?? 'es'),
                        'issued_at' => (string) ($cells['issued_at'] ?? ''),
                        'access_level' => (string) ($cells['access_level'] ?? 'open'),
                        'embargo_until' => (string) ($cells['embargo_until'] ?? ''),
                    ], $authorRows, $keywords, $actorId);

                    foreach ([['doi', 'doi'], ['isbn', 'isbn']] as [$field, $type]) {
                        $value = trim((string) ($cells[$field] ?? ''));
                        if ($value !== '') {
                            Database::statement(
                                "INSERT INTO item_identifiers (institution_id, item_id, identifier_type, identifier_value, provider, is_verified, created_at)
                                 VALUES (?, ?, ?, ?, 'import', 0, UTC_TIMESTAMP())",
                                [TenantContext::institutionId(), $itemId, $type, mb_substr($value, 0, 255)]
                            );
                        }
                    }
                    Database::statement(
                        "UPDATE import_rows SET item_id = ? WHERE batch_id = ? AND row_no = ?",
                        [$itemId, (int) $batch['id'], (int) $row['row_no']]
                    );
                    $n++;
                }
                return $n;
            });
        } catch (\Throwable $e) {
            Database::statement(
                "UPDATE import_batches SET status = 'failed' WHERE id = ? AND status <> 'imported'",
                [(int) $batch['id']]
            );
            AuditLogger::log('imports.failed', 'import_batches', (int) $batch['id'], 'validated', mb_substr($e->getMessage(), 0, 300));
            throw new HttpException(422, 'La importación falló y se revirtió completa: ' . $e->getMessage());
        }

        Database::statement(
            "UPDATE import_batches SET status = 'imported', finished_at = UTC_TIMESTAMP() WHERE id = ?",
            [(int) $batch['id']]
        );
        AuditLogger::log('imports.commit', 'import_batches', (int) $batch['id'], 'validated', "$imported recurso(s) creados en estado DRAFT");

        $this->flash('success', "Lote importado: $imported recurso(s) creados en estado borrador.");
        return $this->redirect('/admin/importar-exportar/' . (int) $batch['id']);
    }

    /** POST /admin/importar-exportar/{id}/revertir — solo si es seguro. */
    public function revert(Request $request): Response
    {
        $this->authorize('imports.manage');
        $batch = $this->batch((int) $request->route('id'));
        if ($batch['status'] !== 'imported') {
            throw new HttpException(422, 'Solo se pueden revertir lotes ya importados.');
        }
        $rows = Database::select(
            'SELECT item_id FROM import_rows WHERE batch_id = ? AND item_id IS NOT NULL',
            [(int) $batch['id']]
        );
        $removed = 0;
        $skipped = 0;
        [$removed, $skipped] = Database::transaction(function () use ($rows, $batch) {
            $removed = 0;
            $skipped = 0;
            foreach ($rows as $row) {
                $itemId = (int) $row['item_id'];
                $item = Database::selectOne(
                    'SELECT status FROM items WHERE id = ? AND deleted_at IS NULL',
                    [$itemId]
                );
                if ($item === null) {
                    continue;
                }
                if ($item['status'] !== 'DRAFT') {
                    $skipped++;
                    continue;
                }
                Database::statement('DELETE FROM item_identifiers WHERE item_id = ?', [$itemId]);
                Database::statement('UPDATE items SET deleted_at = UTC_TIMESTAMP() WHERE id = ?', [$itemId]);
                AuditLogger::log('imports.revert_item', 'items', $itemId, 'DRAFT', 'revertido del lote #' . (int) $batch['id']);
                $removed++;
            }
            return [$removed, $skipped];
        });
        Database::statement(
            "UPDATE import_batches SET status = 'reverted', finished_at = UTC_TIMESTAMP() WHERE id = ?",
            [(int) $batch['id']]
        );
        AuditLogger::log('imports.revert', 'import_batches', (int) $batch['id'], 'imported', "revertidos=$removed omitidos=$skipped");

        $this->flash('success', "Lote revertido: $removed recurso(s) eliminados" . ($skipped > 0 ? ", $skipped omitido(s) por haber salido de borrador" : '') . '.');
        return $this->redirect('/admin/importar-exportar/' . (int) $batch['id']);
    }

    /** POST /admin/importar-exportar/exportar */
    public function export(Request $request): Response
    {
        $this->authorize('exports.manage');
        $format = $request->str('format', 'csv') === 'json' ? 'json' : 'csv';
        $filters = [
            'estado' => $request->str('estado', ''),
            'collection_id' => $request->int('collection_id', 0),
            'desde' => $request->str('desde', ''),
            'hasta' => $request->str('hasta', ''),
        ];
        $instId = TenantContext::institutionId();
        Database::statement(
            "INSERT INTO export_jobs (institution_id, format, filters_json, status, row_count, created_by, created_at)
             VALUES (?, ?, ?, 'pending', 0, ?, UTC_TIMESTAMP())",
            [$instId, $format, json_encode($filters, JSON_UNESCAPED_UNICODE), Auth::id()]
        );
        $jobId = (int) Database::lastInsertId();

        try {
            $dir = storage_path('private' . DIRECTORY_SEPARATOR . 'exports');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $path = $dir . DIRECTORY_SEPARATOR . 'export_' . $jobId . '.' . $format;
            $count = $format === 'json' ? $this->writeJson($path, $filters, $instId) : $this->writeCsv($path, $filters, $instId);
            Database::statement(
                "UPDATE export_jobs SET status = 'done', file_path = ?, row_count = ?, finished_at = UTC_TIMESTAMP() WHERE id = ?",
                [$path, $count, $jobId]
            );
            AuditLogger::log('exports.create', 'export_jobs', $jobId, null, "$format | $count registro(s) | filtros: " . json_encode($filters, JSON_UNESCAPED_UNICODE));
            $this->flash('success', "Exportación generada: $count registro(s) en formato $format.");
        } catch (\Throwable $e) {
            Database::statement(
                "UPDATE export_jobs SET status = 'failed', finished_at = UTC_TIMESTAMP() WHERE id = ?",
                [$jobId]
            );
            AuditLogger::log('exports.failed', 'export_jobs', $jobId, null, mb_substr($e->getMessage(), 0, 300));
            throw new HttpException(500, 'No se pudo generar la exportación: ' . $e->getMessage());
        }

        return $this->redirect('/admin/importar-exportar');
    }

    /** GET /admin/importar-exportar/exportar/{id} — descarga el archivo. */
    public function download(Request $request): Response
    {
        $this->authorize('exports.manage');
        $job = Database::selectOne(
            'SELECT * FROM export_jobs WHERE id = ? AND institution_id = ?',
            [(int) $request->route('id'), TenantContext::institutionId()]
        );
        if ($job === null) {
            throw new HttpException(404, 'Exportación no encontrada.');
        }
        $path = (string) ($job['file_path'] ?? '');
        if ($job['status'] !== 'done' || $path === '' || !is_file($path)) {
            throw new HttpException(404, 'El archivo de exportación no está disponible.');
        }
        AuditLogger::log('exports.download', 'export_jobs', (int) $job['id'], null, basename($path));

        $mime = $job['format'] === 'json' ? 'application/json; charset=UTF-8' : 'text/csv; charset=UTF-8';
        $proto = new Response('', 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
            'Content-Length' => (string) (int) filesize($path),
            'Cache-Control' => 'private, no-store',
        ]);
        return new StreamResponse($path, $proto);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /** @return array<string,mixed> */
    private function batch(int $id): array
    {
        $batch = Database::selectOne(
            'SELECT * FROM import_batches WHERE id = ? AND institution_id = ?',
            [$id, TenantContext::institutionId()]
        );
        if ($batch === null) {
            throw new HttpException(404, 'Lote de importación no encontrado.');
        }
        return $batch;
    }

    /** @return array{0:string,1:array<int,string>} */
    private function readHeader(string $content): array
    {
        $firstLine = strtok($content, "\r\n") ?: '';
        $counts = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delim = (string) array_key_first($counts);
        if ($counts[$delim] === 0) {
            $delim = ',';
        }
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);
        $header = fgetcsv($fh, 0, $delim, '"', '');
        fclose($fh);
        $headers = [];
        foreach ((array) $header as $i => $cell) {
            $cell = trim((string) $cell);
            if ($i === 0) {
                $cell = preg_replace('/^\xEF\xBB\xBF/', '', $cell) ?? $cell;
            }
            $headers[] = $cell !== '' ? $cell : 'columna_' . ($i + 1);
        }
        return [$delim, $headers];
    }

    /** @return array<int,array<int,?string>> */
    private function readRows(string $path, string $delim): array
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new HttpException(500, 'No se pudo leer el CSV.');
        }
        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($fh, 0, $delim, '"', '')) !== false) {
            $line++;
            if ($line === 1) {
                continue; // encabezados
            }
            $allEmpty = true;
            foreach ($cells as $c) {
                if (trim((string) $c) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }
            $rows[] = $cells;
        }
        fclose($fh);
        if (count($rows) > 5000) {
            throw new HttpException(422, 'El CSV supera el máximo de 5000 filas por lote.', ['csv' => 'Divida el archivo en lotes.']);
        }
        return $rows;
    }

    /**
     * @param array<string,string> $cells
     * @param array{collection_id:int,language_code:string,access_level:string} $defaults
     * @param array<string,int> $types
     * @param array<string,int> $programs
     * @param array{error:array<int,string>,warn:array<int,string>} $problems
     * @return array<string,mixed>
     */
    private function validateRow(array $cells, array $defaults, array $types, array $programs, array &$problems): array
    {
        $problems = ['error' => [], 'warn' => []];
        $instId = TenantContext::institutionId();
        $out = [];

        $title = $cells['title'];
        if ($title === '') {
            $problems['error'][] = 'Título vacío.';
        } elseif (mb_strlen($title) < 5) {
            $problems['error'][] = 'Título demasiado corto (mínimo 5 caracteres).';
        } elseif (mb_strlen($title) > 500) {
            $problems['error'][] = 'Título mayor a 500 caracteres.';
        }
        $out['title'] = mb_substr($title, 0, 500);

        $language = $cells['language_code'] !== '' ? $cells['language_code'] : $defaults['language_code'];
        if (!in_array($language, $this->languages(), true)) {
            $base = (string) preg_replace('/[-_].*$/', '', $language);
            if ($base !== $language && in_array($base, $this->languages(), true)) {
                $language = $base;
            } else {
                $problems['error'][] = "Idioma no normalizado: «{$language}».";
            }
        }
        $out['language_code'] = $language;

        $issued = $this->normalizeDate($cells['issued_at']);
        if ($issued === false) {
            $problems['error'][] = 'Fecha de emisión inválida (use AAAA-MM-DD, MM/AAAA o AAAA).';
            $issued = '';
        } elseif ($issued !== '' && $issued > gmdate('Y-m-d')) {
            $problems['error'][] = 'Fecha de emisión futura.';
        }
        $out['issued_at'] = $issued === false ? '' : $issued;

        $access = $cells['access_level'] !== '' ? $cells['access_level'] : $defaults['access_level'];
        if (!in_array($access, self::ACCESS_LEVELS, true)) {
            $problems['error'][] = "Nivel de acceso inválido: «{$access}».";
            $access = $defaults['access_level'];
        }
        $embargo = $this->normalizeDate($cells['embargo_until']);
        if ($embargo === false) {
            $problems['error'][] = 'Fecha de embargo inválida (use AAAA-MM-DD).';
            $embargo = '';
        }
        if ($access === 'embargoed') {
            if ($embargo === '') {
                $problems['error'][] = 'Embargo sin fecha de levante.';
            } elseif ($embargo <= gmdate('Y-m-d')) {
                $problems['error'][] = 'Embargo vencido o sin fecha futura.';
            }
        } elseif ($embargo !== '') {
            $problems['warn'][] = 'Aviso: fecha de embargo ignorada porque el acceso no es «embargoed».';
            $embargo = '';
        }
        $out['access_level'] = $access;
        $out['embargo_until'] = $embargo;

        $collectionId = $defaults['collection_id'];
        $collectionRaw = $cells['collection'];
        if ($collectionRaw !== '') {
            $found = null;
            if (ctype_digit($collectionRaw)) {
                $found = Database::selectOne(
                    'SELECT id FROM collections WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
                    [(int) $collectionRaw, $instId]
                );
            }
            if ($found === null) {
                $found = Database::selectOne(
                    'SELECT id FROM collections WHERE institution_id = ? AND deleted_at IS NULL AND (slug = ? OR LOWER(name) = LOWER(?)) LIMIT 1',
                    [$instId, $collectionRaw, $collectionRaw]
                );
            }
            if ($found === null) {
                $problems['error'][] = "Colección no encontrada: «{$collectionRaw}».";
            } else {
                $collectionId = (int) $found['id'];
            }
        }
        $out['collection_id'] = $collectionId;

        $out['document_type_id'] = 0;
        if ($cells['document_type'] !== '') {
            $typeId = $types[$this->normKey($cells['document_type'])] ?? 0;
            if ($typeId === 0) {
                $problems['error'][] = "Tipo documental no reconocido: «{$cells['document_type']}».";
            }
            $out['document_type_id'] = $typeId;
        }
        $out['program_id'] = 0;
        if ($cells['program'] !== '') {
            $programId = $programs[$this->normKey($cells['program'])] ?? 0;
            if ($programId === 0) {
                $problems['error'][] = "Programa no reconocido: «{$cells['program']}».";
            }
            $out['program_id'] = $programId;
        }

        $abstract = $cells['abstract'];
        if (mb_strlen($abstract) > 20000) {
            $problems['error'][] = 'Resumen mayor a 20000 caracteres.';
        }
        $out['abstract'] = $abstract;

        $authors = $this->splitList($cells['authors']);
        $lower = array_map('mb_strtolower', $authors);
        if (count($lower) !== count(array_unique($lower))) {
            $problems['warn'][] = 'Aviso: autores repetidos en la fila.';
        }
        if ($authors === []) {
            $problems['warn'][] = 'Aviso: sin autores.';
        }
        $out['authors'] = implode('; ', array_slice($authors, 0, 50));
        $out['keywords'] = implode('; ', array_slice($this->splitList($cells['keywords']), 0, 30));

        $doi = $cells['doi'];
        if ($doi !== '' && !preg_match('/^10\.\d{4,9}\/\S{1,80}$/i', $doi)) {
            $problems['error'][] = 'DOI mal formado.';
        } elseif ($doi !== '' && Database::selectOne(
            'SELECT i.id FROM item_identifiers ii
             JOIN items i ON i.id = ii.item_id AND i.deleted_at IS NULL
             WHERE ii.institution_id = ? AND ii.identifier_type = \'doi\' AND ii.identifier_value = ?',
            [$instId, $doi]
        ) !== null) {
            $problems['error'][] = 'DOI ya registrado en otro recurso.';
        }
        $out['doi'] = $doi;
        $isbn = preg_replace('/[^0-9Xx]/', '', $cells['isbn']) ?? '';
        if ($cells['isbn'] !== '' && !$this->validIsbn($isbn)) {
            $problems['error'][] = 'ISBN inválido.';
        } elseif ($cells['isbn'] !== '' && Database::selectOne(
            'SELECT i.id FROM item_identifiers ii
             JOIN items i ON i.id = ii.item_id AND i.deleted_at IS NULL
             WHERE ii.institution_id = ? AND ii.identifier_type = \'isbn\' AND ii.identifier_value = ?',
            [$instId, $cells['isbn']]
        ) !== null) {
            $problems['error'][] = 'ISBN ya registrado en otro recurso.';
        }
        $out['isbn'] = $cells['isbn'];

        return $out;
    }

    /** Clave normalizada para comparar nombres sin distinguir acentos ni mayúsculas. */
    private function normKey(string $value): string
    {
        return mb_strtolower(trim(strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u',
            '·' => ' ',
        ])));
    }

    /** @return array<int,string> */
    private function splitList(string $value): array
    {
        $parts = preg_split('/[;|\n]+/', $value) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }
        return $out;
    }

    /** @return string|false */
    private function normalizeDate(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(\d{4})$/', $value, $m)) {
            return $m[1] . '-01-01';
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            return $m[1] . '-' . $m[2] . '-01';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : false;
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : false;
        }
        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }
        return false;
    }

    private function validIsbn(string $isbn): bool
    {
        if (strlen($isbn) === 10) {
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $d = strtolower($isbn[$i]) === 'x' ? 10 : (int) $isbn[$i];
                $sum += $d * (10 - $i);
            }
            return $sum % 11 === 0;
        }
        if (strlen($isbn) === 13) {
            $sum = 0;
            for ($i = 0; $i < 13; $i++) {
                $sum += ((int) $isbn[$i]) * ($i % 2 === 0 ? 1 : 3);
            }
            return $sum % 10 === 0;
        }
        return false;
    }

    /** @return array<int,string> */
    private function languages(): array
    {
        $terms = Database::select(
            "SELECT t.term_value FROM vocabulary_terms t
             JOIN controlled_vocabularies v ON v.id = t.vocabulary_id
             WHERE v.slug = 'languages' AND t.is_active = 1 ORDER BY t.sort_order"
        );
        $langs = array_map(static fn (array $t): string => (string) $t['term_value'], $terms);
        return $langs !== [] ? $langs : self::LANGUAGES;
    }

    /** @param array<string,string> $filters */
    private function writeCsv(string $path, array $filters, int $instId): int
    {
        $stmt = Database::connection()->prepare($this->exportSql($filters));
        $stmt->execute($this->exportParams($filters, $instId));
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo crear el archivo.');
        }
        fwrite($fh, "\xEF\xBB\xBF");
        $header = [
            'public_id', 'titulo', 'resumen', 'idioma', 'fecha_emision', 'estado', 'acceso',
            'embargo_hasta', 'comunidad', 'coleccion', 'tipo_documental', 'programa',
            'autores', 'palabras_clave', 'doi', 'isbn', 'fecha_publicacion', 'url',
        ];
        fputcsv($fh, $header, ';');
        $count = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $extra = $this->itemExtras((int) $row['id']);
            fputcsv($fh, [
                $row['public_id'],
                $row['title'],
                mb_substr((string) $row['abstract'], 0, 500),
                $row['language_code'],
                $row['issued_at'],
                $row['status'],
                $row['access_level'],
                $row['embargo_until'],
                $row['community_name'],
                $row['collection_name'],
                $row['type_name'],
                trim((string) $row['program_name'] . ((string) $row['program_code'] !== '' ? ' (' . $row['program_code'] . ')' : '')),
                implode('; ', $extra['authors']),
                implode('; ', $extra['keywords']),
                $extra['doi'],
                $extra['isbn'],
                $row['published_at'],
                url('/recursos/' . $row['public_id']),
            ], ';');
            $count++;
        }
        fclose($fh);
        return $count;
    }

    /** @param array<string,string> $filters */
    private function writeJson(string $path, array $filters, int $instId): int
    {
        $stmt = Database::connection()->prepare($this->exportSql($filters));
        $stmt->execute($this->exportParams($filters, $instId));
        $records = [];
        $count = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $extra = $this->itemExtras((int) $row['id']);
            $records[] = [
                'public_id' => $row['public_id'],
                'titulo' => $row['title'],
                'resumen' => $row['abstract'],
                'idioma' => $row['language_code'],
                'fecha_emision' => $row['issued_at'],
                'estado' => $row['status'],
                'acceso' => $row['access_level'],
                'embargo_hasta' => $row['embargo_until'],
                'comunidad' => $row['community_name'],
                'coleccion' => $row['collection_name'],
                'tipo_documental' => $row['type_name'],
                'programa' => $row['program_name'],
                'autores' => $extra['authors'],
                'palabras_clave' => $extra['keywords'],
                'identificadores' => ['doi' => $extra['doi'], 'isbn' => $extra['isbn']],
                'fecha_publicacion' => $row['published_at'],
                'url' => url('/recursos/' . $row['public_id']),
            ];
            $count++;
        }
        $payload = [
            'institucion' => TenantContext::institution()['name'] ?? '',
            'generado_en' => gmdate('c'),
            'filtros' => $filters,
            'total' => $count,
            'registros' => $records,
        ];
        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $count;
    }

    /** @param array<string,string> $filters */
    private function exportSql(array $filters): string
    {
        $sql = 'SELECT i.id, i.public_id, i.status, i.access_level, i.embargo_until, i.published_at, i.created_at,
                       v.title, v.abstract, v.language_code, v.issued_at,
                       c.name AS collection_name, cm.name AS community_name,
                       dt.name AS type_name, p.name AS program_name, p.code AS program_code
                FROM items i
                JOIN item_versions v ON v.id = i.current_version_id
                LEFT JOIN collections c ON c.id = i.collection_id
                LEFT JOIN communities cm ON cm.id = c.community_id
                LEFT JOIN document_types dt ON dt.id = i.document_type_id
                LEFT JOIN programs p ON p.id = i.program_id
                WHERE i.institution_id = ? AND i.deleted_at IS NULL';
        if (($filters['estado'] ?? '') !== '') {
            $sql .= ' AND i.status = ?';
        }
        if ((int) ($filters['collection_id'] ?? 0) > 0) {
            $sql .= ' AND i.collection_id = ?';
        }
        if (!empty($filters['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['desde'])) {
            $sql .= ' AND i.created_at >= ?';
        }
        if (!empty($filters['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['hasta'])) {
            $sql .= ' AND i.created_at <= ?';
        }
        return $sql . ' ORDER BY i.id';
    }

    /** @param array<string,string> $filters @return array<int,mixed> */
    private function exportParams(array $filters, int $instId): array
    {
        $params = [$instId];
        if (($filters['estado'] ?? '') !== '') {
            $params[] = $filters['estado'];
        }
        if ((int) ($filters['collection_id'] ?? 0) > 0) {
            $params[] = (int) $filters['collection_id'];
        }
        if (!empty($filters['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['desde'])) {
            $params[] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['hasta'])) {
            $params[] = $filters['hasta'] . ' 23:59:59';
        }
        return $params;
    }

    /** @return array{authors:array<int,string>,keywords:array<int,string>,doi:string,isbn:string} */
    private function itemExtras(int $itemId): array
    {
        $authors = array_map(
            static fn (array $a): string => (string) $a['full_name'],
            Database::select('SELECT full_name FROM item_authors WHERE item_id = ? ORDER BY position, id', [$itemId])
        );
        $keywords = array_map(
            static fn (array $k): string => (string) $k['term'],
            Database::select(
                'SELECT k.term FROM item_keywords ik JOIN keywords k ON k.id = ik.keyword_id WHERE ik.item_id = ? ORDER BY k.term',
                [$itemId]
            )
        );
        $ids = Database::select('SELECT identifier_type, identifier_value FROM item_identifiers WHERE item_id = ?', [$itemId]);
        $doi = '';
        $isbn = '';
        foreach ($ids as $id) {
            if ($id['identifier_type'] === 'doi' && $doi === '') {
                $doi = (string) $id['identifier_value'];
            }
            if ($id['identifier_type'] === 'isbn' && $isbn === '') {
                $isbn = (string) $id['identifier_value'];
            }
        }
        return ['authors' => $authors, 'keywords' => $keywords, 'doi' => $doi, 'isbn' => $isbn];
    }
}
