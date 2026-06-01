<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsnCode extends Model
{
    protected $fillable = [
        'code',
        'gst_rate',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'gst_rate'   => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        $name = $this->name ? ' — ' . $this->name : '';
        $rate = number_format((float) $this->gst_rate, 2);

        return "{$this->code}{$name} ({$rate}% GST)";
    }
}
