<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaignRecipient extends Model
{
    use HasFactory;

    protected $table = 'newsletter_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'sent_at',
        'open_count',
        'last_opened_at',
        'bounce_status',
        'unsubscribe_token',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'last_opened_at'=> 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(NewsletterCampaign::class, 'campaign_id');
    }

    public function subscriber()
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'subscriber_id');
    }
}
