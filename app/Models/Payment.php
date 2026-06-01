<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // If your table is 'payments', this is optional, but explicit is fine:
    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'currency',
        'method',
        'status',
        'transaction_id',
        'payment_data',
        'reference',
        'received_date',
        'notes',
        'recorded_by_id',
        'cheque_number',
        'cheque_date',
        'cheque_bank_name',
        'cheque_branch_name',
        'paid_at',
    ];

    protected $casts = [
        'payment_data'  => 'array',
        'received_date' => 'date',
        'cheque_date'   => 'date',
        'paid_at'       => 'datetime',
    ];

    /**
     * Payment can be allocated across many invoices.
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_payments')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }

    /**
     * Optional main order this payment is associated with (for Razorpay etc.).
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Customer this payment belongs to (if set).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin / accountant who recorded this payment.
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}





// <!-- <?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class Payment extends Model
// {
//     use HasFactory;

//     protected $table = 'payments';

//     protected $fillable = [
//         'order_id',
//         'amount',
//         'currency',
//         'method',
//         'status',
//         'transaction_id',
//         'payment_data',
//         'paid_at',
//     ];

//     protected $casts = [
//         'amount'       => 'float',
//         'payment_data' => 'array',
//         'paid_at'      => 'datetime',
//     ];

//     public function order()
//     {
//         return $this->belongsTo(Order::class);
//     }
// } -->
