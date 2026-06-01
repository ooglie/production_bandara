<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',     // ✅ DB column in your project
        'body',        // ✅ kept for backward compatibility
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    /**
     * Compatibility layer:
     * - If DB column is `message`, treat `body` as an alias.
     * - If DB column is `body`, treat `message` as an alias.
     */
    public function setBodyAttribute($value): void
    {
        if (Schema::hasColumn($this->getTable(), 'message')) {
            $this->attributes['message'] = $value;
            return;
        }
        $this->attributes['body'] = $value;
    }

    public function getBodyAttribute()
    {
        if (array_key_exists('body', $this->attributes)) {
            return $this->attributes['body'];
        }
        return $this->attributes['message'] ?? null;
    }

    public function setMessageAttribute($value): void
    {
        if (Schema::hasColumn($this->getTable(), 'message')) {
            $this->attributes['message'] = $value;
            return;
        }
        $this->attributes['body'] = $value;
    }

    public function getMessageAttribute()
    {
        if (array_key_exists('message', $this->attributes)) {
            return $this->attributes['message'];
        }
        return $this->attributes['body'] ?? null;
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments()
    {
        // assumes ticket_attachments has ticket_message_id FK (your controllers do)
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }
}