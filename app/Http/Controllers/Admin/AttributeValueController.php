<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Attribute $attribute)
    {
        $values = $attribute->values()
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(50);

        return view('admin.attributes.values.index', compact('attribute', 'values'));
    }

    public function create(Attribute $attribute)
    {
        return view('admin.attributes.values.create', compact('attribute'));
    }

    public function store(Request $request, Attribute $attribute)
    {
        $data = $this->validatedData($request);

        $data['attribute_id'] = $attribute->id;

        AttributeValue::create($data);

        return redirect()
            ->route('admin.attributes.values.index', $attribute)
            ->with('status', 'Attribute value created.');
    }

    public function edit(AttributeValue $value)
    {
        $attribute = $value->attribute;

        return view('admin.attributes.values.edit', compact('attribute', 'value'));
    }

    public function update(Request $request, AttributeValue $value)
    {
        $data = $this->validatedData($request);

        $value->update($data);

        return redirect()
            ->route('admin.attributes.values.index', $value->attribute)
            ->with('status', 'Attribute value updated.');
    }

    public function destroy(AttributeValue $value)
    {
        $attribute = $value->attribute;

        $value->delete();

        return redirect()
            ->route('admin.attributes.values.index', $attribute)
            ->with('status', 'Attribute value deleted.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'value'    => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);
    }
}
