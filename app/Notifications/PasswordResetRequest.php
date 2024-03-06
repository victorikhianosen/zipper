<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PasswordResetRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly string $otp)
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
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Password Reset Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->line('Please use the otp below to reset your password.')
            ->line(new HtmlString('<h2>'.$this->otp.'</h2>'))
            ->line('This password reset otp will expire in 10 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
