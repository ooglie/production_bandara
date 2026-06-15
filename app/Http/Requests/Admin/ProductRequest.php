<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isDraftSave(): bool
    {
        return $this->boolean('save_as_draft')
            || ! $this->boolean('is_active')
            || (string) $this->input('inventory_role', 'saleable') === 'internal';
    }

    protected function prepareForValidation(): void
    {
        $normalizeIntOrNull = function ($value) {
            return ($value === '' || $value === null) ? null : (int) $value;
        };

        $normalizeArrayInts = function ($values) {
            return collect(is_array($values) ? $values : [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        };

        $normalizeMultiline = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            return trim(preg_replace('/\r\n?|\n/', "\n", (string) $value));
        };

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'sku' => trim((string) $this->input('sku', '')),
            'slug' => trim((string) $this->input('slug', '')),
            'barcode' => trim((string) $this->input('barcode', '')),
            'short_description' => trim((string) $this->input('short_description', '')),
            'description' => trim((string) $this->input('description', '')),
            'storage_guidance' => $normalizeMultiline($this->input('storage_guidance')),
            'delivery_support' => $normalizeMultiline($this->input('delivery_support')),

            'type' => $this->input('type', 'simple'),
            'inventory_role' => $this->filled('inventory_role') ? $this->input('inventory_role') : 'saleable',
            'pack_type' => $this->filled('pack_type') ? $this->input('pack_type') : 'quantity',
            'country_of_origin' => $this->filled('country_of_origin')
                ? strtoupper(trim((string) $this->input('country_of_origin')))
                : null,

            'vendor_id' => $normalizeIntOrNull($this->input('vendor_id')),
            'hsn_code_id' => $normalizeIntOrNull($this->input('hsn_code_id')),

            'lot_stage_default' => $this->filled('lot_stage_default')
                ? $this->input('lot_stage_default')
                : null,

            'special_starts_at' => $this->filled('special_starts_at')
                ? $this->input('special_starts_at')
                : null,

            'special_ends_at' => $this->filled('special_ends_at')
                ? $this->input('special_ends_at')
                : null,

            'category_ids' => $normalizeArrayInts($this->input('category_ids', [])),
            'attribute_value_ids' => $normalizeArrayInts($this->input('attribute_value_ids', [])),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product
            ? $product->getKey()
            : (is_numeric($product) ? (int) $product : null);

        $isDraft = $this->isDraftSave();
        $isVariable = (string) $this->input('type', 'simple') === 'variable';
        $packType = (string) $this->input('pack_type', 'quantity');
        $productWeightRules = $isDraft || $isVariable || $packType === 'fixed_piece_pack'
            ? ['nullable', 'numeric', 'min:0']
            : ['required', 'numeric', 'gt:0'];
        $piecesPerPackRules = (! $isDraft && ! $isVariable && $packType === 'fixed_piece_pack')
            ? ['required', 'numeric', 'gt:0']
            : ['nullable', 'numeric', 'min:0'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'storage_guidance' => ['nullable', 'string', 'max:5000'],
            'delivery_support' => ['nullable', 'string', 'max:5000'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],

            'sku' => [
                'required',
                'string',
                'max:191',
                Rule::unique('products', 'sku')->ignore($productId),
            ],

            'type' => ['required', Rule::in(['simple', 'variable'])],
            'inventory_role' => ['required', Rule::in(['internal', 'saleable', 'both'])],

            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'barcode' => ['nullable', 'string', 'max:191'],

            'mrp_price' => ($isDraft || $isVariable)
                ? ['nullable', 'numeric', 'min:0']
                : ['required', 'numeric', 'gt:0'],
            'base_price' => ($isDraft || $isVariable)
                ? ['nullable', 'numeric', 'min:0']
                : ['required', 'numeric', 'gt:0'],
            'b2c_price_includes_gst' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
            'b2b_price_includes_gst' => ['required', Rule::in(['0', '1', 0, 1, true, false])],

            'hsn_code_id' => ['required', 'integer', 'exists:hsn_codes,id'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'sell_unit' => ['nullable', Rule::in(['piece', 'kg', 'pack'])],
            'pack_type' => ['nullable', Rule::in(['bulk', 'quantity', 'fixed_weight_pack', 'fixed_piece_pack', 'variable_weight'])],
            'product_weight' => $productWeightRules,
            'pieces_per_pack' => $piecesPerPackRules,

            'country_of_origin' => ['required', 'string', 'size:2', 'exists:countries,code'],

            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],

            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0'],

            'lot_stage_default' => ['nullable', Rule::in(['raw', 'slab', 'slice', 'trim', 'waste'])],

            'inventory_is_saleable' => [Rule::in(['0', '1', 0, 1, true, false])],
            'inventory_can_repack' => [Rule::in(['0', '1', 0, 1, true, false])],

            'manage_stock' => [Rule::in(['0', '1', 0, 1, true, false])],
            'dynamic_pricing_enabled' => [Rule::in(['0', '1', 0, 1, true, false])],
            'is_featured' => [Rule::in(['0', '1', 0, 1, true, false])],
            'is_new' => [Rule::in(['0', '1', 0, 1, true, false])],
            'is_special' => [Rule::in(['0', '1', 0, 1, true, false])],
            'is_active' => [Rule::in(['0', '1', 0, 1, true, false])],
            'save_as_draft' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],

            'special_price' => ['nullable', 'numeric', 'min:0'],
            'standard_b2b_price' => ['nullable', 'numeric', 'min:0'],
            'standard_b2b_min_order_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'special_audience' => ['nullable', Rule::in(['b2c', 'b2b', 'all'])],
            'special_starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'special_ends_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:special_starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'short_description.required' => 'Short description is required.',
            'description.required' => 'Full description is required.',

            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU has already been taken.',

            'type.required' => 'Type is required.',

            'mrp_price.required' => 'MRP is required before activating/publishing the product.',
            'base_price.required' => 'Sell price is required before activating/publishing the product.',
            'mrp_price.gt' => 'MRP must be greater than zero before activation.',
            'base_price.gt' => 'Sell price must be greater than zero before activation.',
            'b2c_price_includes_gst.required' => 'B2C price mode is required.',
            'b2b_price_includes_gst.required' => 'B2B price mode is required.',

            'hsn_code_id.required' => 'HSN is required.',
            'gst_rate.required' => 'GST rate is required.',

            'product_weight.required' => 'Product weight / pack weight is required before activating/publishing this product.',
            'product_weight.gt' => 'Product weight / pack weight must be greater than zero before activation.',
            'pieces_per_pack.required' => 'Pieces per pack is required for fixed piece pack products.',
            'pieces_per_pack.gt' => 'Pieces per pack must be greater than zero.',

            'country_of_origin.required' => 'Country of origin is required.',
            'country_of_origin.exists' => 'Please select a valid country of origin.',

            'category_ids.required' => 'Please select at least one category.',
            'category_ids.min' => 'Please select at least one category.',

            'slug.unique' => 'This slug has already been taken.',

            'special_ends_at.after_or_equal' => 'Special end time must be after or equal to the start time.',
        ];
    }
}