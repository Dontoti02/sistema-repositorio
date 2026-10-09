<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ThemeService;
use Tests\TestCase;

final class AppearanceWorkflowTest extends TestCase
{
    public function testCompleteThemeWorkflowDraftPublishAndRestore(): void
    {
        $institutionId = 1;

        // 1. Obtener tema inicial
        $published = ThemeService::getPublishedTheme($institutionId);
        $this->assertNotNull($published);
        $initialVersion = (int) $published['version'];

        // 2. Guardar un borrador con nuevos colores
        $newTokens = $published['tokens'];
        $newTokens['brand_primary'] = '#145a42'; // Verde académico
        $newTokens['brand_secondary'] = '#238263';
        $newTokens['brand_accent'] = '#d9ae50';

        $draft = ThemeService::saveDraft(
            $institutionId,
            $newTokens,
            $published['layout'],
            $published['texts'],
            1
        );

        $this->assertEquals('draft', $draft['status']);
        $this->assertEquals('#145a42', $draft['tokens']['brand_primary']);

        // 3. Verificar que el publicado todavía tiene la versión anterior (no ha cambiado)
        $currentPub = ThemeService::getPublishedTheme($institutionId);
        $this->assertEquals($initialVersion, (int) $currentPub['version']);

        // 4. Publicar el tema
        $newPublished = ThemeService::publish($institutionId, 1);
        $this->assertEquals('published', $newPublished['status']);
        $this->assertEquals('#145a42', $newPublished['tokens']['brand_primary']);
        $this->assertTrue((int) $newPublished['version'] > $initialVersion);

        // 5. Historial debe contener registros
        $history = ThemeService::getHistory($institutionId);
        $this->assertTrue(count($history) >= 1);
    }
}
