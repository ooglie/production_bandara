<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TicketTag extends Model
{
    protected $table = 'ticket_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(
            Ticket::class,
            'ticket_tag_ticket', // pivot
            'ticket_tag_id',     // pivot FK to ticket_tags
            'ticket_id'          // pivot FK to tickets
        );
        // ✅ IMPORTANT: pivot has NO created_at/updated_at, so do NOT call withTimestamps()
    }
}