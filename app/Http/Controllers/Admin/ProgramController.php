<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Repository\CatalogRepository;
use App\Services\AuditLogger;

/**
 * Programas y dependencias (6.5).
 */
final class ProgramController extends AdminController
{
    private CatalogRepository $repo;

    public function __construct()
    {
        $this->repo = new CatalogRepository();
    }

    public function index(Request $request): Response
    {
        $this->authorize('programs.manage');
        $q = $request->str('q');
        $status = $request->str('estado');
        return $this->view('admin/programs/index', [
            'pageTitle' => 'Programas y dependencias',
            'programs' => $this->repo->programs($q, $status),
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('programs.manage');
        return $this->view('admin/programs/form', [
            'pageTitle' => 'Nuevo programa',
            'program' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('programs.manage');
        $data = $this->data($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido para generar el identificador.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->programSlugExists($slug)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un programa con ese identificador.']);
        }
        $id = $this->repo->createProgram($data['name'], $slug, $data['code'], $data['level'], $data['description'], $data['status']);
        AuditLogger::log('programs.create', 'programs', $id, null, $data['name']);
        $this->flash('success', 'Programa creado.');
        return $this->redirect('/admin/programas');
    }

    public function edit(Request $request): Response
    {
        $this->authorize('programs.manage');
        $program = $this->repo->program((int) $request->route('id'));
        if ($program === null) {
            throw new HttpException(404, 'Programa no encontrado.');
        }
        return $this->view('admin/programs/form', [
            'pageTitle' => 'Editar programa',
            'program' => $program,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('programs.manage');
        $id = (int) $request->route('id');
        $program = $this->repo->program($id);
        if ($program === null) {
            throw new HttpException(404, 'Programa no encontrado.');
        }
        $data = $this->data($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->programSlugExists($slug, $id)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe un programa con ese identificador.']);
        }
        $this->repo->updateProgram($id, $data['name'], $slug, $data['code'], $data['level'], $data['description'], $data['status']);
        AuditLogger::log('programs.update', 'programs', $id, (string) $program['name'], $data['name']);
        $this->flash('success', 'Programa actualizado.');
        return $this->redirect('/admin/programas');
    }

    /** @return array{name:string,slug:string,code:string,level:string,description:string,status:string} */
    private function data(Request $request): array
    {
        $data = $this->validate($request, [
            'name' => 'required|max:190',
            'slug' => 'nullable|slug|max:140',
            'code' => 'nullable|max:40',
            'level' => 'nullable|max:40',
            'description' => 'nullable|max:4000',
            'status' => 'required|in:active,inactive',
        ], ['name' => 'Nombre', 'slug' => 'Identificador web', 'code' => 'Código', 'level' => 'Nivel', 'status' => 'Estado']);

        return [
            'name' => (string) $data['name'],
            'slug' => (string) ($data['slug'] ?? ''),
            'code' => (string) ($data['code'] ?? ''),
            'level' => (string) ($data['level'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'status' => (string) $data['status'],
        ];
    }
}
