<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var ProductCollection|null $productCollection */
        $productCollection = $this->route('productCollection');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_collections', 'slug')->ignore($productCollection?->id),
            ],
            'kind' => ['required', Rule::in(['general', 'occasion', 'chef', 'seasonal', 'campaign'])],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],

            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:2048'],

            'selection_mode' => ['nullable', Rule::in(['manual', 'rule'])],
            'rules' => ['nullable', 'array'],

            'is_active' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
            'home_section' => ['nullable', Rule::in(['occasions', 'chef_picks', 'seasonal', 'general'])],
            'home_order' => ['nullable', 'integer', 'min:0', 'max:999999'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],

            'product_sort_orders' => ['nullable', 'array'],
            'product_featured' => ['nullable', 'array'],
        ];
    }
}