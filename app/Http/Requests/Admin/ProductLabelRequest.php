<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductLabelRequest extends FormRequest
{
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
            'unit_label',
            'company_name',
            'fssai',
            'website',
        ] as $field) {
            $trimmed[$field] = trim((string) $this->input($field, ''));
        }

        $price = str_replace(
            [',', '₹', ' '],
            '',
            trim((string) $this->input('price', '')),
        );

        $this->merge(array_merge($trimmed, [
            'price' => $price,
            'copies' => $this->input('copies', 1),
            'disposition' => $this->input('disposition', 'inline'),
        ]));
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:24'],
            'country' => ['required', 'string', 'max:32'],
            'product_name' => ['required', 'string', 'max:64'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'unit_label' => ['required', 'string', 'max:24'],
            'company_name' => ['required', 'string', 'max:40'],
            'fssai' => ['required', 'regex:/^[0-9]{14}$/'],
            'website' => ['required', 'url:http,https', 'max:100'],
            'best_before' => ['required', 'date_format:Y-m'],
            'copies' => ['required', 'integer', 'min:1', 'max:100'],
            'disposition' => ['required', Rule::in(['inline', 'download'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('best_before')) {
                    return;
                }

                $bestBefore = CarbonImmutable::createFromFormat('!Y-m', (string) $this->input('best_before'));
                if ($bestBefore->endOfMonth()->isBefore(now()->startOfMonth())) {
                    $validator->errors()->add('best_before', 'Best before must be the current month or later.');
                }
            },
        ];
    }
}
