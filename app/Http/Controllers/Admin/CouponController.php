<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query()->withCount('redemptions');

        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($type = $request->get('type')) {
            $type = $type === 'flat' ? 'fixed' : $type;
            $query->where('discount_type', $type);
        }

        $coupons = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $data['is_active'] = $request->boolean('is_active');
        $data['created_by_id'] = $request->user()->id;
        $data['updated_by_id'] = $request->user()->id;

        Coupon::create($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validatedData($request, $coupon->id);

        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by_id'] = $request->user()->id;

        $coupon->update($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon deleted.');
    }

    protected function validatedData(Request $request, ?int $couponId = null): array
    {
        $codeRule = Rule::unique('coupons', 'code')
            ->whereNull('deleted_at');

        if ($couponId) {
            $codeRule->ignore($couponId);
        }

        $data = $request->validate([
            'code'                 => ['required', 'string', 'max:50', $codeRule],
            'name'                 => ['nullable', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],

            // Accept both names safely; persist as "fixed" for DB consistency
            'discount_type'        => ['required', Rule::in(['fixed', 'flat', 'percent'])],
            'discount_value'       => ['required', 'numeric', 'min:0.01'],
            'max_discount_amount'  => ['nullable', 'numeric', 'min:0'],

            'min_order_amount'     => ['nullable', 'numeric', 'min:0'],
            'usage_limit'          => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],

            'starts_at'            => ['nullable', 'date'],
            'ends_at'              => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['discount_type'] = $data['discount_type'] === 'flat'
            ? 'fixed'
            : $data['discount_type'];

        $data['name'] = $this->nullableString($data['name'] ?? null);
        $data['description'] = $this->nullableString($data['description'] ?? null);

        return $data;
    }

    protected function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}