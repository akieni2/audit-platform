<?php

namespace App\Notifications\Cfdt;

use App\Models\CfdtEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CfdtTestAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public CfdtEnrollment $enrollment, public bool $mailOnly = false) {}

    public function via(object $notifiable): array
    {
        return $this->mailOnly ? ['mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CFDT — nouveau test à réaliser')
            ->greeting('Bonjour '.trim($notifiable->prenom.' '.$notifiable->name).',')
            ->line('Le test « '.$this->enrollment->course->title.' » vous a été assigné.')
            ->line('Date limite : '.($this->enrollment->expires_at?->format('d/m/Y à H:i') ?? 'non définie').'.')
            ->action('Accéder au test', route('cfdt.invitation', $this->enrollment->invitation_token))
            ->line('Ce lien est personnel et devient inutilisable après la date limite.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nouveau test CFDT',
            'message' => $this->enrollment->course->title,
            'url' => route('cfdt.invitation', $this->enrollment->invitation_token),
            'expires_at' => $this->enrollment->expires_at?->toIso8601String(),
        ];
    }
}
