<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CheckoutLoginRedirectTest extends TestCase
{
    public function test_login_form_preserves_checkout_redirect(): void
    {
        $this->get(route('login', ['redirect' => '/checkout']))
            ->assertOk()
            ->assertSessionHas('url.intended', '/checkout')
            ->assertSee('name="redirect"', false)
            ->assertSee('value="/checkout"', false);
    }

    public function test_login_form_rejects_external_redirects(): void
    {
        $this->get(route('login', ['redirect' => 'https://example.com/checkout']))
            ->assertOk()
            ->assertSessionMissing('url.intended')
            ->assertDontSee('name="redirect"', false);
    }
}
