<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Domain\Repository\ItemAdminRepository;
use App\Services\AuditLogger;
use App\Services\FileStorageService;
use App\Services\WorkflowService;

/**
 * Recursos: deposito guiado (6.8), ingestas de archivos (6.9),
 * revision y publicacion (6.10), versiones (6.11), acceso (6.12)
 * y buscador interno (6.13).
 */
final class ItemAdminController extends AdminController
{
    private ItemAdminRepository $repo;

    private WorkflowService $workflow;

    public function __construct()
    {
        $this->repo = new ItemAdminRepository();
        $this->workflow = new WorkflowService();
    }

    // ---------- 6.13 Buscador interno ----------

    public function index(Request $request): Response
    {
        $this->authorize('items.read_all');
        $filters = [
            'q' => $request->str('q'),
            'status' => $request->str('estado'),
            'collection_id' => $request->int('coleccion', 0),
            'type_id' => $request->int('tipo', 0),
            'page' => $request->int('pagina', 1),
        ];
        $result = $this->repo->paginate($filters, 20);

        return $this->view('admin/items/index', [
            'pageTitle' => 'Recursos',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => max(1, $filters['page']),
            'filters' => $filters,
            'collections' => $this->repo->collectionsOptions(),
            'types' => $this->repo->documentTypesOptions(),
        ]);
    }

    // ---------- 6.8 Depósito guiado ----------

    public function create(Request $request): Response
    {
        $this->authorize('items.create');
        return $this->form($request, null);
    }

    public function store(Request $request): Response
    {
        $this->authorize('items.create');
        $data = $this->validated($request);
        $this->assertCollectionUsable((int) $data['collection_id'], true);

        $itemId = $this->repo->create($data, $this->authorsFrom($request), $this->keywordsFrom($request), (int) Auth::id());
        AuditLogger::log('items.create', 'items', $itemId, null, (string) $data['title']);

        $this->flash('success', 'Borrador creado. Ya puede adjuntar archivos y enviarlo a revisión.');
        return $this->redirect('/admin/recursos/' . $itemId);
    }

    public function edit(Request $request): Response
    {
        $this->authorize('items.read_all');
        $item = $this->item((int) $request->route('id'));
        $this->assertEditable($item);
        return $this->form($request, $item);
    }

