<?php

namespace Tests\Unit;

use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Tests\TestCase;

class SafeRedirectTest extends TestCase
{
    public function test_it_accepts_only_local_or_explicitly_allowed_redirects(): void
    {
        config([
            'app.url' => 'https://shop.bandara.test',
            'security.redirect_hosts' => ['shop.bandara.test'],
        ]);

        $request = Request::create('https://shop.bandara.test/cart', 'GET');

        $this->assertSame('/checkout?address=2', SafeRedirect::local($request, '/checkout?address=2'));
        $this->assertSame('/orders/10', SafeRedirect::local($request, 'https://shop.bandara.test/orders/10'));

        $this->assertNull(SafeRedirect::local($request, '//evil.example/path'));
        $this->assertNull(SafeRedirect::local($request, '/\\evil.example/path'));
        $this->assertNull(SafeRedirect::local($request, '%2F%2Fevil.example/path'));
        $this->assertNull(SafeRedirect::local($request, 'https://evil.example/path'));
        $this->assertNull(SafeRedirect::local($request, "https://shop.bandara.test/\r\nLocation: https://evil.example"));
    }
}
