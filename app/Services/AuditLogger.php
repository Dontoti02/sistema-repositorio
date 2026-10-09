<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\TenantContext;

/**
 * Bitácora de auditoría (acciones sensibles).
 * Nunca registra contraseñas ni tokens: solo resúmenes.
 */
final class AuditLogger
{
    public static function log(string $action, string $entityType, ?int $entityId = null, ?string $before = null, ?string $after = null): void
    {
        try {
            $request = Request::capture();
            Database::statement(
                'INSERT INTO audit_logs
                 (institution_id, actor_id, action, entity_type, entity_id, before_summary, after_summary, ip_masked, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    TenantContext::institutionId(),
                    Auth::id(),
                    mb_substr($action, 0, 80),
                    mb_substr($entityType, 0, 60),
                    $entityId,
                    $before !== null ? mb_substr($before, 0, 4000) : null,
                    $after !== null ? mb_substr($after, 0, 4000) : null,
                    $request->ipMasked(),
                    mb_substr($request->userAgent(), 0, 300),
                ]
            );
        } catch (\Throwable) {
            // La auditoría no debe interrumpir el flujo principal.
        }
    }
}
