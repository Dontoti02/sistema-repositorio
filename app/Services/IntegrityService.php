<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantContext;
use App\Services\FileStorageService;

/**
 * Verificacion de integridad (6.11 / 6.19): recalcula SHA-256 de los
 * archivos activos y registra el resultado en file_integrity_checks.
 */
final class IntegrityService
{
    /**
     * @return array{checked:int, ok:int, mismatch:int, missing:int}
     */
    public function checkAll(?int $limit = null): array
    {
        $instId = TenantContext::institutionId();
        $sql = 'SELECT id, storage_key, sha256 FROM files WHERE institution_id = ? AND deleted_at IS NULL';
        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $files = Database::select($sql, [$instId]);
        $storage = new FileStorageService();

        $report = ['checked' => 0, 'ok' => 0, 'mismatch' => 0, 'missing' => 0];
        foreach ($files as $file) {
            $report['checked']++;
            $path = $storage->absolutePath((string) $file['storage_key']);
            if (!is_file($path)) {
                $this->record((int) $file['id'], (string) $file['sha256'], null, 'missing', 'Archivo no encontrado en almacén');
                $report['missing']++;
                continue;
            }
            $computed = hash_file('sha256', $path) ?: '';
            if (!hash_equals((string) $file['sha256'], $computed)) {
                $this->record((int) $file['id'], (string) $file['sha256'], $computed, 'mismatch', 'Checksum distinto al registrado');
                $report['mismatch']++;
            } else {
                $this->record((int) $file['id'], (string) $file['sha256'], $computed, 'ok', null);
                $report['ok']++;
            }
        }
        return $report;
    }

    private function record(int $fileId, string $expected, ?string $computed, string $status, ?string $detail): void
    {
        Database::statement(
            'INSERT INTO file_integrity_checks (file_id, expected_sha256, computed_sha256, status, detail, checked_at)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$fileId, $expected, $computed, $status, $detail]
        );
    }

    /** @return array{total:int, ok:int, mismatch:int, missing:int, last: ?string} */
    public function summary(): array
    {
        $byStatus = Database::select(
            'SELECT status, COUNT(*) AS n FROM (
                SELECT c2.status, ROW_NUMBER() OVER (PARTITION BY c2.file_id ORDER BY c2.checked_at DESC, c2.id DESC) AS rn
                FROM file_integrity_checks c2
             ) x WHERE x.rn = 1 GROUP BY status'
        );
        $total = 0;
        $ok = 0;
        $mismatch = 0;
        $missing = 0;
        foreach ($byStatus as $row) {
            $total += (int) $row['n'];
            if ($row['status'] === 'ok') {
                $ok = (int) $row['n'];
            } elseif ($row['status'] === 'mismatch') {
                $mismatch = (int) $row['n'];
            } elseif ($row['status'] === 'missing') {
                $missing = (int) $row['n'];
            }
        }
        $last = Database::selectOne('SELECT MAX(checked_at) AS mx FROM file_integrity_checks')['mx'] ?? null;
        return ['total' => $total, 'ok' => $ok, 'mismatch' => $mismatch, 'missing' => $missing, 'last' => $last !== null ? (string) $last : null];
    }
}
