<?php

namespace App\Notifications;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $password)
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
    public function toMail(Admin $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Created')
            ->line('You are receiving this email because an admin account has been created for you on '.env('APP_NAME').'.')
            ->line('Please use the credentials below to sign in.')
            ->line(new HtmlString('Email: <b>'.$notifiable->email.'</b>'))
            ->line(new HtmlString('Password: <b>'.$this->password.'</b>'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
