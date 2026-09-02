<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class B2BCorrectivePatchSourceTest extends TestCase
{
    #[Test]
    public function location_mapping_uses_state_code_relationship(): void
    {
        $config = require config_path('b2b_application_corrective.php');

        $this->assertSame('code', $config['location']['states']['relation_key']);
        $this->assertSame('state_code', $config['location']['cities']['state_key']);

        $service = (string) file_get_contents(app_path('Services/B2B/B2BLocationService.php'));
        $this->assertStringContainsString("['code', 'state_code']", $service);
        $this->assertStringNotContainsString("->where((string) (\$config['state_id'] ?? 'state_id'), \$stateId)", $service);
    }

    #[Test]
    public function business_views_do_not_use_the_rejected_standalone_layout_or_a_new_palette(): void
    {
        $files = [
            resource_path('views/business-account/index.blade.php'),
            resource_path('views/account/business-application/step-one.blade.php'),
            resource_path('views/account/business-application/step-two.blade.php'),
            resource_path('views/account/business-application/show.blade.php'),
            resource_path('views/admin/b2b-applications/index.blade.php'),
            resource_path('views/admin/b2b-applications/show.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            $this->assertStringNotContainsString('x-layouts.business-account', $contents, $file);
            $this->assertDoesNotMatchRegularExpression(
                '/(?:slate|sky|emerald|rose|amber|orange|red|green|blue|gray|grey|purple|indigo|teal|cyan|lime|yellow|pink|violet|fuchsia)-[0-9]/',
                $contents,
                $file,
            );
        }
    }

    #[Test]
    public function business_entry_reuses_existing_customer_authentication(): void
    {
        $routes = (string) file_get_contents(base_path('routes/b2b_application.php'));
        $controller = (string) file_get_contents(app_path('Http/Controllers/BusinessAccountEntryController.php'));

        $this->assertStringContainsString("'/business/login'", $routes);
        $this->assertStringContainsString("'/business/register'", $routes);
        $this->assertStringContainsString("route('login')", $controller);
        $this->assertStringContainsString("route('register')", $controller);
        $this->assertStringNotContainsString('Auth::guard(', $controller);
    }
}
