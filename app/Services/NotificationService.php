<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\TenantContext;

/**
 * Notificaciones (6.15): bandeja interna + correo opcional por cola.
 * Preferencias por usuario, reintentos limitados y registro de fallos.
 */
final class NotificationService
{
    /** @var array<string,string> tipos -> asunto por defecto */
    public const TYPES = [
        'item.submitted' => 'Recurso enviado a revisión',
        'item.assigned' => 'Se le asignó una revisión',
        'item.commented' => 'Nueva observación en un recurso',
        'item.changes_requested' => 'Se solicitaron cambios',
        'item.approved' => 'Recurso aprobado',
        'item.rejected' => 'Recurso rechazado',
        'item.published' => 'Recurso publicado',
        'embargo.expiring' => 'Embargo próximo a vencer',
        'embargo.released' => 'Embargo liberado',
        'system.integrity' => 'Control de integridad',
        'system.quota' => 'Cuota de almacenamiento',
    ];

    public function notify(
        int $userId,
        string $type,
        string $message,
        ?string $link = null,
        array $data = [],
        ?int $institutionId = null
    ): void {
        $instId = $institutionId ?? TenantContext::institutionId();
        $prefs = $this->preference($userId, $type);

        if ($prefs['in_app']) {
            Database::statement(
                'INSERT INTO notifications (institution_id, user_id, type, title, message, data_json, link, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    $instId,
                    $userId,
                    mb_substr($type, 0, 60),
                    mb_substr(self::TYPES[$type] ?? 'Notificación', 0, 190),
                    mb_substr($message, 0, 6000),
                    $data !== [] ? (json_encode($data, JSON_UNESCAPED_UNICODE) ?: null) : null,
                    $link !== null ? mb_substr($link, 0, 255) : null,
                ]
            );
        }

