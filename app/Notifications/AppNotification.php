<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string[]  $channels
     */
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly array $channels = ['mail', 'database', 'firebase'],
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(Str::headline($this->title))
            ->line(new HtmlString($this->body));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => Str::headline($this->title),
            'body' => $this->body,
        ];
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(User $notifiable): mixed
    {
        if ($notifiable->device_tokens) {
            return (new FirebaseMessage())
                ->withTitle(Str::headline($this->title))
                ->withBody($this->body)
                ->asNotification($notifiable->device_tokens);
        }

        return null;
    }
}
