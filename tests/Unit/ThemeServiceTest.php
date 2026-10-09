<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ThemeService;
use Tests\TestCase;

final class ThemeServiceTest extends TestCase
{
    public function testGetDefaultsContainsRequiredTokensAndLayout(): void
    {
        $defaults = ThemeService::getDefaults();

        $this->assertArrayHasKey('tokens', $defaults);
        $this->assertArrayHasKey('layout', $defaults);
        $this->assertArrayHasKey('texts', $defaults);

        $tokens = $defaults['tokens'];
        $this->assertArrayHasKey('brand_primary', $tokens);
        $this->assertArrayHasKey('brand_secondary', $tokens);
        $this->assertArrayHasKey('brand_accent', $tokens);
        $this->assertArrayHasKey('page_bg', $tokens);
        $this->assertArrayHasKey('surface', $tokens);
        $this->assertArrayHasKey('text', $tokens);

        $this->assertEquals('#113b70', $tokens['brand_primary']);
    }

    public function testSanitizeHexNormalizesValidHexAndRejectsInvalid(): void
    {
        $this->assertEquals('#113b70', ThemeService::sanitizeHex('#113B70', '#000000'));
        $this->assertEquals('#ffffff', ThemeService::sanitizeHex('#FFF', '#000000'));
        $this->assertEquals('#123456', ThemeService::sanitizeHex('123456', '#000000'));
        // Invalido cae en fallback
        $this->assertEquals('#000000', ThemeService::sanitizeHex('not-a-color', '#000000'));
        $this->assertEquals('#000000', ThemeService::sanitizeHex('javascript:alert(1)', '#000000'));
        $this->assertEquals('#000000', ThemeService::sanitizeHex('red; background:url(x)', '#000000'));
    }

    public function testCalculateContrastRatio(): void
    {
        // Blanco sobre negro = 21:1
        $ratio = ThemeService::calculateContrastRatio('#ffffff', '#000000');
        $this->assertTrue($ratio >= 20.9 && $ratio <= 21.1, 'Ratio blanco y negro debe ser 21');

        // Mismo color = 1:1
        $sameRatio = ThemeService::calculateContrastRatio('#ffffff', '#ffffff');
        $this->assertEquals(1.0, $sameRatio, 'Ratio mismo color debe ser 1');

        // Contraste de paleta institucional azul #113B70 sobre blanco #FFFFFF debe superar 4.5:1 (WCAG AA)
        $brandRatio = ThemeService::calculateContrastRatio('#ffffff', '#113b70');
        $this->assertTrue($brandRatio >= 4.5, "Ratio azul institucional debe ser >= 4.5, obtenido: {$brandRatio}");
    }

    public function testDeriveOnColorReturnsHighContrastText(): void
    {
        // Sobre color muy oscuro, texto debe ser blanco
        $onDark = ThemeService::deriveOnColor('#113b70');
        $this->assertEquals('#ffffff', $onDark);

        // Sobre color muy claro (amarillo brillante o blanco), texto debe ser oscuro
        $onLight = ThemeService::deriveOnColor('#ffff00');
        $this->assertEquals('#152438', $onLight);
    }

    public function testCompileCssGeneratesValidEscapedRootVariables(): void
    {
        $tokens = [
            'brand_primary' => '#113b70',
            'brand_secondary' => '#2873bd',
            'brand_accent' => '#e4ae35',
            'page_bg' => '#f6f8fc',
            'surface' => '#ffffff',
            'text' => '#152438',
            'radius_md' => '12px',
        ];

        $css = ThemeService::compileCss($tokens);

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--brand-primary: #113b70;', $css);
        $this->assertStringContainsString('--brand-secondary: #2873bd;', $css);
        $this->assertStringContainsString('--brand-accent: #e4ae35;', $css);
        $this->assertStringContainsString('--radius-md: 12px;', $css);
        $this->assertStringContainsString('}', $css);
    }
}
