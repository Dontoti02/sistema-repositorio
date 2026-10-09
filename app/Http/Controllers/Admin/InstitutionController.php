<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\AuditLogger;
use App\Services\SchedulerService;

/**
 * Ficha institucional (6.12), catalogo de licencias de reutilizacion y
 * gestion de embargos.
 */
final class InstitutionController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('institution.read');
        return $this->view('admin/institution/index', [
            'pageTitle' => 'Institución',
            'inst' => TenantContext::institution(),
            'licenses' => $this->licenses(),
            'embargos' => $this->embargos(),
            'canUpdate' => \App\Core\Authorization::can('institution.update'),
            'canLicenses' => \App\Core\Authorization::can('settings.manage'),
            'canEmbargos' => \App\Core\Authorization::can('embargo.manage'),
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('institution.update');
        $data = $this->validate($request, [
            'name' => 'required|max:200',
            'short_name' => 'nullable|max:120',
            'institution_type' => 'nullable|max:40',
            'institution_code' => 'nullable|max:40',
            'ruc' => 'nullable|max:15',
            'address' => 'nullable|max:255',
            'phone' => 'nullable|max:60',
            'email' => 'nullable|email|max:190',
            'website' => 'nullable|max:255',
            'support_email' => 'nullable|email|max:190',
            'dre_gre' => 'nullable|max:160',
            'coordinator_name' => 'nullable|max:190',
            'coordinator_email' => 'nullable|email|max:190',
            'description' => 'nullable|max:5000',
            'mission' => 'nullable|max:5000',
            'vision' => 'nullable|max:5000',
            'deposit_policy' => 'nullable|max:10000',
            'privacy_policy' => 'nullable|max:10000',
            'about_text' => 'nullable|max:10000',
        ], [
            'name' => 'Nombre de la institución', 'short_name' => 'Nombre corto',
            'email' => 'Correo institucional', 'support_email' => 'Correo de soporte',
            'coordinator_email' => 'Correo del coordinador',
        ]);

        $columns = [
            'name', 'short_name', 'institution_type', 'institution_code', 'ruc', 'address', 'phone', 'email',
            'website', 'support_email', 'dre_gre', 'coordinator_name', 'coordinator_email',
            'description', 'mission', 'vision', 'deposit_policy', 'privacy_policy', 'about_text',
        ];
        $sets = [];
        $params = [];
        foreach ($columns as $col) {
            if (!array_key_exists($col, $data)) {
                continue;
            }
            $sets[] = "{$col} = ?";
            $params[] = $data[$col] !== null && $data[$col] !== '' ? (string) $data[$col] : null;
        }
        $params[] = TenantContext::institutionId();
        Database::statement('UPDATE institutions SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);

        AuditLogger::log('institution.update', 'institutions', (int) TenantContext::institutionId(), null, implode(', ', array_keys(array_filter($data, static fn ($v) => $v !== null && $v !== ''))));
        $this->flash('success', 'Ficha institucional actualizada.');
        return $this->redirect('/admin/institucion');
    }

    /** POST /admin/institucion/licencias */
    public function storeLicense(Request $request): Response
    {
        $this->authorize('settings.manage');
        $data = $this->validate($request, [
            'code' => 'required|max:60',
            'name' => 'required|max:140',
            'url' => 'nullable|max:255',
            'description' => 'nullable|max:500',
        ], ['code' => 'Código', 'name' => 'Nombre']);

        $instId = TenantContext::institutionId();
        $exists = Database::selectOne(
            'SELECT id FROM licenses WHERE (institution_id = ? OR institution_id IS NULL) AND code = ? LIMIT 1',
            [$instId, $data['code']]
        );
        if ($exists !== null) {
            throw new HttpException(422, 'Ya existe una licencia con ese código.', ['code' => 'Código duplicado.']);
        }

        $isDefault = $request->int('is_default', 0) === 1 ? 1 : 0;
        if ($isDefault === 1) {
            Database::statement('UPDATE licenses SET is_default = 0 WHERE institution_id = ? OR institution_id IS NULL', [$instId]);
        }
        Database::statement(
            'INSERT INTO licenses (institution_id, code, name, url, description, requires_attribution, is_default, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)',
            [
                $instId,
                $data['code'],
                $data['name'],
                $data['url'] ?? null,
                $data['description'] ?? null,
                $request->int('requires_attribution', 0) === 1 ? 1 : 0,
                $isDefault,
            ]
        );
        AuditLogger::log('licenses.create', 'licenses', (int) Database::lastInsertId(), null, $data['code']);
        $this->flash('success', 'Licencia registrada.');
        return $this->redirect('/admin/institucion');
    }

    /** POST /admin/institucion/licencias/{id} */
    public function updateLicense(Request $request): Response
    {
        $this->authorize('settings.manage');
        $id = (int) $request->route('id');
        $instId = TenantContext::institutionId();
        $license = Database::selectOne(
            'SELECT * FROM licenses WHERE id = ? AND (institution_id = ? OR institution_id IS NULL)',
            [$id, $instId]
        );
        if ($license === null) {
            throw new HttpException(404, 'Licencia no encontrada.');
        }
        $data = $this->validate($request, [
            'name' => 'required|max:140',
            'url' => 'nullable|max:255',
            'description' => 'nullable|max:500',
        ], ['name' => 'Nombre']);

        $isDefault = $request->int('is_default', 0) === 1 ? 1 : 0;
        if ($isDefault === 1) {
            Database::statement('UPDATE licenses SET is_default = 0 WHERE id <> ? AND (institution_id = ? OR institution_id IS NULL)', [$id, $instId]);
        }
        Database::statement(
            'UPDATE licenses SET name = ?, url = ?, description = ?, requires_attribution = ?, is_default = ? WHERE id = ?',
            [
                $data['name'],
                $data['url'] ?? null,
                $data['description'] ?? null,
                $request->int('requires_attribution', 0) === 1 ? 1 : 0,
                $isDefault,
                $id,
            ]
        );
        AuditLogger::log('licenses.update', 'licenses', $id, null, $data['name']);
        $this->flash('success', 'Licencia actualizada.');
        return $this->redirect('/admin/institucion');
    }

    /** POST /admin/institucion/licencias/{id}/alternar */
    public function toggleLicense(Request $request): Response
    {
        $this->authorize('settings.manage');
        $id = (int) $request->route('id');
        $n = Database::statement(
            'UPDATE licenses SET is_active = 1 - is_active WHERE id = ? AND (institution_id = ? OR institution_id IS NULL)',
            [$id, TenantContext::institutionId()]
        );
        if ($n === 0) {
            throw new HttpException(404, 'Licencia no encontrada.');
        }
        AuditLogger::log('licenses.toggle', 'licenses', $id, null, null);
        $this->flash('success', 'Estado de la licencia actualizado.');
        return $this->redirect('/admin/institucion');
    }

    /** POST /admin/institucion/embargos/{id}/liberar */
    public function releaseEmbargo(Request $request): Response
    {
        $this->authorize('embargo.manage');
        $id = (int) $request->route('id');
        $released = (new SchedulerService())->releaseItem($id);
        if (!$released) {
            throw new HttpException(422, 'El recurso no tiene embargo activo.');
        }
        $this->flash('success', 'Embargo liberado: el archivo ya es accesible.');
        return $this->redirect('/admin/institucion');
    }

    /** @return array<int,array<string,mixed>> */
    private function licenses(): array
    {
        return Database::select(
            'SELECT id, code, name, url, description, requires_attribution, is_default, is_active, sort_order,
                    CASE WHEN institution_id IS NULL THEN 1 ELSE 0 END AS is_global
             FROM licenses WHERE institution_id = ? OR institution_id IS NULL
             ORDER BY sort_order, id',
            [TenantContext::institutionId()]
        );
    }

    /** @return array<int,array<string,mixed>> */
    private function embargos(): array
    {
        return Database::select(
            "SELECT i.id, i.public_id, i.access_level, i.embargo_until, i.created_at,
                    v.title, u.full_name AS owner_name
             FROM items i
             LEFT JOIN item_versions v ON v.id = i.current_version_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.institution_id = ? AND i.deleted_at IS NULL
               AND (i.access_level = 'embargoed' OR i.embargo_until IS NOT NULL)
             ORDER BY i.embargo_until IS NULL, i.embargo_until",
            [TenantContext::institutionId()]
        );
    }
}
