<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public NewsletterCampaign $campaign;
    public NewsletterSubscriber $subscriber;
    public NewsletterCampaignRecipient $recipient;

    public function __construct(
        NewsletterCampaign $campaign,
        NewsletterSubscriber $subscriber,
        NewsletterCampaignRecipient $recipient
    ) {
        $this->campaign  = $campaign;
        $this->subscriber = $subscriber;
        $this->recipient = $recipient;
    }

    public function build()
    {
        // Signed unsubscribe link
        $unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', [
            'subscriber' => $this->subscriber->id,
        ]);

        // Plain text fallback
        $textContent = $this->campaign->content_text
            ?: strip_tags($this->campaign->content_html ?? '');

        return $this->subject($this->campaign->subject)
            ->view('emails.newsletter.campaign', [
                'campaign'       => $this->campaign,
                'subscriber'     => $this->subscriber,
                'unsubscribeUrl' => $unsubscribeUrl,
            ])
            ->text('emails.newsletter.campaign_plain', [
                'campaign'       => $this->campaign,
                'subscriber'     => $this->subscriber,
                'unsubscribeUrl' => $unsubscribeUrl,
                'textContent'    => $textContent,
            ]);
    }
}
