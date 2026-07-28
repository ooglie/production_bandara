<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class B2CRegistrationDateOfBirthTest extends TestCase
{
    use RefreshDatabase;

    public function test_b2c_registration_requires_and_stores_date_of_birth(): void
    {
        Notification::fake();
        Role::findOrCreate('Customer', 'web');

        $response = $this->post(route('register'), [
            'name' => 'B2C Customer',
            'email' => 'customer@example.test',
            'phone' => '9876543210',
            'date_of_birth' => '1990-05-14',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $user = User::query()->where('email', 'customer@example.test')->firstOrFail();

        $this->assertSame('b2c', $user->customer_type);
        $this->assertSame('1990-05-14', $user->date_of_birth?->format('Y-m-d'));
        $this->assertTrue($user->hasRole('Customer'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_b2c_registration_rejects_a_missing_or_future_date_of_birth(): void
    {
        Role::findOrCreate('Customer', 'web');

        $missing = $this->from(route('register'))->post(route('register'), [
            'name' => 'Missing DOB',
            'email' => 'missing-dob@example.test',
            'phone' => '9876543210',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $missing->assertRedirect(route('register'));
        $missing->assertSessionHasErrors('date_of_birth');

        $future = $this->from(route('register'))->post(route('register'), [
            'name' => 'Future DOB',
            'email' => 'future-dob@example.test',
            'phone' => '9876543210',
            'date_of_birth' => now()->addDay()->toDateString(),
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $future->assertRedirect(route('register'));
        $future->assertSessionHasErrors('date_of_birth');
    }
}
