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
        $data = $this->normalizedData($this->validatedData($request));

        $data['attribute_id'] = $attribute->id;

        AttributeValue::create($data);

        return redirect()
            ->route('admin.attributes.values.index', $attribute)
            ->with('status', 'Variant option value created.');
    }

    public function edit(AttributeValue $value)
    {
        $attribute = $value->attribute;

        return view('admin.attributes.values.edit', compact('attribute', 'value'));
    }

    public function update(Request $request, AttributeValue $value)
    {
        $data = $this->normalizedData($this->validatedData($request));

        $value->update($data);

        return redirect()
            ->route('admin.attributes.values.index', $value->attribute)
            ->with('status', 'Variant option value updated.');
    }

    public function destroy(AttributeValue $value)
    {
        $attribute = $value->attribute;
        $usage = $value->usageCounts();

        if (($usage['products'] ?? 0) > 0 || ($usage['variants'] ?? 0) > 0) {
            return redirect()
                ->route('admin.attributes.values.index', $attribute)
                ->with('error', sprintf(
                    'Cannot delete "%s" because it is used by %d product(s) and %d variant(s). Remove it from products/variants first.',
                    $value->name,
                    $usage['products'] ?? 0,
                    $usage['variants'] ?? 0
                ));
        }

        $value->delete();

        return redirect()
            ->route('admin.attributes.values.index', $attribute)
            ->with('status', 'Variant option value deleted.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'value'    => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);
    }

    protected function normalizedData(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['value'] = isset($data['value']) && trim((string) $data['value']) !== ''
            ? trim((string) $data['value'])
            : null;
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }
}

