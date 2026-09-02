<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('security.administrator_provisioning.email'));
        $password = (string) config('security.administrator_provisioning.password');
        $name = trim((string) config('security.administrator_provisioning.name', 'Bandara Administrator'));

        if ($email === '' || $password === '') {
            throw ValidationException::withMessages([
                'admin' => 'ADMIN_EMAIL and ADMIN_PASSWORD must be explicitly set before running AdminUserSeeder.',
            ]);
        }

        validator(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email:rfc'],
                'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            ]
        )->validate();

        $adminRole = Role::where('name', 'Admin')->first();
        if (! $adminRole) {
            throw ValidationException::withMessages([
                'admin' => 'Admin role not found. Run RolesAndPermissionsSeeder first.',
            ]);
        }

        $user = User::where('email', $email)->first();
        $allowPromotion = filter_var(
            config('security.administrator_provisioning.promote_existing', false),
            FILTER_VALIDATE_BOOL
        );

        if ($user && ! $user->hasRole('Admin') && ! $allowPromotion) {
            throw ValidationException::withMessages([
                'admin' => 'The configured ADMIN_EMAIL belongs to a non-Admin account. Set ADMIN_PROMOTE_EXISTING_USER=true only after verifying that intentional promotion.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'name' => $name !== '' ? $name : 'Bandara Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
                'customer_type' => 'staff',
                'email_verified_at' => now(),
            ]);
        } else {
            // Explicit execution is treated as a credential rotation, not a
            // silent "firstOrCreate" that can preserve a known old password.
            $user->forceFill([
                'name' => $name !== '' ? $name : $user->name,
                'password' => Hash::make($password),
                'is_active' => true,
                'customer_type' => 'staff',
                'email_verified_at' => $user->email_verified_at ?: now(),
                'remember_token' => null,
            ])->save();
        }

        $user->syncRoles(['Admin']);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        $this->command?->info("Administrator provisioned and password rotated: {$email}");
    }
}
