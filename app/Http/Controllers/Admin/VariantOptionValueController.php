<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VariantOptionValueController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::query()
            ->withCount('values')
            ->orderBy('name')
            ->get();

        $selectedAttributeId = (int) $request->query('attribute_id', 0);
        $search = trim((string) $request->query('q', ''));

        $query = AttributeValue::query()
            ->with('attribute')
            ->withCount('products')
            ->leftJoin('attributes as option_groups', 'option_groups.id', '=', 'attribute_values.attribute_id')
            ->select('attribute_values.*')
            ->whereNull('option_groups.deleted_at');

        if ($selectedAttributeId > 0) {
            $query->where('attribute_values.attribute_id', $selectedAttributeId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('attribute_values.name', 'like', "%{$search}%")
                    ->orWhere('attribute_values.value', 'like', "%{$search}%")
                    ->orWhere('option_groups.name', 'like', "%{$search}%")
                    ->orWhere('option_groups.display_name', 'like', "%{$search}%");
            });
        }

        $values = $query
            ->orderBy('option_groups.name')
            ->orderBy('attribute_values.position')
            ->orderBy('attribute_values.name')
            ->paginate(50)
            ->withQueryString();

        $variantUsageCounts = $this->variantUsageCounts(
            $values->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        return view('admin.variant_option_values.index', compact(
            'attributes',
            'values',
            'variantUsageCounts',
            'selectedAttributeId',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, requireAttribute: true);

        AttributeValue::create($data);

        return redirect()
            ->route('admin.variant-option-values.index', ['attribute_id' => $data['attribute_id']])
            ->with('status', 'Variant option value created.');
    }

    public function update(Request $request, AttributeValue $value)
    {
        $data = $this->validatedData($request, requireAttribute: false);

        $value->update($data);

        return redirect()
            ->route('admin.variant-option-values.index', $this->redirectQuery($request, $value->attribute_id))
            ->with('status', 'Variant option value updated.');
    }

    public function destroy(Request $request, AttributeValue $value)
    {
        $usage = $value->usageCounts();

        if (($usage['products'] ?? 0) > 0 || ($usage['variants'] ?? 0) > 0) {
            return redirect()
                ->route('admin.variant-option-values.index', $this->redirectQuery($request, $value->attribute_id))
                ->with('error', sprintf(
                    'Cannot delete "%s" because it is used by %d product(s) and %d variant(s). Remove it from products/variants first.',
                    $value->name,
                    $usage['products'] ?? 0,
                    $usage['variants'] ?? 0
                ));
        }

        $attributeId = $value->attribute_id;
        $value->delete();

        return redirect()
            ->route('admin.variant-option-values.index', $this->redirectQuery($request, $attributeId))
            ->with('status', 'Variant option value deleted.');
    }

    protected function validatedData(Request $request, bool $requireAttribute): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ];

        if ($requireAttribute) {
            $rules['attribute_id'] = [
                'required',
                'integer',
                Rule::exists('attributes', 'id')->whereNull('deleted_at'),
            ];
        }

        $data = $request->validate($rules);

        $data['name'] = trim((string) $data['name']);
        $data['value'] = isset($data['value']) && trim((string) $data['value']) !== ''
            ? trim((string) $data['value'])
            : null;
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }

    protected function redirectQuery(Request $request, int $fallbackAttributeId): array
    {
        $query = $request->only(['attribute_id', 'q', 'page']);

        if (empty($query['attribute_id'])) {
            $query['attribute_id'] = $fallbackAttributeId;
        }

        return array_filter($query, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<int>  $attributeValueIds
     * @return array<int, int>
     */
    protected function variantUsageCounts(array $attributeValueIds): array
    {
        $attributeValueIds = array_values(array_unique(array_filter($attributeValueIds)));

        if (empty($attributeValueIds)
            || ! Schema::hasTable('product_attribute_values')
            || ! Schema::hasTable('product_variant_attribute_values')
            || ! Schema::hasColumn('product_variant_attribute_values', 'product_attribute_value_id')) {
            return [];
        }

        return DB::table('product_variant_attribute_values as pvav')
            ->join('product_attribute_values as pav', 'pav.id', '=', 'pvav.product_attribute_value_id')
            ->whereIn('pav.attribute_value_id', $attributeValueIds)
            ->select('pav.attribute_value_id', DB::raw('COUNT(DISTINCT pvav.product_variant_id) as aggregate'))
            ->groupBy('pav.attribute_value_id')
            ->pluck('aggregate', 'pav.attribute_value_id')
            ->mapWithKeys(fn ($count, $attributeValueId) => [(int) $attributeValueId => (int) $count])
            ->all();
    }
}