    public function update(Request $request): Response
    {
        $this->authorize('items.read_all');
        $item = $this->item((int) $request->route('id'));
        $this->assertEditable($item);
        $data = $this->validated($request);
        $this->assertCollectionUsable((int) $data['collection_id'], false);

        $this->repo->update((int) $item['id'], $data, $this->authorsFrom($request), $this->keywordsFrom($request), (int) Auth::id());
        AuditLogger::log('items.update', 'items', (int) $item['id'], (string) $item['title'], (string) $data['title']);

        // Autoguardado (JS con data-autosave).
        if ($request->input('_autosave') !== null) {
            return Response::json(['ok' => true]);
        }
        $this->flash('success', 'Cambios guardados.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    // ---------- Ficha en panel ----------

    public function show(Request $request): Response
    {
        $this->authorize('items.read_all');
        $item = $this->item((int) $request->route('id'));
        $canEdit = \App\Core\Authorization::can('items.edit_all')
            || (\App\Core\Authorization::can('items.edit_own') && (int) $item['created_by'] === (int) Auth::id());

        return $this->view('admin/items/show', [
            'pageTitle' => 'Recurso ' . $item['public_id'],
            'item' => $item,
            'authors' => $this->repo->authors((int) $item['id']),
            'keywords' => $this->repo->keywords((int) $item['id']),
            'files' => $this->repo->files((int) $item['id']),
            'versions' => $this->repo->versions((int) $item['id']),
            'history' => $this->repo->history((int) $item['id']),
            'comments' => $this->repo->comments((int) $item['id']),
            'allowedTransitions' => $canEdit || \App\Core\Authorization::can('items.review')
                ? WorkflowService::allowedFrom((string) $item['status'])
                : [],
            'canEdit' => $canEdit && in_array($item['status'], ['DRAFT', 'CHANGES_REQUESTED', 'REJECTED'], true),
            'canUpload' => \App\Core\Authorization::can('files.upload'),
            'policy' => $this->collectionPolicy((int) $item['collection_id']),
        ]);
    }

    // ---------- 6.10 Revisión y publicación ----------

    public function submit(Request $request): Response
    {
        $this->authorize('items.submit');
        $item = $this->item((int) $request->route('id'));
        $from = (string) $item['status'];
        if (!in_array($from, ['DRAFT', 'CHANGES_REQUESTED'], true)) {
            throw new HttpException(422, 'Solo se pueden enviar borradores o recursos con cambios solicitados.');
        }
        $this->workflow->transition((int) $item['id'], $from, 'SUBMITTED', (string) $request->str('reason'));
        $this->flash('success', 'Recurso enviado a revisión.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    public function transition(Request $request): Response
    {
        $item = $this->item((int) $request->route('id'));
        $to = $request->str('to');
        $this->workflow->transition((int) $item['id'], (string) $item['status'], $to, (string) $request->str('reason'));
        $this->flash('success', 'Estado actualizado: ' . $to . '.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    // ---------- 6.11 Nueva versión ----------

    public function newVersion(Request $request): Response
    {
        $this->authorize('items.submit');
        $item = $this->item((int) $request->route('id'));
        if (!in_array($item['status'], ['PUBLISHED'], true)) {
            throw new HttpException(422, 'Las nuevas versiones aplican a recursos publicados.');
        }
        $reason = $request->str('reason');
        if (trim($reason) === '') {
            throw new HttpException(422, 'Debe indicar el motivo de la nueva versión.', ['reason' => 'El motivo es obligatorio.']);
        }
        $version = $this->repo->createNewVersion((int) $item['id'], $reason, (int) Auth::id());
        // PUBLISHED -> UNDER_REVIEW (matriz 8.2): exige items.review; si falta el permiso,
        // se revierte la version creada y se informa al usuario.
        if (!\App\Core\Authorization::can('items.review')) {
            \App\Core\Database::statement('DELETE FROM item_versions WHERE id = ?', [$version]);
            throw new HttpException(403, 'Requiere el permiso items.review para enviar a evaluación.');
        }
        $this->workflow->transition((int) $item['id'], 'PUBLISHED', 'UNDER_REVIEW', 'Nueva versión: ' . $reason);
        AuditLogger::log('items.new_version', 'items', (int) $item['id'], 'v' . ($version - 1), 'v' . $version);

        $this->flash('success', 'Nueva versión creada (v' . $version . ') y enviada a revisión.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    // ---------- 6.9 Ingesta de archivos ----------

    public function uploadFile(Request $request): Response
    {
        $this->authorize('files.upload');
        $item = $this->item((int) $request->route('id'));
        $file = $request->file('archivo');
        if ($file === []) {
            throw new HttpException(422, 'No se recibió ningún archivo.', ['archivo' => 'Seleccione un archivo.']);
        }

        $policy = $this->collectionPolicy((int) $item['collection_id']);
        $existing = $this->repo->files((int) $item['id']);
        if (count($existing) >= (int) $policy['max_files']) {
            throw new HttpException(422, 'Límite de archivos alcanzado.', ['archivo' => 'Esta colección permite máximo ' . $policy['max_files'] . ' archivo(s).']);
        }

        $original = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = array_filter(array_map('trim', explode(',', (string) $policy['allowed_extensions'])));
        if (!in_array($extension, $allowed, true)) {
            throw new HttpException(422, 'Extensión no permitida.', ['archivo' => 'Extensiones permitidas: ' . implode(', ', $allowed) . '.']);
        }
        $maxBytes = (int) $policy['max_file_mb'] * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            throw new HttpException(422, 'Archivo demasiado grande.', ['archivo' => 'El máximo es ' . $policy['max_file_mb'] . ' MB.']);
        }

        try {
            $stored = (new FileStorageService())->store(TenantContext::institutionId(), $file);
        } catch (\RuntimeException $e) {
            throw new HttpException(422, $e->getMessage(), ['archivo' => $e->getMessage()]);
        }

        $visibility = $item['status'] === 'PUBLISHED' ? 'public' : 'private';
        \App\Core\Database::statement(
            'INSERT INTO files (uuid, institution_id, item_id, item_version_id, original_filename, storage_key,
             mime_type, extension, file_size, sha256, visibility, is_primary, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [
                \App\Domain\Identity\UserAdminRepository::uuid4(),
                TenantContext::institutionId(),
                (int) $item['id'],
                $item['current_version_id'],
                mb_substr($stored['original_filename'], 0, 255),
                $stored['storage_key'],
                $stored['mime'],
                $extension,
                (int) $stored['size'],
                $stored['sha256'],
                $visibility,
                count($existing) === 0 ? 1 : 0,
                Auth::id(),
            ]
        );
        AuditLogger::log('files.upload', 'files', (int) $item['id'], null, $stored['original_filename']);

        if ($request->header('x-requested-with') !== null) {
            return Response::json(['ok' => true, 'redirect' => '/admin/recursos/' . $item['id']]);
        }
        $this->flash('success', 'Archivo adjuntado.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    public function deleteFile(Request $request): Response
    {
        $this->authorize('files.replace');
        $item = $this->item((int) $request->route('id'));
        $fileId = (int) $request->route('fileId');

        $file = \App\Core\Database::selectOne(
            'SELECT id, original_filename, storage_key, item_id FROM files
             WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
            [$fileId, TenantContext::institutionId()]
        );
        if ($file === null || (int) $file['item_id'] !== (int) $item['id']) {
            throw new HttpException(404, 'Archivo no encontrado.');
        }

        \App\Core\Database::statement('UPDATE files SET deleted_at = UTC_TIMESTAMP() WHERE id = ?', [$fileId]);
        AuditLogger::log('files.delete', 'files', $fileId, (string) $file['original_filename'], null);

        $this->flash('success', 'Archivo eliminado.');
        return $this->redirect('/admin/recursos/' . $item['id']);
    }

    // ---------- Internos ----------

    /** @return array<string,mixed> */
    private function item(int $id): array
    {
        $item = $this->repo->find($id);
        if ($item === null) {
            throw new HttpException(404, 'Recurso no encontrado.');
        }
        return $item;
    }

    private function assertEditable(array $item): void
    {
        $editable = in_array($item['status'], ['DRAFT', 'CHANGES_REQUESTED', 'REJECTED'], true);
        $canAll = \App\Core\Authorization::can('items.edit_all');
        $canOwn = \App\Core\Authorization::can('items.edit_own') && (int) $item['created_by'] === (int) Auth::id();
        if (!$editable || (!$canAll && !$canOwn)) {
            throw new HttpException(403, 'Este recurso no es editable en su estado actual (' . $item['status'] . ').');
        }
    }

    private function assertCollectionUsable(int $collectionId, bool $forDeposit): void
    {
        $row = \App\Core\Database::selectOne(
            'SELECT id, deposit_enabled FROM collections WHERE id = ? AND institution_id = ? AND deleted_at IS NULL',
            [$collectionId, TenantContext::institutionId()]
        );
        if ($row === null) {
            throw new HttpException(422, 'Colección inválida.', ['collection_id' => 'Seleccione una colección válida.']);
        }
        if ($forDeposit && (int) $row['deposit_enabled'] !== 1) {
            throw new HttpException(422, 'La colección no acepta depósitos.', ['collection_id' => 'Esta colección tiene el depósito deshabilitado.']);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'collection_id' => 'required|integer',
            'title' => 'required|min:5|max:500',
            'abstract' => 'nullable|max:20000',
            'language_code' => 'required|max:12',
            'issued_at' => 'nullable|date',
            'access_level' => 'required|in:open,metadata_only,embargoed,restricted,private',
            'embargo_until' => 'nullable|date',
            'document_type_id' => 'nullable|integer',
            'program_id' => 'nullable|integer',
        ], [
            'collection_id' => 'Colección',
            'title' => 'Título',
            'abstract' => 'Resumen',
            'language_code' => 'Idioma',
            'issued_at' => 'Fecha de emisión',
            'access_level' => 'Nivel de acceso',
            'embargo_until' => 'Fin de embargo',
            'document_type_id' => 'Tipo documental',
            'program_id' => 'Programa',
        ]);

        if ($data['access_level'] === 'embargoed' && empty($data['embargo_until'])) {
            throw new HttpException(422, 'Embargo sin fecha.', ['embargo_until' => 'Indique la fecha de levante del embargo.']);
        }

        return [
            'collection_id' => (int) $data['collection_id'],
            'document_type_id' => !empty($data['document_type_id']) ? (int) $data['document_type_id'] : 0,
            'program_id' => !empty($data['program_id']) ? (int) $data['program_id'] : 0,
            'title' => (string) $data['title'],
            'abstract' => (string) ($data['abstract'] ?? ''),
            'language_code' => (string) $data['language_code'],
            'issued_at' => (string) ($data['issued_at'] ?? ''),
            'access_level' => (string) $data['access_level'],
            'embargo_until' => (string) ($data['embargo_until'] ?? ''),
        ];
    }

    /** @return array<int,array<string,string>> */
    private function authorsFrom(Request $request): array
    {
        $raw = $request->array('authors', []);
        $out = [];
        foreach ($raw as $author) {
            if (!is_array($author)) {
                continue;
            }
            $out[] = [
                'full_name' => (string) ($author['full_name'] ?? ''),
                'author_role' => (string) ($author['author_role'] ?? 'author'),
                'affiliation' => (string) ($author['affiliation'] ?? ''),
                'orcid' => (string) ($author['orcid'] ?? ''),
                'is_corresponding' => !empty($author['is_corresponding']),
            ];
        }
        return $out;
    }

    /** @return array<int,string> */
    private function keywordsFrom(Request $request): array
    {
        $raw = (string) $request->str('keywords');
        $parts = preg_split('/[,;\n]+/', $raw) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $parts), 'strlen')));
    }

    /**
     * @return array{allowed_extensions:string,max_file_mb:int,max_files:int}
     */
    private function collectionPolicy(int $collectionId): array
    {
        $row = \App\Core\Database::selectOne(
            'SELECT allowed_extensions, max_file_mb, max_files FROM collection_policies WHERE collection_id = ?',
            [$collectionId]
        );
        return [
            'allowed_extensions' => (string) ($row['allowed_extensions'] ?? 'pdf'),
            'max_file_mb' => (int) ($row['max_file_mb'] ?? 50),
            'max_files' => (int) ($row['max_files'] ?? 5),
        ];
    }

    /**
     * @param array<string,mixed>|null $item
     */
    private function form(Request $request, ?array $item): Response
    {
        return $this->view('admin/items/form', [
            'pageTitle' => $item === null ? 'Nuevo depósito' : 'Editar recurso',
            'item' => $item,
            'authors' => $item !== null ? $this->repo->authors((int) $item['id']) : [],
            'keywords' => $item !== null
                ? implode(', ', array_column($this->repo->keywords((int) $item['id']), 'term'))
                : '',
            'collections' => $this->repo->collectionsOptions($item === null),
            'types' => $this->repo->documentTypesOptions(),
            'programs' => $this->repo->programsOptions(),
        ]);
    }
}
