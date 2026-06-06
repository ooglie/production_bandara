<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])
                ->onlyInput('email');
        }

        // ✅ Regenerate session (prevents fixation) — intended URL is preserved
        $request->session()->regenerate();

        // ✅ IMPORTANT: do NOT return redirectPathFor() here,
        // because it overrides intended redirect (e.g. checkout).
        return $this->authenticated($request, Auth::user());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been logged out.');
    }

    /**
     * After successful login:
     * - Attach guest cart to user if session cart exists
     * - Redirect to intended URL FIRST (checkout), otherwise role-based dashboard
     */
    protected function authenticated(Request $request, $user)
    {
        // ✅ attach guest cart (by session cart_id) to this user
        $cartId = $request->session()->get('cart_id');

        if ($cartId) {
            \App\Models\Cart::where('id', $cartId)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id, 'session_id' => null]);
        }

        $fallback = $this->redirectPathFor($user);

        // Delivery agents should never be sent to a stale/customer/admin
        // intended URL after login. Mobile browsers often preserve an
        // intended URL from a previous protected page, which can send a
        // DeliveryAgent user to a route protected by another role and cause
        // a 403 immediately after successful login.
        if ($user->hasRole('DeliveryAgent')) {
            $request->session()->forget('url.intended');

            return redirect()->to($fallback);
        }

        // ✅ If Laravel stored an intended URL (like /checkout), honor it for
        // storefront customers. The storefront is unified and prices are
        // resolved by account type after login.
        return redirect()->intended($fallback);
    }

    protected function redirectPathFor($user): string
    {
        // Spatie roles
        if ($user->hasRole('Admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('Manager')) {
            return route('manager.dashboard');
        }

        if ($user->hasRole('Support')) {
            return route('support.dashboard');
        }

        if ($user->hasRole('Accountant')) {
            return route('accountant.dashboard');
        }

        if ($user->hasRole('CAAccountant')) {
            return route('accountant.dashboard');
        }

        if ($user->hasRole('Stores')) {
            return route('stores.dashboard');
        }

        if ($user->hasRole('DeliveryAgent')) {
            return route('delivery.index');
        }

        // B2B users use the unified storefront/catalogue with account-aware
        // pricing, MOQ, and Pay Later terms. Send them to Shop by default so
        // they immediately see the full active product catalogue.
        if ($user->hasRole('Customer') && (($user->customer_type ?? 'b2c') === 'b2b')) {
            return route('shop.index');
        }

        return route('dashboard.customer');
    }
}