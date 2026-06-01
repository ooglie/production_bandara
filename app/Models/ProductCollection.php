<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCollection extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'kind',
        'eyebrow',
        'description',
        'image_path',
        'cta_text',
        'cta_url',
        'selection_mode',
        'rules',
        'is_active',
        'show_on_home',
        'home_section',
        'home_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'show_on_home' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collection_product')
            ->withPivot(['sort_order', 'is_featured'])
            ->withTimestamps()
            ->orderBy('product_collection_product.sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeHomeSection(Builder $query, string $section): Builder
    {
        return $query
            ->active()
            ->where('show_on_home', true)
            ->where('home_section', $section)
            ->orderBy('home_order');
    }
}