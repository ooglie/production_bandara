<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePaymentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'payment_id',
        'amount',
        'currency',
        'method',
        'status',
        'reference',
        'paid_on',
        'bank_name',
        'account_holder_name',
        'cheque_number',
        'cheque_date',
        'cheque_bank_name',
        'cheque_branch_name',
        'proof_path',
        'customer_note',
        'admin_note',
        'approved_by_id',
        'approved_at',
        'rejected_by_id',
        'rejected_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_on' => 'date',
        'cheque_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'bank_transfer' => 'NEFT / RTGS / IMPS',
            'upi' => 'UPI',
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            default => str((string) $this->method)->replace('_', ' ')->headline()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => 'Pending approval',
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
