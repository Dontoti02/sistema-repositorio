<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\AuditLogger;

/**
 * Diccionario de metadatos y perfiles de metadatos (7.3).
 */
final class MetadataController extends AdminController
{
    /** @var array<int,string> */
    private const DATATYPES = ['text', 'textarea', 'select', 'date', 'url', 'integer', 'boolean'];

    public function index(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $instId = TenantContext::institutionId();
        $q = $request->str('q');

        $sql = 'SELECT f.*, v.name AS vocab_name
                FROM metadata_fields f
                LEFT JOIN controlled_vocabularies v ON v.id = f.vocabulary_id';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE f.field_key LIKE ? OR f.label LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        $sql .= ' ORDER BY f.sort_order, f.field_key';

        return $this->view('admin/metadata/index', [
            'pageTitle' => 'Metadatos y perfiles',
            'fields' => Database::select($sql, $params),
            'profiles' => Database::select(
                'SELECT p.*, (SELECT COUNT(*) FROM metadata_profile_fields ppf WHERE ppf.profile_id = p.id) AS field_count
                 FROM metadata_profiles p
                 WHERE p.institution_id = ? ORDER BY p.is_default DESC, p.name',
                [$instId]
            ),
            'vocabularies' => Database::select(
                'SELECT id, name, slug FROM controlled_vocabularies WHERE institution_id IS NULL OR institution_id = ? ORDER BY name',
                [$instId]
            ),
            'q' => $q,
        ]);
    }

    /** GET /admin/metadatos/campos/nuevo */
    public function createField(Request $request): Response
    {
        $this->authorize('metadata.manage');
        return $this->view('admin/metadata/field-form', [
            'pageTitle' => 'Nuevo campo de metadatos',
            'field' => null,
            'vocabularies' => $this->vocabularies(),
        ]);
    }

