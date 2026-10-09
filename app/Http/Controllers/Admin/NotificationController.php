<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\NotificationService;

/**
 * Bandeja de notificaciones internas y preferencias por usuario (6.15).
 */
final class NotificationController extends AdminController
{
    public function index(Request $request): Response
    {
        $service = new NotificationService();
        $userId = (int) Auth::id();
        $unreadOnly = $request->query('filtro', '') === 'no-leidas';
        $page = max(1, $request->int('page', 1));

        $items = $service->inbox($userId, $page, 20, $unreadOnly);
        $total = (int) \App\Core\Database::selectOne(
            'SELECT COUNT(*) AS n FROM notifications WHERE user_id = ?' . ($unreadOnly ? ' AND read_at IS NULL' : ''),
            [$userId]
        )['n'];

        return $this->view('admin/notifications/index', [
            'pageTitle' => 'Notificaciones',
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => 20,
            'unreadOnly' => $unreadOnly,
            'unread' => $service->unreadCount($userId),
        ]);
    }

    public function read(Request $request): Response
    {
        $service = new NotificationService();
        $userId = (int) Auth::id();
        $id = (int) $request->route('id');
        $service->markRead($userId, $id);
        $row = \App\Core\Database::selectOne('SELECT link FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row !== null && $row['link'] !== null && $row['link'] !== '') {
            return $this->redirect((string) $row['link']);
        }
        return $this->redirect('/admin/notificaciones');
    }

    public function readAll(Request $request): Response
    {
        $n = (new NotificationService())->markAllRead((int) Auth::id());
        $this->flash('success', $n . ' notificación(es) marcada(s) como leídas.');
        return $this->redirect('/admin/notificaciones');
    }

    public function preferences(Request $request): Response
    {
        $userId = (int) Auth::id();
        return $this->view('admin/notifications/preferences', [
            'pageTitle' => 'Preferencias de notificación',
            'prefs' => (new NotificationService())->preferences($userId),
            'types' => NotificationService::TYPES,
        ]);
    }

    public function savePreferences(Request $request): Response
    {
        $userId = (int) Auth::id();
        $inApp = $request->array('in_app');
        $email = $request->array('email');

        $saved = [];
        foreach (array_keys(NotificationService::TYPES) as $type) {
            $saved[$type] = [
                'in_app' => ($inApp[$type] ?? '0') === '1',
                'email' => ($email[$type] ?? '0') === '1',
            ];
        }
        (new NotificationService())->savePreferences($userId, $saved);
        $this->flash('success', 'Preferencias guardadas.');
        return $this->redirect('/admin/notificaciones/preferencias');
    }
}