        if ($prefs['email']) {
            $user = Database::selectOne('SELECT email, full_name FROM users WHERE id = ?', [$userId]);
            if ($user !== null) {
                $subject = (self::TYPES[$type] ?? 'Notificación') . ' — ' . (string) (Config::file('app')['name'] ?? 'Repositorio');
                Database::statement(
                    "INSERT INTO email_outbox (institution_id, to_email, subject, body, status, attempts, created_at)
                     VALUES (?, ?, ?, ?, 'pending', 0, UTC_TIMESTAMP())",
                    [
                        $instId,
                        $user['email'],
                        mb_substr($subject, 0, 255),
                        $this->renderEmail((string) $user['full_name'], $message, $link),
                    ]
                );
            }
        }
    }

    /** Notifica a todos los usuarios que poseen un permiso. */
    public function notifyPermission(string $permission, string $type, string $message, ?string $link = null): int
    {
        $instId = TenantContext::institutionId();
        $rows = Database::select(
            "SELECT DISTINCT u.id
             FROM users u
             JOIN user_institution_memberships m ON m.user_id = u.id AND m.institution_id = ? AND m.status = 'active'
             JOIN membership_roles mr ON mr.membership_id = m.id
             JOIN role_permissions rp ON rp.role_id = mr.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE p.permission_key = ? AND u.status = 1 AND u.deleted_at IS NULL",
            [$instId, $permission]
        );
        foreach ($rows as $row) {
            $this->notify((int) $row['id'], $type, $message, $link);
        }
        return count($rows);
    }

    /** @return array{in_app:bool,email:bool} */
    private function preference(int $userId, string $type): array
    {
        $row = Database::selectOne(
            'SELECT in_app, email FROM notification_preferences WHERE user_id = ? AND type = ?',
            [$userId, $type]
        );
        if ($row === null) {
            // Por defecto: bandeja interna activa, correo solo eventos editoriales.
            $emailDefault = in_array($type, ['item.assigned', 'item.changes_requested', 'item.rejected', 'item.published'], true);
            return ['in_app' => true, 'email' => $emailDefault];
        }
        return ['in_app' => (int) $row['in_app'] === 1, 'email' => (int) $row['email'] === 1];
    }

    /**
     * Procesa el correo pendiente con reintentos limitados.
     *
     * @return int enviados
     */
    public function processQueue(int $max = 50): int
    {
        $pending = Database::select(
            "SELECT id, to_email, subject, body, attempts FROM email_outbox
             WHERE status = 'pending' AND attempts < 3 ORDER BY id LIMIT " . max(1, $max)
        );
        $sent = 0;
        foreach ($pending as $mail) {
            $error = $this->deliver((string) $mail['to_email'], (string) $mail['subject'], (string) $mail['body']);
            if ($error === null) {
                Database::statement(
                    "UPDATE email_outbox SET status = 'sent', sent_at = UTC_TIMESTAMP(), attempts = attempts + 1 WHERE id = ?",
                    [(int) $mail['id']]
                );
                $sent++;
            } else {
                $attempts = (int) $mail['attempts'] + 1;
                Database::statement(
                    "UPDATE email_outbox SET attempts = ?, status = IF(? >= 3, 'failed', 'pending'), last_error = ? WHERE id = ?",
                    [$attempts, $attempts, mb_substr($error, 0, 500), (int) $mail['id']]
                );
            }
        }
        return $sent;
    }

    /** Devuelve null si se envió, o el mensaje de error. */
    private function deliver(string $to, string $subject, string $body): ?string
    {
        $mail = Config::file('mail');
        $driver = (string) ($mail['driver'] ?? 'log');
        if ($driver === 'log') {
            @file_put_contents(
                storage_path('logs/mail.log'),
                '[' . gmdate('Y-m-d H:i:s') . "] TO={$to} SUBJECT={$subject}\n{$body}\n---\n",
                FILE_APPEND
            );
            return null;
        }
        $from = (string) ($mail['from']['address'] ?? 'no-responder@example.invalid');
        $fromName = (string) ($mail['from']['name'] ?? 'Repositorio');
        $headers = implode("\r\n", [
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>',
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ]);
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
        return $ok ? null : 'mail() devolvió false (revise la configuración SMTP del servidor).';
    }

    private function renderEmail(string $name, string $message, ?string $link): string
    {
        $base = (string) config('app.url', '');
        $lines = ["Hola {$name},", '', $message];
        if ($link !== null && $link !== '') {
            $lines[] = '';
            $lines[] = 'Enlace: ' . (str_starts_with($link, 'http') ? $link : $base . $link);
        }
        $lines[] = '';
        $lines[] = 'Mensaje automático del Repositorio Institucional. No responda a este correo.';
        return implode("\n", $lines);
    }

    /** @return array<int,array<string,mixed>> */
    public function inbox(int $userId, int $page = 1, int $perPage = 20, bool $unreadOnly = false): array
    {
        $where = 'user_id = ?' . ($unreadOnly ? ' AND read_at IS NULL' : '');
        return Database::select(
            "SELECT id, type, title, message, link, read_at, created_at
             FROM notifications WHERE {$where}
             ORDER BY created_at DESC, id DESC
             LIMIT " . max(1, $perPage) . ' OFFSET ' . max(0, ($page - 1) * $perPage),
            [$userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) Database::selectOne(
            'SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        )['n'];
    }

    public function markRead(int $userId, int $notificationId): void
    {
        Database::statement(
            'UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE id = ? AND user_id = ? AND read_at IS NULL',
            [$notificationId, $userId]
        );
    }

    public function markAllRead(int $userId): int
    {
        return Database::statement(
            'UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        );
    }

    /** @return array<string,array{in_app:bool,email:bool}> */
    public function preferences(int $userId): array
    {
        $rows = Database::select('SELECT type, in_app, email FROM notification_preferences WHERE user_id = ?', [$userId]);
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['type']] = ['in_app' => (int) $row['in_app'] === 1, 'email' => (int) $row['email'] === 1];
        }
        return $out;
    }

    /** @param array<string,array{in_app:bool,email:bool}> $prefs */
    public function savePreferences(int $userId, array $prefs): void
    {
        foreach ($prefs as $type => $value) {
            Database::statement(
                'INSERT INTO notification_preferences (user_id, type, in_app, email) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE in_app = VALUES(in_app), email = VALUES(email)',
                [$userId, mb_substr($type, 0, 60), $value['in_app'] ? 1 : 0, $value['email'] ? 1 : 0]
            );
        }
    }
}
