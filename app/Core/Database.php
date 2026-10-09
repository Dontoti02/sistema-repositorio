<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Acceso a la base de datos (PDO, parámetros enlazados siempre).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $cfg = Config::file('database');
        $conn = $cfg['connections']['mysql'] ?? null;
        if ($conn === null) {
            throw new RuntimeException('Configuración de base de datos ausente.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $conn['host'],
            $conn['port'],
            $conn['database'],
            $conn['charset']
        );
        try {
            self::$pdo = new PDO($dsn, $conn['username'], $conn['password'], $conn['options']);
        } catch (PDOException $e) {
            throw new PDOException('No se pudo conectar a la base de datos: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return self::$pdo;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Ejecuta SELECT/SHOW y devuelve todas las filas.
     *
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve la primera fila o null.
     *
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Ejecuta INSERT/UPDATE/DELETE y devuelve filas afectadas.
     *
     * @param array<int|string,mixed> $params
     */
    public static function statement(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Último ID insertado.
     */
    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }

    /**
     * Ejecuta un bloque dentro de transacción; revierte en error.
     *
     * @template T
     * @param callable(PDO):T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $result = $callback($pdo);
            if (!$inTransaction) {
                $pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if (!$inTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Verifica disponibilidad y versión del servidor. */
    public static function ping(): array
    {
        try {
            $pdo = self::connection();
            return [
                'ok' => true,
                'version' => (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'version' => '', 'error' => $e->getMessage()];
        }
    }
}
