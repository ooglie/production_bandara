<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'label',
        'message',
        'type',
        'icon',
        'background_image_path',
        'cta_text',
        'cta_url',
        'secondary_text',
        'secondary_url',
        'is_active',
        'show_on_home',
        'is_dismissible',
        'priority',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_home' => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeActiveForHome(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_on_home', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->latest();
    }
}