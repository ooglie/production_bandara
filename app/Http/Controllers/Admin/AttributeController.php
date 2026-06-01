<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::query()->withCount('values');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $attributes = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_filterable'] = $request->boolean('is_filterable');
        
        Attribute::create($data);

        return redirect()
            ->route('admin.attributes.index')
            ->with('status', 'Attribute created.');
    }

    public function edit(Attribute $attribute)
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $data = $this->validatedData($request, $attribute->id);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_filterable'] = $request->boolean('is_filterable');

        $attribute->update($data);

        return redirect()
            ->route('admin.attributes.index')
            ->with('status', 'Attribute updated.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()
            ->route('admin.attributes.index')
            ->with('status', 'Attribute deleted.');
    }

    protected function validatedData(Request $request, ?int $attributeId = null): array
    {
        $slugRule = Rule::unique('attributes', 'slug')
            ->whereNull('deleted_at');

        if ($attributeId) {
            $slugRule->ignore($attributeId);
        }


        return $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', $slugRule],
            'display_name'  => ['nullable', 'string', 'max:255'],
            'frontend_type' => ['required', Rule::in(['select', 'radio', 'label', 'text'])],
            'is_filterable' => ['nullable', 'boolean'],
        ]);
    }
}
