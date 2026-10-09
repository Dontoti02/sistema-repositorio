<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BrandAssetService;
use Tests\TestCase;

final class BrandAssetServiceTest extends TestCase
{
    public function testAllowedKindsConstants(): void
    {
        $this->assertCount(6, BrandAssetService::ALLOWED_KINDS);
        $this->assertTrue(in_array('logo_primary', BrandAssetService::ALLOWED_KINDS, true));
        $this->assertTrue(in_array('favicon', BrandAssetService::ALLOWED_KINDS, true));
        $this->assertTrue(in_array('hero', BrandAssetService::ALLOWED_KINDS, true));
    }

    public function testInvalidKindThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        BrandAssetService::uploadAsset(1, 'malicious_kind', []);
    }
}
