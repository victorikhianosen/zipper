<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TwoFactorRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $otp)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(Admin|User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Two Factor Authentication Notification')
            ->greeting("Hello $notifiable->first_name")
            ->line('Please use the otp below to complete your sign in.')
            ->line(new HtmlString('<h2>'.$this->otp.'</h2>'))
            ->line('This 2fa otp will expire in 10 minutes.');
    }
}
