<?php

namespace App\Notifications\Cfdt;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CfdtAccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $resetUrl, public string $cfdtRole, public bool $mailOnly = false) {}

    public function via(object $notifiable): array { return $this->mailOnly ? ['mail'] : ['database']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Création de votre compte CFDT')
            ->greeting('Bonjour '.trim($notifiable->prenom.' '.$notifiable->name).',')
            ->line('Votre compte CFDT a été créé avec le profil '.$this->cfdtRole.'.')
            ->action('Définir mon mot de passe', $this->resetUrl)
            ->line('Pour votre sécurité, ce lien est temporaire.');
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Compte CFDT créé', 'message' => 'Définissez votre mot de passe.', 'url' => $this->resetUrl];
    }
}
