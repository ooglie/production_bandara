<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class B2BCustomerMoqController extends Controller
{
    protected function ensureB2B(User $user): void
    {
        if (($user->customer_type ?? 'b2c') !== 'b2b') {
            abort(404);
        }
    }

    /**
     * MOQ overrides for a customer (Option B).
     * - No row => MOQ = 1
     * - Row active => MOQ = min_order_quantity
     */
    public function index(User $user)
    {
        $this->ensureB2B($user);

        $overrides = B2BCustomerProduct::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->paginate(25);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('admin.b2b.moq.index', compact('user', 'overrides', 'products'));
    }

    public function store(Request $request, User $user)
    {
        $this->ensureB2B($user);

        $data = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'min_order_quantity' => ['required', 'numeric', 'min:0.01'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $row = B2BCustomerProduct::query()
            ->where('user_id', $user->id)
            ->where('product_id', $data['product_id'])
            ->first();

        if (!$row) {
            $row = new B2BCustomerProduct();
            $row->user_id = $user->id;
            $row->product_id = (int) $data['product_id'];
        }

        $row->min_order_quantity = (float) $data['min_order_quantity'];
        $row->is_active = $request->boolean('is_active', true);
        $row->save();

        return back()->with('status', 'MOQ saved.');
    }

    public function update(Request $request, User $user, B2BCustomerProduct $row)
    {
        $this->ensureB2B($user);

        if ((int) $row->user_id !== (int) $user->id) {
            abort(404);
        }

        $data = $request->validate([
            'min_order_quantity' => ['required', 'numeric', 'min:0.01'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $row->min_order_quantity = (float) $data['min_order_quantity'];
        $row->is_active = $request->boolean('is_active', true);
        $row->save();

        return back()->with('status', 'MOQ updated.');
    }

    public function destroy(User $user, B2BCustomerProduct $row)
    {
        $this->ensureB2B($user);

        if ((int) $row->user_id !== (int) $user->id) {
            abort(404);
        }

        $row->delete();

        return back()->with('status', 'MOQ override removed (default MOQ is 1).');
    }
}
