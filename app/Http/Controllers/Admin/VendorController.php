<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $vendors = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $data['is_active'] = $request->boolean('is_active');

        Vendor::create($data);

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', 'Vendor created.');
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $this->validatedData($request, $vendor->id);

        $data['is_active'] = $request->boolean('is_active');

        $vendor->update($data);

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', 'Vendor updated.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['invoices', 'payments']);
        $suppliedProducts = $vendor->suppliedProducts();

        return view('admin.vendors.show', compact('vendor', 'suppliedProducts'));
    }


    public function destroy(Vendor $vendor)
    {
        $vendor->delete(); // uses soft deletes

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', 'Vendor deleted.');
    }

    protected function validatedData(Request $request, ?int $vendorId = null): array
    {
        $bankName = trim((string) $request->input('bank_name', ''));
        $bankIfscCode = strtoupper((string) preg_replace('/\s+/', '', (string) $request->input('bank_ifsc_code', '')));
        $bankAccountNumber = (string) preg_replace('/[\s-]+/', '', (string) $request->input('bank_account_number', ''));

        $request->merge([
            'bank_name' => $bankName !== '' ? $bankName : null,
            'bank_ifsc_code' => $bankIfscCode !== '' ? $bankIfscCode : null,
            'bank_account_number' => $bankAccountNumber !== '' ? $bankAccountNumber : null,
        ]);

        $codeRule = Rule::unique('vendors', 'code')
            ->whereNull('deleted_at');

        if ($vendorId) {
            $codeRule->ignore($vendorId);
        }


        return $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50', $codeRule],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],

            'gst_number'    => ['nullable', 'string', 'max:50'],
            'fssai_number'  => ['nullable', 'string', 'max:50'],

            'bank_name'           => ['nullable', 'string', 'max:150'],
            'bank_ifsc_code'      => ['nullable', 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_account_number' => ['nullable', 'string', 'min:6', 'max:34', 'regex:/^[0-9]+$/'],

            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'state_code'    => ['nullable', 'string', 'max:10'],
            'country'       => ['nullable', 'string', 'max:100'],
            'pincode'       => ['nullable', 'string', 'max:20'],

            'notes'         => ['nullable', 'string'],
        ], [
            'bank_ifsc_code.size' => 'The IFSC code must contain exactly 11 characters.',
            'bank_ifsc_code.regex' => 'Enter a valid IFSC code, for example HDFC0001234.',
            'bank_account_number.min' => 'The bank account number must contain at least 6 digits.',
            'bank_account_number.max' => 'The bank account number may not contain more than 34 digits.',
            'bank_account_number.regex' => 'The bank account number may contain digits only.',
        ]);
    }
}
