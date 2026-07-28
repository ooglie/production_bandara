<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('Correct-password-123'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Correct-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->get(route('home'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_stores_role_cannot_open_product_write_routes_without_manage_products(): void
    {
        $role = Role::firstOrCreate(['name' => 'Stores', 'guard_name' => 'web']);
        $viewProducts = Permission::firstOrCreate(['name' => 'view products', 'guard_name' => 'web']);
        $viewStores = Permission::firstOrCreate(['name' => 'view stores', 'guard_name' => 'web']);
        $manageStores = Permission::firstOrCreate(['name' => 'manage stores', 'guard_name' => 'web']);
        $role->syncPermissions([$viewProducts, $viewStores, $manageStores]);

        $user = User::factory()->create(['customer_type' => 'staff']);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.products.create'))
            ->assertForbidden();
    }
}
