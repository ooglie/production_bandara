<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveB2BApplicationRequirementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => $this->input('intent') === 'submit' ? 'submit' : 'save',
            'terms_accepted' => $this->boolean('terms_accepted'),
        ]);
    }

    public function rules(): array
    {
        $submitting = $this->input('intent') === 'submit';

        return [
            'intent' => ['required', Rule::in(['save', 'submit'])],
            'interested_categories' => [$submitting ? 'required' : 'nullable', 'array', $submitting ? 'min:1' : 'max:20'],
            'interested_categories.*' => ['string', Rule::in(array_keys((array) config('b2b_application.product_categories', [])))],
            'estimated_monthly_purchase' => [$submitting ? 'required' : 'nullable', Rule::in(array_keys((array) config('b2b_application.monthly_purchase_ranges', [])))],
            'purchase_frequency' => [$submitting ? 'required' : 'nullable', Rule::in(array_keys((array) config('b2b_application.purchase_frequencies', [])))],
            'requirements_message' => ['nullable', 'string', 'max:5000'],
            'terms_accepted' => [$submitting ? 'accepted' : 'nullable'],
        ];
    }
}
