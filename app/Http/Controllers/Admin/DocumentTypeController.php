<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Repository\CatalogRepository;
use App\Services\AuditLogger;

/**
 * Tipos documentales (6.7): catalogo de tipos aplicables a los depositos.
 */
final class DocumentTypeController extends AdminController
{
    private CatalogRepository $repo;

    public function __construct()
    {
        $this->repo = new CatalogRepository();
    }

    public function index(Request $request): Response
    {
        $this->authorize('metadata.manage');
        return $this->view('admin/document_types/index', [
            'pageTitle' => 'Tipos documentales',
            'types' => $this->repo->documentTypes(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('metadata.manage');
        return $this->view('admin/document_types/form', [
            'pageTitle' => 'Nuevo tipo documental',
            'type' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $data = $this->data($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('document_types', $slug)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un tipo con ese identificador.']);
        }
        $id = $this->repo->createDocumentType($data['name'], $slug, $data['description'], $data['requires_degree_fields'], $data['is_active']);
        AuditLogger::log('document_types.create', 'document_types', $id, null, $data['name']);
        $this->flash('success', 'Tipo documental creado.');
        return $this->redirect('/admin/tipos-documentales');
    }

    public function edit(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $type = $this->repo->documentType((int) $request->route('id'));
        if ($type === null) {
            throw new HttpException(404, 'Tipo documental no encontrado.');
        }
        return $this->view('admin/document_types/form', [
            'pageTitle' => 'Editar tipo documental',
            'type' => $type,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('metadata.manage');
        $id = (int) $request->route('id');
        $type = $this->repo->documentType($id);
        if ($type === null) {
            throw new HttpException(404, 'Tipo documental no encontrado.');
        }
        $data = $this->data($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('document_types', $slug, $id)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un tipo con ese identificador.']);
        }
        $this->repo->updateDocumentType($id, $data['name'], $slug, $data['description'], $data['requires_degree_fields'], $data['is_active']);
        AuditLogger::log('document_types.update', 'document_types', $id, (string) $type['name'], $data['name']);
        $this->flash('success', 'Tipo documental actualizado.');
        return $this->redirect('/admin/tipos-documentales');
    }

    /** @return array{name:string,slug:string,description:string,requires_degree_fields:bool,is_active:bool} */
    private function data(Request $request): array
    {
        $data = $this->validate($request, [
            'name' => 'required|max:140',
            'slug' => 'nullable|slug|max:100',
            'description' => 'nullable|max:255',
        ], ['name' => 'Nombre', 'slug' => 'Identificador web', 'description' => 'Descripcion']);

        return [
            'name' => (string) $data['name'],
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'requires_degree_fields' => $request->input('requires_degree_fields') !== null,
            'is_active' => $request->input('is_active') !== null,
        ];
    }
}