    /** POST /admin/metadatos/campos */
    public function storeField(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $data = $this->fieldData($request);
        if ($this->fieldKeyExists($data['field_key'])) {
            throw new HttpException(422, 'Clave de campo duplicada.', ['field_key' => 'Ya existe un campo con esa clave.']);
        }
        if ($data['vocabulary_id'] !== null && Database::selectOne('SELECT id FROM controlled_vocabularies WHERE id = ?', [$data['vocabulary_id']]) === null) {
            throw new HttpException(422, 'El vocabulario indicado no existe.', ['vocabulary_id' => 'Seleccione un vocabulario válido.']);
        }
        Database::statement(
            'INSERT INTO metadata_fields (field_key, namespace, label, description, datatype, cardinality, validation_rule, vocabulary_id, is_public, is_internal, sort_order, example_text, help_text)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['field_key'], $data['namespace'], $data['label'], $data['description'],
                $data['datatype'], $data['cardinality'], $data['validation_rule'],
                $data['vocabulary_id'], $data['is_public'], $data['is_internal'], $data['sort_order'],
                $data['example_text'], $data['help_text'],
            ]
        );
        $id = (int) Database::lastInsertId();
        AuditLogger::log('metadata.field.create', 'metadata_fields', $id, null, $data['field_key']);
        $this->flash('success', 'Campo de metadatos creado.');
        return $this->redirect('/admin/metadatos');
    }

    /** GET /admin/metadatos/campos/{id}/editar */
    public function editField(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $field = $this->field((int) $request->route('id'));
        if ($field === null) {
            throw new HttpException(404, 'Campo no encontrado.');
        }
        return $this->view('admin/metadata/field-form', [
            'pageTitle' => 'Editar campo: ' . $field['label'],
            'field' => $field,
            'vocabularies' => $this->vocabularies(),
        ]);
    }

    /** POST /admin/metadatos/campos/{id} */
    public function updateField(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $id = (int) $request->route('id');
        $field = $this->field($id);
        if ($field === null) {
            throw new HttpException(404, 'Campo no encontrado.');
        }
        $data = $this->fieldData($request);
        if ($this->fieldKeyExists($data['field_key'], $id)) {
            throw new HttpException(422, 'Clave de campo duplicada.', ['field_key' => 'Ya existe un campo con esa clave.']);
        }
        if ($data['vocabulary_id'] !== null && Database::selectOne('SELECT id FROM controlled_vocabularies WHERE id = ?', [$data['vocabulary_id']]) === null) {
            throw new HttpException(422, 'El vocabulario indicado no existe.', ['vocabulary_id' => 'Seleccione un vocabulario válido.']);
        }
        Database::statement(
            'UPDATE metadata_fields SET field_key = ?, namespace = ?, label = ?, description = ?, datatype = ?, cardinality = ?, validation_rule = ?, vocabulary_id = ?, is_public = ?, is_internal = ?, sort_order = ?, example_text = ?, help_text = ? WHERE id = ?',
            [
                $data['field_key'], $data['namespace'], $data['label'], $data['description'],
                $data['datatype'], $data['cardinality'], $data['validation_rule'],
                $data['vocabulary_id'], $data['is_public'], $data['is_internal'], $data['sort_order'],
                $data['example_text'], $data['help_text'], $id,
            ]
        );
        AuditLogger::log('metadata.field.update', 'metadata_fields', $id, (string) $field['field_key'], $data['field_key']);
        $this->flash('success', 'Campo actualizado.');
        return $this->redirect('/admin/metadatos');
    }

    /** POST /admin/metadatos/campos/{id}/eliminar */
    public function deleteField(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $id = (int) $request->route('id');
        $field = $this->field($id);
        if ($field === null) {
            throw new HttpException(404, 'Campo no encontrado.');
        }
        $inUse = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM metadata_values WHERE field_id = ?', [$id])['c'] ?? 0);
        if ($inUse > 0) {
            throw new HttpException(422, "No se puede eliminar: el campo está en uso por $inUse recurso(s).");
        }
        Database::statement('DELETE FROM metadata_fields WHERE id = ?', [$id]);
        AuditLogger::log('metadata.field.delete', 'metadata_fields', $id, (string) $field['field_key'], null);
        $this->flash('success', 'Campo eliminado.');
        return $this->redirect('/admin/metadatos');
    }

    /** POST /admin/metadatos/perfiles */
    public function storeProfile(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $data = $this->profileData($request);
        $instId = TenantContext::institutionId();
        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['name']);
        }
        if ($data['slug'] === '') {
            throw new HttpException(422, 'Nombre no válido para generar el identificador.', ['name' => 'Proporcione un nombre válido.']);
        }
        if ($this->profileSlugExists($data['slug'])) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un perfil con ese identificador.']);
        }
        Database::transaction(function () use ($data, $instId): void {
            if ($data['is_default']) {
                Database::statement('UPDATE metadata_profiles SET is_default = 0 WHERE institution_id = ?', [$instId]);
            }
            Database::statement(
                'INSERT INTO metadata_profiles (institution_id, name, slug, version, description, is_default, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$instId, $data['name'], $data['slug'], $data['version'], $data['description'], $data['is_default'], $data['is_active']]
            );
        });
        $id = (int) Database::lastInsertId();
        AuditLogger::log('metadata.profile.create', 'metadata_profiles', $id, null, $data['name']);
        $this->flash('success', 'Perfil de metadatos creado.');
        return $this->redirect('/admin/metadatos');
    }

    /** GET /admin/metadatos/perfiles/{id} */
    public function editProfile(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $instId = TenantContext::institutionId();
        $profile = Database::selectOne(
            'SELECT * FROM metadata_profiles WHERE id = ? AND institution_id = ?',
            [(int) $request->route('id'), $instId]
        );
        if ($profile === null) {
            throw new HttpException(404, 'Perfil no encontrado.');
        }
        $assigned = [];
        foreach (Database::select(
            'SELECT ppf.*, f.field_key, f.label, f.datatype, f.cardinality
             FROM metadata_profile_fields ppf
             JOIN metadata_fields f ON f.id = ppf.field_id
             WHERE ppf.profile_id = ?
             ORDER BY ppf.sort_order, f.field_key',
            [(int) $profile['id']]
        ) as $row) {
            $assigned[(int) $row['field_id']] = $row;
        }

        return $this->view('admin/metadata/profile-form', [
            'pageTitle' => 'Perfil: ' . $profile['name'],
            'profile' => $profile,
            'assigned' => $assigned,
            'fields' => Database::select('SELECT id, field_key, label, datatype, cardinality FROM metadata_fields ORDER BY sort_order, field_key'),
        ]);
    }

    /** POST /admin/metadatos/perfiles/{id} */
    public function updateProfile(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $instId = TenantContext::institutionId();
        $id = (int) $request->route('id');
        $profile = Database::selectOne(
            'SELECT * FROM metadata_profiles WHERE id = ? AND institution_id = ?',
            [$id, $instId]
        );
        if ($profile === null) {
            throw new HttpException(404, 'Perfil no encontrado.');
        }
        $data = $this->profileData($request);
        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['name']);
        }
        if ($data['slug'] === '') {
            throw new HttpException(422, 'Nombre no válido para generar el identificador.', ['name' => 'Proporcione un nombre válido.']);
        }
        if ($this->profileSlugExists($data['slug'], $id)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un perfil con ese identificador.']);
        }
        Database::transaction(function () use ($data, $id, $instId): void {
            if ($data['is_default']) {
                Database::statement('UPDATE metadata_profiles SET is_default = 0 WHERE institution_id = ? AND id <> ?', [$instId, $id]);
            }
            Database::statement(
                'UPDATE metadata_profiles SET name = ?, slug = ?, version = ?, description = ?, is_default = ?, is_active = ? WHERE id = ?',
                [$data['name'], $data['slug'], $data['version'], $data['description'], $data['is_default'], $data['is_active'], $id]
            );
        });
        AuditLogger::log('metadata.profile.update', 'metadata_profiles', $id, (string) $profile['name'], $data['name']);
        $this->flash('success', 'Perfil actualizado.');
        return $this->redirect('/admin/metadatos');
    }

    /** POST /admin/metadatos/perfiles/{id}/duplicar — duplica como nueva versión. */
    public function duplicateProfile(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $instId = TenantContext::institutionId();
        $id = (int) $request->route('id');
        $profile = Database::selectOne(
            'SELECT * FROM metadata_profiles WHERE id = ? AND institution_id = ?',
            [$id, $instId]
        );
        if ($profile === null) {
            throw new HttpException(404, 'Perfil no encontrado.');
        }

        $newVersion = $this->nextVersion((string) $profile['version']);
        $baseSlug = preg_replace('/-v[\d.]+$/', '', (string) $profile['slug']) ?: (string) $profile['slug'];
        $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', $baseSlug . '-v' . $newVersion) ?? '', '-');
        if ($this->profileSlugExists(strtolower($slug))) {
            $slug = $slug . '-' . gmdate('His');
        }
        $newName = $profile['name'] . ' (versión ' . $newVersion . ')';

        $newId = Database::transaction(function () use ($profile, $instId, $newVersion, $slug, $newName): int {
            Database::statement(
                'INSERT INTO metadata_profiles (institution_id, name, slug, version, description, is_default, is_active)
                 VALUES (?, ?, ?, ?, ?, 0, 0)',
                [$instId, $newName, $slug, $newVersion, (string) ($profile['description'] ?? null)]
            );
            $newId = (int) Database::lastInsertId();
            Database::statement(
                'INSERT INTO metadata_profile_fields (profile_id, field_id, label_override, help_text, is_required, is_required_if, show_in_public, sort_order)
                 SELECT ?, field_id, label_override, help_text, is_required, is_required_if, show_in_public, sort_order
                 FROM metadata_profile_fields WHERE profile_id = ?',
                [$newId, (int) $profile['id']]
            );
            return $newId;
        });
        AuditLogger::log('metadata.profile.duplicate', 'metadata_profiles', (int) $newId, (string) $profile['slug'] . ' v' . $profile['version'], $newName . ' v' . $newVersion);
        $this->flash('success', "Perfil duplicado como nueva versión $newVersion (inactivo por defecto).");
        return $this->redirect('/admin/metadatos/perfiles/' . (int) $newId);
    }

    /** POST /admin/metadatos/perfiles/{id}/campos — guarda la asignación de campos. */
    public function saveProfileFields(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $instId = TenantContext::institutionId();
        $id = (int) $request->route('id');
        $profile = Database::selectOne(
            'SELECT * FROM metadata_profiles WHERE id = ? AND institution_id = ?',
            [$id, $instId]
        );
        if ($profile === null) {
            throw new HttpException(404, 'Perfil no encontrado.');
        }

        $enabled = array_map('intval', array_keys($request->array('fields')));
        $labels = $request->array('label');
        $orders = $request->array('order');
        $required = $request->array('required');
        $public = $request->array('public');

        $validIds = [];
        foreach (Database::select('SELECT id FROM metadata_fields') as $f) {
            $validIds[] = (int) $f['id'];
        }
        $enabled = array_values(array_intersect($enabled, $validIds));

        Database::transaction(function () use ($id, $enabled, $labels, $orders, $required, $public): void {
            Database::statement('DELETE FROM metadata_profile_fields WHERE profile_id = ?', [$id]);
            $sort = 0;
            foreach ($enabled as $fieldId) {
                $sort++;
                $order = isset($orders[$fieldId]) && trim((string) $orders[$fieldId]) !== ''
                    ? (int) $orders[$fieldId]
                    : $sort;
                Database::statement(
                    'INSERT INTO metadata_profile_fields (profile_id, field_id, label_override, is_required, show_in_public, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $id,
                        $fieldId,
                        isset($labels[$fieldId]) && trim((string) $labels[$fieldId]) !== '' ? mb_substr(trim((string) $labels[$fieldId]), 0, 140) : null,
                        isset($required[$fieldId]) ? 1 : 0,
                        isset($public[$fieldId]) ? 1 : 0,
                        $order,
                    ]
                );
            }
        });
        AuditLogger::log('metadata.profile.fields', 'metadata_profiles', $id, null, count($enabled) . ' campo(s) asignado(s)');
        $this->flash('success', 'Campos del perfil guardados.');
        return $this->redirect('/admin/metadatos/perfiles/' . $id);
    }

    /** @return array<string,mixed>|null */
    private function field(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM metadata_fields WHERE id = ?', [$id]);
    }

    private function fieldKeyExists(string $key, int $exceptId = 0): bool
    {
        return Database::selectOne(
            'SELECT id FROM metadata_fields WHERE field_key = ? AND id <> ?',
            [$key, $exceptId]
        ) !== null;
    }

    private function profileSlugExists(string $slug, int $exceptId = 0): bool
    {
        return Database::selectOne(
            'SELECT id FROM metadata_profiles WHERE institution_id = ? AND slug = ? AND id <> ?',
            [TenantContext::institutionId(), $slug, $exceptId]
        ) !== null;
    }

    /**
     * @return array{field_key:string,namespace:string,label:string,description:string,datatype:string,cardinality:string,validation_rule:string,vocabulary_id:int|null,is_public:int,is_internal:int,sort_order:int,example_text:string,help_text:string}
     */
    private function fieldData(Request $request): array
    {
        $data = $this->validate($request, [
            'field_key' => 'required|max:80',
            'namespace' => 'required|in:local,dublin_core,dim',
            'label' => 'required|max:140',
            'description' => 'nullable|max:255',
            'datatype' => 'required|in:' . implode(',', self::DATATYPES),
            'cardinality' => 'required|in:single,multiple',
            'validation_rule' => 'nullable|max:190',
            'vocabulary_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'example_text' => 'nullable|max:190',
            'help_text' => 'nullable|max:255',
        ], [
            'field_key' => 'Clave', 'namespace' => 'Espacio de nombres', 'label' => 'Etiqueta',
            'datatype' => 'Tipo de dato', 'cardinality' => 'Cardinalidad', 'vocabulary_id' => 'Vocabulario',
        ]);

        $key = strtolower(trim((string) $data['field_key']));
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            throw new HttpException(422, 'Clave de campo inválida.', ['field_key' => 'Use solo minúsculas, números y guion bajo.']);
        }

        return [
            'field_key' => $key,
            'namespace' => (string) $data['namespace'],
            'label' => trim((string) $data['label']),
            'description' => trim((string) ($data['description'] ?? '')),
            'datatype' => (string) $data['datatype'],
            'cardinality' => (string) $data['cardinality'],
            'validation_rule' => trim((string) ($data['validation_rule'] ?? '')) ?: null,
            'vocabulary_id' => isset($data['vocabulary_id']) && (int) $data['vocabulary_id'] > 0 ? (int) $data['vocabulary_id'] : null,
            'is_public' => $request->str('is_public', '') === '1' ? 1 : 0,
            'is_internal' => $request->str('is_internal', '') === '1' ? 1 : 0,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'example_text' => trim((string) ($data['example_text'] ?? '')) ?: null,
            'help_text' => trim((string) ($data['help_text'] ?? '')) ?: null,
        ];
    }

    /** @return array{name:string,slug:string,version:string,description:string,is_default:int,is_active:int} */
    private function profileData(Request $request): array
    {
        $data = $this->validate($request, [
            'name' => 'required|max:140',
            'slug' => 'nullable|max:100',
            'version' => 'required|max:30',
            'description' => 'nullable|max:255',
        ], ['name' => 'Nombre', 'version' => 'Versión']);

        return [
            'name' => trim((string) $data['name']),
            'slug' => trim((string) ($data['slug'] ?? '')),
            'version' => trim((string) $data['version']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_default' => $request->str('is_default', '') === '1' ? 1 : 0,
            'is_active' => $request->str('is_active', '') === '1' ? 1 : 0,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function vocabularies(): array
    {
        $instId = TenantContext::institutionId();
        return Database::select(
            'SELECT id, name, slug FROM controlled_vocabularies WHERE institution_id IS NULL OR institution_id = ? ORDER BY name',
            [$instId]
        );
    }

    private function nextVersion(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $m)) {
            return $m[1] . '.' . ((int) $m[2] + 1);
        }
        if (preg_match('/^(\d+)$/', $version, $m)) {
            return $m[1] . '.1';
        }
        return $version . '.1';
    }
}
