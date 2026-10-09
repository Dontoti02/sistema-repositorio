<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autorización: denegación por defecto. Verifica permiso + alcance.
 */
final class Authorization
{
    /**
     * ¿Posee el usuario el permiso en el instituto activo?
     */
    public static function can(string $permission): bool
    {
        $perms = Auth::permissions();
        return in_array($permission, $perms, true);
    }

    /**
     * Verifica permiso o lanza 403.
     */
    public static function authorize(string $permission): void
    {
        if (!self::can($permission)) {
            throw new HttpException(403, "No tiene el permiso requerido: {$permission}.");
        }
    }

    /**
     * Verifica uno de varios permisos (OR).
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica un permiso con alcance sobre un recurso propiedad de un autor.
     * - Si tiene `perm` (versión _all) → true.
     * - Si tiene `perm_own` y es dueño → true.
     */
    public static function canOnOwn(string $allPermission, ?int $ownerId, bool $ownerCheck = true): bool
    {
        if (self::can($allPermission)) {
            return true;
        }
        $own = str_ends_with($allPermission, '_all')
            ? substr($allPermission, 0, -4) . '_own'
            : $allPermission . '_own';
        if (self::can($own) && $ownerCheck && $ownerId !== null && Auth::id() === $ownerId) {
            return true;
        }
        return false;
    }

    /**
     * Alcance de revisión: el revisor solo ve asignaciones dentro de su institución.
     */
    public static function withinInstitution(int $entityInstitutionId): bool
    {
        return $entityInstitutionId === TenantContext::institutionId();
    }
}
