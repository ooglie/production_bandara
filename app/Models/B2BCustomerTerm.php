<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class B2BCustomerTerm extends Model
{
    use HasFactory;

    protected $table = 'b2b_customer_terms';

    protected $fillable = [
        'user_id',
        'pay_later_enabled',
        'credit_limit',
        'payment_terms_days',
        'credit_status',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'pay_later_enabled' => 'boolean',
        'credit_limit' => 'float',
        'payment_terms_days' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
