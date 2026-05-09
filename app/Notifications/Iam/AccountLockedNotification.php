<?php

namespace App\Notifications\Iam;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Compte temporairement verrouillé',
            'body' => 'Trop de tentatives de connexion. Réessayez après la période de blocage ou contactez l’administration.',
            'severity' => 'warning',
        ];
    }
}
