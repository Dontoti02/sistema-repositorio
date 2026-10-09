<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\TenantContext;

/**
 * Respaldos (6.16 / 6.19): volcado de BD con mysqldump (o volcado PHP
 * como alternativa), manifiesto con SHA-256 y verificacion posterior.
 */
final class BackupService
{
    private string $dir;

    public function __construct()
    {
        $configured = (string) (Config::file('storage')['backup_path'] ?? '');
        $this->dir = $configured !== '' ? rtrim($configured, '/\\') : storage_path('backups');
    }

    /** @return array{path:string,size_bytes:int,sha256:string,method:string} */
    public function create(): array
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        $name = 'respaldo_' . gmdate('Ymd_His') . '.sql';
        $path = $this->dir . DIRECTORY_SEPARATOR . $name;

        $method = $this->dumpWithMysqldump($path);
        if ($method === null) {
            $this->dumpWithPhp($path);
            $method = 'php';
        }
        $size = (int) filesize($path);
        $sha = hash_file('sha256', $path) ?: '';

        Database::statement(
            "INSERT INTO backups (institution_id, kind, file_path, size_bytes, sha256, status, created_by, created_at)
             VALUES (?, 'full', ?, ?, ?, 'created', ?, UTC_TIMESTAMP())",
            [
                TenantContext::institutionId(),
                $path,
                $size,
                $sha !== '' ? $sha : null,
                \App\Core\Auth::id(),
            ]
        );
        AuditLogger::log('backups.create', 'backups', (int) Database::lastInsertId(), null, $name . ' (' . $size . ' bytes)');

        return ['path' => $path, 'size_bytes' => $size, 'sha256' => $sha, 'method' => (string) $method];
    }

    private function dumpWithMysqldump(string $path): ?string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];
        $dump = null;
        foreach ($candidates as $bin) {
            if ($bin !== 'mysqldump' && !is_file($bin)) {
                continue;
            }
            $dump = $bin;
            break;
        }
        if ($dump === null) {
            return null;
        }
        $cfg = Config::file('database')['connections']['mysql'] ?? [];
        $db = (string) ($cfg['database'] ?? 'repositorio_institucional');
        $args = [
            '--host=' . (string) ($cfg['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($cfg['port'] ?? '3306'),
            '--user=' . (string) ($cfg['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--skip-set-charset',
            '--default-character-set=utf8mb4',
            $db,
        ];
        $cmd = escapeshellarg($dump) . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';
        if ($dump === 'mysqldump') {
            $cmd = 'mysqldump ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';
        }
        $pass = (string) ($cfg['password'] ?? '');
        if ($pass !== '') {
            $env = 'MYSQL_PWD=' . escapeshellarg($pass);
            $cmd = $env . ' ' . $cmd;
        }
        $lines = [];
        $code = 0;
        exec($cmd, $lines, $code);
        if ($code !== 0) {
            return null;
        }
        $content = implode("\n", $lines);
        if ($content === '' || !str_contains($content, 'CREATE TABLE')) {
            return null;
        }
        file_put_contents($path, "-- Respaldo generado " . gmdate('c') . "\n" . $content . "\n");
        return 'mysqldump';
    }

    /** Volcado portable: esquema + datos, por lotes de INSERT. */
    private function dumpWithPhp(string $path): void
    {
        $pdo = Database::connection();
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo abrir el archivo de respaldo.');
        }
        fwrite($fh, "-- Respaldo PHP " . gmdate('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n" . ($create[1] ?? '') . ";\n\n");
            $rows = $pdo->query("SELECT * FROM `{$table}`");
            $buffer = [];
            $cols = null;
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`,`', array_keys($row)) . '`';
                }
                $values = array_map(static function ($v) use ($pdo): string {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                $buffer[] = '(' . implode(',', $values) . ')';
                if (count($buffer) >= 200) {
                    fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $buffer) . ";\n");
                    $buffer = [];
                }
            }
            if ($buffer !== []) {
                fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $buffer) . ";\n");
            }
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    /** @return array{ok:bool,path:string,size:int,details:array<int,string>} */
    public function verifyLatest(): array
    {
        $row = Database::selectOne(
            'SELECT file_path, size_bytes, sha256 FROM backups ORDER BY id DESC LIMIT 1'
        );
        if ($row === null) {
            return ['ok' => false, 'path' => '(ninguno)', 'size' => 0, 'details' => ['No hay respaldos registrados.']];
        }
        $path = (string) $row['file_path'];
        $details = [];
        $ok = true;
        if (!is_file($path)) {
            $details[] = "El archivo no existe: {$path}";
            $ok = false;
        } else {
            $size = (int) filesize($path);
            $details[] = "Tamaño registrado: {$row['size_bytes']} | actual: {$size}";
            if ($size !== (int) $row['size_bytes']) {
                $ok = false;
            }
            $sha = hash_file('sha256', $path) ?: '';
            $details[] = 'SHA-256 coincide: ' . (hash_equals((string) $row['sha256'], $sha) ? 'sí' : 'no');
            if (!hash_equals((string) $row['sha256'], $sha)) {
                $ok = false;
            }
        }
        if ($ok) {
            Database::statement('UPDATE backups SET verified_at = UTC_TIMESTAMP() WHERE file_path = ?', [$path]);
        }
        return ['ok' => $ok, 'path' => $path, 'size' => (int) $row['size_bytes'], 'details' => $details];
    }
}
