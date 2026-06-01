<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['info', 'special', 'festive'])],
            'icon' => ['nullable', 'string', 'max:20'],

            'background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:4096'],
            'remove_background_image' => ['nullable', 'boolean'],

            'cta_text' => ['nullable', 'string', 'max:100', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'string', 'max:2048', 'required_with:cta_text'],

            'secondary_text' => ['nullable', 'string', 'max:100', 'required_with:secondary_url'],
            'secondary_url' => ['nullable', 'string', 'max:2048', 'required_with:secondary_text'],

            'is_active' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
            'is_dismissible' => ['nullable', 'boolean'],

            'priority' => ['nullable', 'integer', 'min:0', 'max:999999'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}