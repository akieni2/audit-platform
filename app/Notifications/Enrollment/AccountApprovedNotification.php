<?php

namespace App\Notifications\Enrollment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[DGCPT] Votre compte a été approuvé')
            ->greeting('Bonjour '.$notifiable->displayName().',')
            ->line('Votre compte DGCPT a été approuvé.')
            ->line('Vous pouvez maintenant vous connecter à la plateforme.')
            ->action('Se connecter', route('login', absolute: true));
    }
}
