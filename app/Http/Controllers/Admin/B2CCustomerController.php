<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class B2CCustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');

        $query = User::query()
            ->where('customer_type', 'b2c')
            ->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('admin.customers.b2c.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.b2c.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => ['required', 'string', 'max:20'],
            'gst_number'    => ['nullable', 'string', 'max:50'],
            'fssai_number'  => ['nullable', 'string', 'max:50'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'mark_email_verified'   => ['nullable', 'boolean'],
            'is_active'             => ['nullable', 'boolean'],
        ]);

        $u = new User();
        $u->name = $data['name'];
        $u->email = $data['email'];
        $u->phone = $data['phone'];
        $u->gst_number = $data['gst_number'];
        $u->fssai_number = $data['fssai_number'];
        $u->password = Hash::make($data['password']);
        $u->is_active = $request->boolean('is_active', true);
        $u->customer_type = 'b2c';

        if ($request->boolean('mark_email_verified')) {
            $u->email_verified_at = now();
        }

        $u->save();

        // Force role = Customer
        if (method_exists($u, 'syncRoles')) {
            $u->syncRoles(['Customer']);
        }

        return redirect()
            ->route('admin.customers.b2c.index')
            ->with('status', 'B2C customer created.');
    }

    public function edit(User $user)
    {
        abort_unless(($user->customer_type ?? null) === 'b2c', 404);

        return view('admin.customers.b2c.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(($user->customer_type ?? null) === 'b2c', 404);

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'               => ['required', 'string', 'max:20'],
            'password'            => ['nullable', 'string', 'min:8', 'confirmed'],
            'gst_number'    => ['nullable', 'string', 'max:50'],
            'fssai_number'  => ['nullable', 'string', 'max:50'],
            'mark_email_verified' => ['nullable', 'boolean'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->gst_number = $data['gst_number'];
        $user->fssai_number = $data['fssai_number'];

        // dd($user->gst_number,$user->fssai_number);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->boolean('mark_email_verified') && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->is_active = $request->boolean('is_active', $user->is_active);
        $user->customer_type = 'b2c';

        $user->save();

        // keep role pinned
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['Customer']);
        }

        return redirect()
            ->route('admin.customers.b2c.index')
            ->with('status', 'B2C customer updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless(($user->customer_type ?? null) === 'b2c', 404);

        if ($request->user()->id === $user->id) {
            return back()->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('status', 'B2C customer deleted.');
    }
}
