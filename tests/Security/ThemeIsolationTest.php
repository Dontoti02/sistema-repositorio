<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ThemeService;
use Tests\TestCase;

final class ThemeIsolationTest extends TestCase
{
    public function testSanitizeHexPreventsCssInjectionAndXss(): void
    {
        $maliciousInputs = [
            '#113b70; } body { display:none }',
            'javascript:alert(1)',
            'expression(alert(1))',
            '<script>alert(1)</script>',
            'url("http://attacker.com/steal")',
            '#113b70/*comment*/',
        ];

        foreach ($maliciousInputs as $badInput) {
            $sanitized = ThemeService::sanitizeHex($badInput, '#113b70');
            $this->assertEquals('#113b70', $sanitized, "Input '{$badInput}' debió caer en fallback seguro.");
            $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $sanitized);
        }
    }

    public function testCompiledCssContainsOnlySafeVariables(): void
    {
        $tokens = [
            'brand_primary' => '#113b70; bad-injection: 1',
            'radius_md' => '12px; font-size: 100px',
            'font_sans' => 'malicious_font; onload=alert(1)',
        ];

        $css = ThemeService::compileCss($tokens);

        $this->assertStringNotContainsString('bad-injection', $css);
        $this->assertStringNotContainsString('font-size: 100px', $css);
        $this->assertStringNotContainsString('onload=alert', $css);
    }

    public function testMultiTenantIsolationBetweenInstitutions(): void
    {
        $themeA = ThemeService::getPublishedTheme(1);
        $this->assertNotNull($themeA);

        // Crear institución de prueba B temporal
        $pdo = \App\Core\Database::connection();
        $pdo->exec("INSERT INTO institutions (uuid, name, slug, status, primary_color, secondary_color, accent_color)
                    VALUES ('b0000000-0000-0000-0000-000000000002', 'Instituto Test B', 'test-tenant-b', 'active', '#702c44', '#9e4b67', '#dca64f')
                    ON DUPLICATE KEY UPDATE name = 'Instituto Test B'");
        $instB = \App\Core\Database::selectOne("SELECT id FROM institutions WHERE slug = 'test-tenant-b'");
        $instBId = (int) $instB['id'];

        try {
            $defaultsB = ThemeService::getDefaults();
            $tokensB = $defaultsB['tokens'];
            $tokensB['brand_primary'] = '#702c44'; // Granate

            $draftB = ThemeService::saveDraft($instBId, $tokensB, $defaultsB['layout'], $defaultsB['texts'], null);

            // El tema publicado de A no debe ser afectado por el borrador de B
            $themeAAfter = ThemeService::getPublishedTheme(1);
            $this->assertEquals($themeA['tokens']['brand_primary'], $themeAAfter['tokens']['brand_primary']);
            $this->assertNotEquals('#702c44', $themeAAfter['tokens']['brand_primary']);

            // El tema de B debe reflejar sus propios colores
            $this->assertEquals('#702c44', $draftB['tokens']['brand_primary']);
        } finally {
            $pdo->exec("DELETE FROM institutions WHERE id = {$instBId}");
        }
    }

    public function testInstitutionWithoutAssetsGracefullyReturnsDefaults(): void
    {
        $theme = ThemeService::getPublishedTheme(1);
        $this->assertNotNull($theme);
        $this->assertNotEmpty($theme['tokens']['brand_primary']);
        $this->assertNotEmpty($theme['layout']['hero_mode']);
        $this->assertNotEmpty($theme['texts']['hero_title']);
    }
}
