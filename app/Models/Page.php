<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'excerpt' => 'array',
        'content' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'is_active' => 'bool',
        'sort_order' => 'int',
    ];

    public function tr(string $field, string $locale = 'en', ?string $fallbackLocale = 'en'): ?string
    {
        $value = $this->{$field} ?? null;

        if (is_array($value)) {
            return $value[$locale]
                ?? ($fallbackLocale ? ($value[$fallbackLocale] ?? null) : null)
                ?? collect($value)->filter(fn ($item) => filled($item))->first();
        }

        return filled($value) ? (string) $value : null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
