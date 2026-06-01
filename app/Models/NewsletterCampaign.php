<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $table = 'newsletter_campaigns';

    protected $fillable = [
        'name',
        'subject',
        'content_html',
        'content_text',
        'status',
        'scheduled_for',
        'sent_at',
        'created_by_id',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at'       => 'datetime',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function recipients()
    {
        return $this->hasMany(NewsletterCampaignRecipient::class, 'campaign_id');
    }
}
