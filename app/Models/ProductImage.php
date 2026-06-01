<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'file_path',
        'alt_text',
        'position',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'bool',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
