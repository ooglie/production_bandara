<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class B2BApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
        public readonly string $type = 'b2b_application',
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ((bool) config('b2b_application.notifications.database', true) && Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        if ((bool) config('b2b_application.notifications.mail', true) && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.trim((string) ($notifiable->name ?? 'there')).',')
            ->line($this->message)
            ->action('View application', $this->url)
            ->line('Thank you for choosing Bandara.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
