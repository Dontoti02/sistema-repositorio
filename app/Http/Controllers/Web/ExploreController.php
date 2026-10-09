<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Domain\Repository\PublicRepository;

/**
 * Exploración: comunidades, colecciones, programas y autores.
 */
final class ExploreController extends Controller
{
    public function communities(Request $request): Response
    {
        $repo = new PublicRepository();
        return $this->view('web/communities', [
            'pageTitle' => 'Comunidades y colecciones',
            'metaDescription' => 'Explore las comunidades académicas y colecciones del repositorio institucional.',
            'communities' => $repo->communities(),
        ]);
    }

    public function community(Request $request): Response
    {
        $repo = new PublicRepository();
        $community = $repo->communityBySlug((string) $request->route('slug'));
        if ($community === null) {
            throw new HttpException(404, 'La comunidad solicitada no existe o no es pública.');
        }
        $repo = new PublicRepository();
        $children = $repo->communities((int) $community['id']);
        return $this->view('web/community', [
            'pageTitle' => (string) $community['name'],
            'metaDescription' => mb_substr((string) ($community['description'] ?? ''), 0, 155),
            'community' => $community,
            'collections' => $repo->collectionsIn((int) $community['id']),
            'children' => $children,
        ]);
    }

    public function collection(Request $request): Response
    {
        $repo = new PublicRepository();
        $collection = $repo->collectionBySlug((string) $request->route('slug'));
        if ($collection === null) {
            throw new HttpException(404, 'La colección solicitada no existe o no es pública.');
        }
        $page = max(1, $request->int('pagina', 1));
        $result = $repo->itemsInCollection((int) $collection['id'], $page, (int) config('repository.page_size_public', 12));
        return $this->view('web/collection', [
            'pageTitle' => (string) $collection['name'],
            'metaDescription' => mb_substr((string) ($collection['description'] ?? ''), 0, 155),
            'collection' => $collection,
            'result' => $result,
            'page' => $page,
        ]);
    }

    public function program(Request $request): Response
    {
        $repo = new PublicRepository();
        $program = $repo->programBySlug((string) $request->route('slug'));
        if ($program === null) {
            throw new HttpException(404, 'El programa solicitado no existe.');
        }
        $page = max(1, $request->int('pagina', 1));
        $result = $repo->search(['programa' => $program['slug']], $page, (int) config('repository.page_size_public', 12));
        return $this->view('web/program', [
            'pageTitle' => (string) $program['name'],
            'metaDescription' => 'Publicaciones del programa ' . (string) $program['name'],
            'program' => $program,
            'result' => $result,
            'page' => $page,
        ]);
    }

    public function authors(Request $request): Response
    {
        $repo = new PublicRepository();
        return $this->view('web/authors', [
            'pageTitle' => 'Autores',
            'metaDescription' => 'Autores con publicaciones en el repositorio institucional.',
            'authors' => $repo->authors(),
        ]);
    }

    public function author(Request $request): Response
    {
        $name = trim((string) $request->route('slug'));
        if (ctype_digit($name)) {
            $author = Database::selectOne(
                'SELECT full_name FROM item_authors WHERE id = ? AND institution_id = ?',
                [(int) $name, TenantContext::institutionId()]
            );
            if ($author !== null) {
                $name = (string) $author['full_name'];
            }
        }
        $page = max(1, $request->int('pagina', 1));
        $repo = new PublicRepository();
        $result = $repo->search(['autor' => $name], $page, (int) config('repository.page_size_public', 12));
        if ($result['total'] === 0) {
            throw new HttpException(404, 'No se encontraron publicaciones de este autor.');
        }
        return $this->view('web/author', [
            'pageTitle' => 'Autor: ' . $name,
            'metaDescription' => 'Publicaciones de ' . $name . ' en el repositorio.',
            'authorName' => $name,
            'result' => $result,
            'page' => $page,
        ]);
    }
}
