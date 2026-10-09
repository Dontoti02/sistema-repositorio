<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\BrandAssetService;
use App\Services\ThemeService;
use Throwable;

/**
 * Controlador del módulo Apariencia e Identidad Institucional.
 * Gestiona el editor visual de temas, carga de activos de marca (logo, favicon, portada),
 * guardado de borrador, vista previa responsive y publicación transaccional.
 */
final class AppearanceController extends AdminController
{
    /**
     * Muestra la interfaz principal del constructor de apariencia.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess();

        $institutionId = TenantContext::institutionId();
        $institution = TenantContext::institution() ?? [];

        $publishedTheme = ThemeService::getPublishedTheme($institutionId);
        $draftTheme     = ThemeService::getDraftTheme($institutionId);
        $history        = ThemeService::getHistory($institutionId, 10);

        // Activos vigentes
        $assets = [
            'logo_primary'   => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_LOGO_PRIMARY),
            'logo_alternate' => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_LOGO_ALTERNATE),
            'logo_compact'   => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_LOGO_COMPACT),
            'favicon'        => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_FAVICON),
            'hero'           => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_HERO),
            'default_cover'  => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_DEFAULT_COVER),
        ];

        return $this->view('admin/appearance/index', [
            'pageTitle'      => 'Apariencia e identidad institucional',
            'institution'    => $institution,
            'publishedTheme' => $publishedTheme,
            'draftTheme'     => $draftTheme,
            'activeTheme'    => $draftTheme ?? $publishedTheme,
            'assets'         => $assets,
            'presets'        => ThemeService::PRESET_PALETTES,
            'history'        => $history,
        ]);
    }

    /**
     * Guarda el borrador del tema sin afectar el portal público.
     */
    public function saveDraft(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $body = $this->getJsonOrPost($request);
        $tokens = (array) ($body['tokens'] ?? []);
        $layout = (array) ($body['layout'] ?? []);
        $texts  = (array) ($body['texts'] ?? []);

        try {
            $draft = ThemeService::saveDraft($institutionId, $tokens, $layout, $texts, Auth::id());
            return Response::json([
                'ok'      => true,
                'message' => 'Borrador guardado exitosamente.',
                'draft'   => $draft,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'ok'      => false,
                'message' => 'Error al guardar el borrador: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Renderiza el contenido para el marco de previsualización (iframe).
     */
    public function preview(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $mode = $request->query('mode', 'draft');
        $theme = ($mode === 'draft')
            ? (ThemeService::getDraftTheme($institutionId) ?? ThemeService::getPublishedTheme($institutionId))
            : ThemeService::getPublishedTheme($institutionId);

        $institution = TenantContext::institution() ?? [];

        // Servir la vista de preview con el layout público pero con noindex y tokens del preview
        return new Response(200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow',
        ], \App\Core\View::partial('admin/appearance/preview', [
            'theme'       => $theme,
            'institution' => $institution,
            'assets'      => [
                'logo_primary' => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_LOGO_PRIMARY),
                'favicon'      => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_FAVICON),
                'hero'         => BrandAssetService::getAssetUrl($institutionId, BrandAssetService::KIND_HERO),
            ],
        ]));
    }

    /**
     * Publica el tema institucional de forma atómica y transaccional.
     */
    public function publish(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $body = $this->getJsonOrPost($request);
        if (!empty($body['tokens'])) {
            // Si el cliente envía datos nuevos, asegurar guardado como borrador primero
            ThemeService::saveDraft(
                $institutionId,
                (array) ($body['tokens'] ?? []),
                (array) ($body['layout'] ?? []),
                (array) ($body['texts'] ?? []),
                Auth::id()
            );
        }

        try {
            $published = ThemeService::publish($institutionId, Auth::id());
            return Response::json([
                'ok'        => true,
                'message'   => 'Identidad institucional y apariencia publicadas con éxito.',
                'published' => $published,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'ok'      => false,
                'message' => 'No se pudo publicar la apariencia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaura una versión anterior del tema desde el historial.
     */
    public function restore(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $themeId = (int) $request->input('theme_id', 0);
        if ($themeId <= 0) {
            return Response::json(['ok' => false, 'message' => 'ID de versión inválido.'], 422);
        }

        try {
            $restored = ThemeService::restoreVersion($institutionId, $themeId, Auth::id());
            return Response::json([
                'ok'       => true,
                'message'  => "Versión {$restored['version']} restaurada y publicada exitosamente.",
                'restored' => $restored,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'ok'      => false,
                'message' => 'Error al restaurar versión: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sube un activo de marca institucional (logo principal, alternativo, favicon, portada).
     */
    public function uploadAsset(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $kind = (string) $request->input('kind', '');
        $file = $_FILES['file'] ?? $_FILES['archivo'] ?? null;

        if ($file === null) {
            return Response::json(['ok' => false, 'message' => 'No se recibió ningún archivo.'], 422);
        }

        try {
            $asset = BrandAssetService::uploadAsset($institutionId, $kind, $file, Auth::id());
            return Response::json([
                'ok'      => true,
                'message' => 'Archivo subido y verificado correctamente.',
                'asset'   => $asset,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina un activo de marca institucional.
     */
    public function deleteAsset(Request $request): Response
    {
        $this->authorizeAccess();
        $institutionId = TenantContext::institutionId();

        $kind = (string) $request->input('kind', '');

        try {
            $deleted = BrandAssetService::deleteAsset($institutionId, $kind, Auth::id());
            return Response::json([
                'ok'      => $deleted,
                'message' => $deleted ? 'Activo eliminado correctamente.' : 'El activo no existía.',
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restablece la paleta de colores a los valores por defecto del tema base.
     */
    public function resetPalette(Request $request): Response
    {
        $this->authorizeAccess();
        $defaults = ThemeService::getDefaults();

        return Response::json([
            'ok'     => true,
            'tokens' => $defaults['tokens'],
        ]);
    }

    /**
     * Valida autorización administrativa para gestionar la apariencia.
     */
    private function authorizeAccess(): void
    {
        if (!Authorization::canAny(['institution.update', 'settings.manage'])) {
            Authorization::authorize('institution.update');
        }
    }

    /**
     * Extrae datos tanto de cuerpo JSON como de form-data.
     *
     * @return array<string,mixed>
     */
    private function getJsonOrPost(Request $request): array
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '' && str_starts_with(trim($raw), '{')) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return $request->all();
    }
}
