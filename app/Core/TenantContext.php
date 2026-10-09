<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Contexto de institución activa (multi-institución listo).
 *
 * - Modo single: siempre el instituto activo único.
 * - Modo multi: resolución por tabla de dominios verificados
 *   (institution_domains), nunca por cabecera Host sin validar.
 */
final class TenantContext
{
    private static ?int $institutionId = null;

    /** @var array<string,mixed>|null */
    private static ?array $institution = null;

    private static bool $resolved = false;

    /** Fuerza un instituto (instalador, pruebas, CLI). */
    public static function set(?int $institutionId, ?array $institution = null): void
    {
        self::$institutionId = $institutionId;
        self::$institution = $institution;
        self::$resolved = true;
    }

    public static function institutionId(): int
    {
        self::resolve();
        if (self::$institutionId === null) {
            throw new HttpException(404, 'No hay institución configurada en esta instalación.');
        }
        return self::$institutionId;
    }

    /** @return array<string,mixed>|null */
    public static function institution(): ?array
    {
        self::resolve();
        return self::$institution;
    }

    public static function isMulti(): bool
    {
        return (string) config('app.tenancy_mode', 'single') === 'multi';
    }

    public static function isReady(): bool
    {
        self::resolve();
        return self::$institutionId !== null;
    }

    private static function resolve(): void
    {
        if (self::$resolved) {
            return;
        }
        self::$resolved = true;
        try {
            if (!self::isMulti()) {
                $row = Database::selectOne(
                    "SELECT * FROM institutions WHERE status = 'active' ORDER BY id ASC LIMIT 1"
                );
                if ($row !== null) {
                    self::$institutionId = (int) $row['id'];
                    self::$institution = $row;
                }
                return;
            }
            // Modo multi: dominio verificado contra la tabla.
            $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            $host = preg_replace('/:\d+$/', '', $host) ?? '';
            if ($host === '') {
                return;
            }
            $row = Database::selectOne(
                'SELECT i.* FROM institution_domains d
                 JOIN institutions i ON i.id = d.institution_id
                 WHERE d.domain = ? AND d.is_verified = 1 AND i.status = ?
                 LIMIT 1',
                [$host, 'active']
            );
            if ($row !== null) {
                self::$institutionId = (int) $row['id'];
                self::$institution = $row;
            }
        } catch (\Throwable) {
            // Sin BD (aún sin instalar): se maneja en routing.
        }
    }

    /** Invalida (tras crear el primer instituto). */
    public static function flush(): void
    {
        self::$institutionId = null;
        self::$institution = null;
        self::$resolved = false;
    }
}
