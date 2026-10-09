<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;
use PDO;

/**
 * Migrador versionado. Ejecuta database/migrations/*.php de forma ordenada
 * e idempotente (cada migración solo una vez, registrada en schema_migrations).
 *
 * Cada archivo retorna:
 *   ['up' => fn(PDO $pdo): void, 'down' => fn(PDO $pdo): void]
 */
final class Migrator
{
    private const TABLE = 'schema_migrations';

    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('database/migrations');
    }

    public function ensureTable(): void
    {
        Database::statement(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(190) NOT NULL,
                batch INT NOT NULL DEFAULT 1,
                executed_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return array<int,string> Nombres ya ejecutados. */
    public function ran(): array
    {
        $this->ensureTable();
        $rows = Database::select('SELECT migration FROM ' . self::TABLE . ' ORDER BY id ASC');
        return array_column($rows, 'migration');
    }

    /**
     * Ejecuta migraciones pendientes.
     *
     * @return array<int,string> Migraciones aplicadas en esta corrida.
     */
    public function migrate(): array
    {
        $this->ensureTable();
        $ran = $this->ran();
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);
        $batch = (int) (Database::selectOne('SELECT COALESCE(MAX(batch),0) AS b FROM ' . self::TABLE)['b'] ?? 0) + 1;
        $applied = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }
            $migration = require $file;
            if (!is_array($migration) || !isset($migration['up']) || !is_callable($migration['up'])) {
                throw new \RuntimeException("Migración inválida: {$name}");
            }
            $pdo = Database::connection();
            try {
                if (!$pdo->inTransaction()) {
                    $pdo->beginTransaction();
                }
                ($migration['up'])($pdo);
                Database::statement(
                    'INSERT INTO ' . self::TABLE . ' (migration, batch, executed_at) VALUES (?, ?, UTC_TIMESTAMP())',
                    [$name, $batch]
                );
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
                $applied[] = $name;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new \RuntimeException("Falló la migración {$name}: " . $e->getMessage(), 0, $e);
            }
        }
        return $applied;
    }

    /** Versión actual del esquema. */
    public function version(): string
    {
        $ran = $this->ran();
        return $ran === [] ? '0' : (string) end($ran);
    }
}
