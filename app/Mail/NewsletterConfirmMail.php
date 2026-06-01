<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmMail extends Mailable
{
    use Queueable, SerializesModels;

    public NewsletterSubscriber $subscriber;

    public function __construct(NewsletterSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function build()
    {
        $confirmUrl = route('newsletter.confirm', [
            'subscriber' => $this->subscriber->id,
            'token'      => $this->subscriber->confirmation_token,
        ]);

        $unsubscribeUrl = route('newsletter.unsubscribe', [
            'subscriber' => $this->subscriber->id,
        ], false);

        return $this->subject('Confirm your subscription to Frozen - Bandara newsletter')
            ->view('emails.newsletter.confirm', [
                'subscriber'     => $this->subscriber,
                'confirmUrl'     => $confirmUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);
    }
}
