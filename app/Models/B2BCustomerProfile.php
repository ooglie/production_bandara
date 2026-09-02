<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BCustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'b2b_customer_profiles';

    protected $fillable = [
        'user_id', 'b2b_application_id', 'legal_business_name', 'trading_name', 'business_type',
        'gstin', 'pan', 'fssai_number', 'address_line_1', 'address_line_2', 'state_id', 'city_id',
        'state_name', 'city_name', 'postal_code', 'price_group_id', 'pay_later_enabled', 'credit_limit',
        'payment_terms_days', 'minimum_order_value', 'delivery_arrangement', 'account_manager_id',
        'approved_by', 'approved_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pay_later_enabled' => 'boolean',
            'credit_limit' => 'decimal:2',
            'minimum_order_value' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(B2BApplication::class, 'b2b_application_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
