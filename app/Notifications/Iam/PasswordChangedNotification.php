<?php

namespace App\Notifications\Iam;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public bool $forcedFirstLogin = false,
    ) {}

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
            'title' => 'Mot de passe modifié',
            'body' => $this->forcedFirstLogin
                ? 'Premier changement de mot de passe effectué — accès plateforme conforme.'
                : 'Votre mot de passe a été mis à jour avec succès.',
            'severity' => 'info',
        ];
    }
}
