<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Repository\CatalogRepository;
use App\Services\AuditLogger;

/**
 * Comunidades y colecciones (6.6).
 */
final class CommunityController extends AdminController
{
    private CatalogRepository $repo;

    public function __construct()
    {
        $this->repo = new CatalogRepository();
    }

    // ---------- Comunidades ----------

    public function index(Request $request): Response
    {
        $this->authorize('communities.manage');
        $q = $request->str('q');
        return $this->view('admin/communities/index', [
            'pageTitle' => 'Comunidades y colecciones',
            'communities' => $this->repo->communities($q),
            'collections' => $this->repo->collections(),
            'q' => $q,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('communities.manage');
        return $this->view('admin/communities/form', [
            'pageTitle' => 'Nueva comunidad',
            'community' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('communities.manage');
        $data = $this->communityData($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('communities', $slug)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe una comunidad con ese identificador.']);
        }
        $id = $this->repo->createCommunity($data['name'], $slug, $data['description'], $data['is_public']);
        AuditLogger::log('communities.create', 'communities', $id, null, $data['name']);
        $this->flash('success', 'Comunidad creada.');
        return $this->redirect('/admin/comunidades/' . $id . '/editar');
    }

    public function edit(Request $request): Response
    {
        $this->authorize('communities.manage');
        $community = $this->repo->community((int) $request->route('id'));
        if ($community === null) {
            throw new HttpException(404, 'Comunidad no encontrada.');
        }
        return $this->view('admin/communities/form', [
            'pageTitle' => 'Editar comunidad',
            'community' => $community,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('communities.manage');
        $id = (int) $request->route('id');
        $community = $this->repo->community($id);
        if ($community === null) {
            throw new HttpException(404, 'Comunidad no encontrada.');
        }
        $data = $this->communityData($request);
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('communities', $slug, $id)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe una comunidad con ese identificador.']);
        }
        $this->repo->updateCommunity($id, $data['name'], $slug, $data['description'], $data['is_public']);
        AuditLogger::log('communities.update', 'communities', $id, (string) $community['name'], $data['name']);
        $this->flash('success', 'Comunidad actualizada.');
        return $this->redirect('/admin/comunidades');
    }

    public function destroy(Request $request): Response
    {
        $this->authorize('communities.manage');
        $id = (int) $request->route('id');
        $community = $this->repo->community($id);
        if ($community === null) {
            throw new HttpException(404, 'Comunidad no encontrada.');
        }
        $this->repo->deleteCommunity($id);
        AuditLogger::log('communities.delete', 'communities', $id, (string) $community['name'], null);
        $this->flash('success', 'Comunidad eliminada.');
        return $this->redirect('/admin/comunidades');
    }

    // ---------- Colecciones ----------

    public function createCollection(Request $request): Response
    {
        $this->authorize('collections.manage');
        return $this->view('admin/communities/collection_form', [
            'pageTitle' => 'Nueva coleccion',
            'collection' => null,
            'communities' => $this->repo->communitiesOptions(),
            'defaultCommunityId' => (int) $request->int('comunidad', 0),
        ]);
    }

    public function storeCollection(Request $request): Response
    {
        $this->authorize('collections.manage');
        $data = $this->collectionData($request);
        if ($this->repo->community($data['community_id']) === null) {
            throw new HttpException(422, 'Comunidad invalida.', ['community_id' => 'Seleccione una comunidad valida.']);
        }
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('collections', $slug)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe una coleccion con ese identificador.']);
        }
        $id = $this->repo->createCollection(
            $data['community_id'], $data['name'], $slug, $data['description'],
            $data['is_public'], $data['deposit_enabled'], $data['requires_approval']
        );
        AuditLogger::log('collections.create', 'collections', $id, null, $data['name']);
        $this->flash('success', 'Coleccion creada.');
        return $this->redirect('/admin/comunidades');
    }

    public function editCollection(Request $request): Response
    {
        $this->authorize('collections.manage');
        $collection = $this->repo->collection((int) $request->route('id'));
        if ($collection === null) {
            throw new HttpException(404, 'Coleccion no encontrada.');
        }
        return $this->view('admin/communities/collection_form', [
            'pageTitle' => 'Editar coleccion',
            'collection' => $collection,
            'communities' => $this->repo->communitiesOptions(),
            'defaultCommunityId' => (int) $collection['community_id'],
        ]);
    }

    public function updateCollection(Request $request): Response
    {
        $this->authorize('collections.manage');
        $id = (int) $request->route('id');
        $collection = $this->repo->collection($id);
        if ($collection === null) {
            throw new HttpException(404, 'Coleccion no encontrada.');
        }
        $data = $this->collectionData($request);
        if ($this->repo->community($data['community_id']) === null) {
            throw new HttpException(422, 'Comunidad invalida.', ['community_id' => 'Seleccione una comunidad valida.']);
        }
        $slug = $data['slug'] !== '' ? $data['slug'] : slugify($data['name']);
        if ($slug === '') {
            throw new HttpException(422, 'Nombre no valido.', ['name' => 'Proporcione un nombre valido.']);
        }
        if ($this->repo->slugExists('collections', $slug, $id)) {
            throw new HttpException(422, 'Identificador duplicado.', ['slug' => 'Ya existe una coleccion con ese identificador.']);
        }
        $this->repo->updateCollection(
            $id, $data['community_id'], $data['name'], $slug, $data['description'],
            $data['is_public'], $data['deposit_enabled'], $data['requires_approval']
        );
        AuditLogger::log('collections.update', 'collections', $id, (string) $collection['name'], $data['name']);
        $this->flash('success', 'Coleccion actualizada.');
        return $this->redirect('/admin/comunidades');
    }

    public function destroyCollection(Request $request): Response
    {
        $this->authorize('collections.manage');
        $id = (int) $request->route('id');
        $collection = $this->repo->collection($id);
        if ($collection === null) {
            throw new HttpException(404, 'Coleccion no encontrada.');
        }
        $this->repo->deleteCollection($id);
        AuditLogger::log('collections.delete', 'collections', $id, (string) $collection['name'], null);
        $this->flash('success', 'Coleccion eliminada.');
        return $this->redirect('/admin/comunidades');
    }

    /** @return array{name:string,slug:string,description:string,is_public:bool} */
    private function communityData(Request $request): array
    {
        $data = $this->validate($request, [
            'name' => 'required|max:190',
            'slug' => 'nullable|slug|max:140',
            'description' => 'nullable|max:8000',
        ], ['name' => 'Nombre', 'slug' => 'Identificador web', 'description' => 'Descripcion']);

        return [
            'name' => (string) $data['name'],
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'is_public' => $request->input('is_public') !== null,
        ];
    }

    /** @return array{community_id:int,name:string,slug:string,description:string,is_public:bool,deposit_enabled:bool,requires_approval:bool} */
    private function collectionData(Request $request): array
    {
        $data = $this->validate($request, [
            'community_id' => 'required|integer',
            'name' => 'required|max:190',
            'slug' => 'nullable|slug|max:140',
            'description' => 'nullable|max:8000',
        ], ['community_id' => 'Comunidad', 'name' => 'Nombre', 'slug' => 'Identificador web', 'description' => 'Descripcion']);

        return [
            'community_id' => (int) $data['community_id'],
            'name' => (string) $data['name'],
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'is_public' => $request->input('is_public') !== null,
            'deposit_enabled' => $request->input('deposit_enabled') !== null,
            'requires_approval' => $request->input('requires_approval') !== null,
        ];
    }
}
