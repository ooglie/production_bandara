<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'         => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'password'      => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'         => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'password'      => Hash::make($data['password']),
            'customer_type' => 'b2c',
            'is_active'     => true,
        ]);

        // Make sure your User model uses Spatie's HasRoles trait
        // and the "Customer" role exists from your seeders.
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('Customer');
        }

        event(new Registered($user));

        auth()->login($user);
        $request->session()->regenerate();

        // Send them either to customer dashboard or home:
        return redirect()->route('account.dashboard'); // or ->route('home')
    }
}
