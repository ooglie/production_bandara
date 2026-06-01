<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'ingredients',
        'steps',
        'prep_time_minutes',
        'cook_time_minutes',
        'servings',
        'image_path',
        'video_url',
        'sort_order',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'short_description' => 'array',
        'description' => 'array',
        'ingredients' => 'array',
        'steps' => 'array',
        'prep_time_minutes' => 'integer',
        'cook_time_minutes' => 'integer',
        'servings' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_recipe')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tr(string $field, ?string $locale = null, string $fallback = 'en')
    {
        $locale = $locale ?: app()->getLocale();
        $value = $this->{$field} ?? [];

        if (!is_array($value)) {
            return $value;
        }

        return $value[$locale]
            ?? $value[$fallback]
            ?? (count($value) ? reset($value) : null);
    }

    public function trList(string $field, ?string $locale = null, string $fallback = 'en'): array
    {
        $locale = $locale ?: app()->getLocale();
        $value = $this->{$field} ?? [];

        if (!is_array($value)) {
            return [];
        }

        $list = $value[$locale]
            ?? $value[$fallback]
            ?? (count($value) ? reset($value) : []);

        return is_array($list) ? $list : [];
    }
}