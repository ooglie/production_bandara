<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductLabelBatchRequest extends FormRequest
{
    public const MAX_LABELS = 100;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach ([
            'category',
            'country',
            'product_name',
            'company_name',
            'fssai',
            'website',
            'manual_weights',
        ] as $field) {
            $trimmed[$field] = trim((string) $this->input($field, ''));
        }

        $pricePerKg = str_replace(
            [',', '₹', ' '],
            '',
            trim((string) $this->input('price_per_kg', '')),
        );

        $this->merge(array_merge($trimmed, [
            'price_per_kg' => $pricePerKg,
            'inventory_piece_ids' => array_values(array_filter((array) $this->input('inventory_piece_ids', []))),
            'inventory_pack_ids' => array_values(array_filter((array) $this->input('inventory_pack_ids', []))),
            'disposition' => $this->input('disposition', 'inline'),
        ]));
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:24'],
            'country' => ['required', 'string', 'max:32'],
            'product_name' => ['required', 'string', 'max:64'],
            'price_per_kg' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'company_name' => ['required', 'string', 'max:40'],
            'fssai' => ['required', 'regex:/^[0-9]{14}$/'],
            'website' => ['required', 'url:http,https', 'max:100'],
            'best_before' => ['required', 'date_format:Y-m'],
            'manual_weights' => ['nullable', 'string', 'max:5000'],
            'inventory_piece_ids' => ['nullable', 'array', 'max:'.self::MAX_LABELS],
            'inventory_piece_ids.*' => ['integer', 'distinct', 'exists:inventory_pieces,id'],
            'inventory_pack_ids' => ['nullable', 'array', 'max:'.self::MAX_LABELS],
            'inventory_pack_ids.*' => ['integer', 'distinct', 'exists:inventory_packs,id'],
            'disposition' => ['required', Rule::in(['inline', 'download'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('best_before')) {
                    $bestBefore = CarbonImmutable::createFromFormat('!Y-m', (string) $this->input('best_before'));
                    if ($bestBefore->endOfMonth()->isBefore(now()->startOfMonth())) {
                        $validator->errors()->add('best_before', 'Best before must be the current month or later.');
                    }
                }

                $manualWeights = $this->manualWeightTokens();
                foreach ($manualWeights as $index => $weight) {
                    if (! is_numeric($weight) || (float) $weight < 0.001 || (float) $weight > 999.999) {
                        $validator->errors()->add(
                            'manual_weights',
                            'Weight #'.($index + 1).' must be between 0.001 kg and 999.999 kg.',
                        );
                        break;
                    }
                }

                $labelCount = count((array) $this->input('inventory_piece_ids', []))
                    + count((array) $this->input('inventory_pack_ids', []))
                    + count($manualWeights);

                if ($labelCount === 0) {
                    $validator->errors()->add('manual_weights', 'Select inventory or enter at least one weight.');
                } elseif ($labelCount > self::MAX_LABELS) {
                    $validator->errors()->add('manual_weights', 'A batch can contain at most '.self::MAX_LABELS.' labels.');
                }
            },
        ];
    }

    /** @return array<int, float> */
    public function manualWeights(): array
    {
        return array_map('floatval', $this->manualWeightTokens());
    }

    /** @return array<int, string> */
    private function manualWeightTokens(): array
    {
        $value = preg_replace('/\bkg\b/i', '', (string) $this->input('manual_weights', ''));

        return array_values(array_filter(
            preg_split('/[\s,;]+/u', trim((string) $value)) ?: [],
            fn (string $token) => $token !== '',
        ));
    }
}
