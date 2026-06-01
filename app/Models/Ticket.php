<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    // ✅ IMPORTANT: make sure Ticket points to tickets table (not ticket_tags)
    protected $table = 'tickets';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        // You use user_id in Customer controller, so keep that as the FK.
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('id','desc');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketTag::class,
            'ticket_tag_ticket',   // ✅ your pivot table
            'ticket_id',           // ✅ pivot FK to tickets
            'ticket_tag_id'        // ✅ pivot FK to ticket_tags
        );
        // ✅ DO NOT call withTimestamps() because pivot has no created_at/updated_at
    }

    public function displayCustomerName(): string
    {
        return $this->customer?->name
            ?? $this->customer_email
            ?? 'Customer';
    }

    public function displaySubject(): string
    {
        return $this->subject
            ?? $this->title
            ?? 'Ticket';
    }

    public function displayNumber(): string
    {
        return (string)($this->ticket_number ?? ('#' . $this->id));
    }
}