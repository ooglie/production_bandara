<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use Illuminate\Http\Request;

class HsnCodeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $hsnCodes = HsnCode::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('code', 'like', '%' . $q . '%')
                      ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->withCount('products')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('admin.hsn-codes.index', compact('hsnCodes', 'q'));
    }

    public function create()
    {
        return view('admin.hsn-codes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:32', 'unique:hsn_codes,code'],
            'gst_rate'    => ['required', 'numeric', 'min:0', 'max:100'],
            'name'        => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $data['code'] = trim($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        HsnCode::create($data);

        return redirect()
            ->route('admin.hsn-codes.index')
            ->with('status', 'HSN/GST code created.');
    }

    public function edit(HsnCode $hsnCode)
    {
        return view('admin.hsn-codes.edit', compact('hsnCode'));
    }

    public function update(Request $request, HsnCode $hsnCode)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:32', 'unique:hsn_codes,code,' . $hsnCode->id],
            'gst_rate'    => ['required', 'numeric', 'min:0', 'max:100'],
            'name'        => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $data['code'] = trim($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        $hsnCode->update($data);

        return redirect()
            ->route('admin.hsn-codes.index')
            ->with('status', 'HSN/GST code updated.');
    }

    public function destroy(HsnCode $hsnCode)
    {
        // If you want to prevent deletion when products exist, uncomment:
        // if ($hsnCode->products()->exists()) {
        //     return back()->withErrors(['hsn' => 'Cannot delete. Products are linked to this HSN code.']);
        // }

        $hsnCode->delete();

        return back()->with('status', 'HSN/GST code deleted.');
    }
}
