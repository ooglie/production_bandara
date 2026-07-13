<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'collage_image_path',
        'collage_updated_at',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'collage_updated_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }


    public function getDisplayImagePathAttribute(): ?string
    {
        return $this->image_path ?: $this->collage_image_path;
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        $path = $this->display_image_path;

        if (! filled($path)) {
            return null;
        }

        if (str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')) {
            return (string) $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
