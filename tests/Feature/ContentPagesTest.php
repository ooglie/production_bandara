<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ContentPagesTest extends TestCase
{
    public static function pageProvider(): array
    {
        return [
            ['/about-us', 'Ancient name'],
            ['/help', 'Help & FAQs'],
            ['/terms', 'Terms & Policies'],
            ['/privacy', 'Privacy Policy'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_public_content_page_is_available(string $uri, string $heading): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertSee($heading);
    }

    public function test_legacy_content_urls_redirect_to_canonical_pages(): void
    {
        $this->get('/faq')->assertRedirect('/help');
        $this->get('/terms-and-conditions')->assertRedirect('/terms');
        $this->get('/privacy-policy')->assertRedirect('/privacy');
    }
}
