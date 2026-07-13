<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductStorageProfileTest extends TestCase
{
    public function test_frozen_is_the_default_storage_profile(): void
    {
        $this->assertSame('frozen', Product::DEFAULT_STORAGE_PROFILE);
        $this->assertSame('frozen', Product::normalizeStorageProfile(null));
        $this->assertSame('frozen', Product::normalizeStorageProfile('unknown'));
    }

    public function test_chilled_profile_has_chilled_guidance_and_delivery_text(): void
    {
        $this->assertArrayHasKey('chilled', Product::storageProfileOptions());
        $this->assertStringContainsString('2°C and 6°C', Product::storageGuidanceTextForProfile('chilled'));
        $this->assertStringContainsString('Delivered chilled', Product::deliverySupportTextForProfile('chilled'));
    }
}
