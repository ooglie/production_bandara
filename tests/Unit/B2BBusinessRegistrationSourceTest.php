<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class B2BBusinessRegistrationSourceTest extends TestCase
{
    #[Test]
    public function business_registration_is_dedicated_and_does_not_collect_personal_birth_data(): void
    {
        $view = (string) file_get_contents(resource_path('views/business-account/register.blade.php'));
        $controller = (string) file_get_contents(app_path('Http/Controllers/BusinessAccountRegistrationController.php'));
        $request = (string) file_get_contents(app_path('Http/Requests/Auth/RegisterBusinessAccountRequest.php'));
        $routes = (string) file_get_contents(base_path('routes/b2b_application.php'));

        $this->assertStringNotContainsString('date_of_birth', $view);
        $this->assertStringNotContainsString('name="dob"', $view);
        $this->assertStringNotContainsString("redirect()->route('register')", $controller);
        $this->assertStringNotContainsString("redirect()->route('register')", $routes);
        $this->assertTrue(
            str_contains($controller, "'date_of_birth' => null")
                || preg_match(
                    '/\$attributes\s*\[\s*[\'"]date_of_birth[\'"]\s*\]\s*=\s*null\s*;/',
                    $controller,
                ) === 1,
            'The business-registration controller must explicitly leave date_of_birth as NULL.',
        );
        $this->assertStringContainsString("customer_type.b2c", $controller);
        $this->assertStringContainsString('gstin', $request);
        $this->assertStringContainsString('fssai_number', $request);
        $this->assertStringContainsString("business-account.register.store", $routes);
        $this->assertStringContainsString("business-account.register.cities", $routes);
    }
}
